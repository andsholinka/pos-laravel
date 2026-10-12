<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StoreSetting;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Turns a validated cart into a Sale. Prices, discount, tax and change are always
 * recomputed from the database (client numbers are never trusted), stock is
 * decremented through StockService under row locks, and void() reverses it.
 */
class CheckoutService
{
    private const MAX_ATTEMPTS = 3;

    public function __construct(private readonly StockService $stock) {}

    /**
     * Create a completed sale from CheckoutRequest::validated().
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException on unavailable products, insufficient stock or cash
     */
    public function checkout(array $data, User $cashier): Sale
    {
        // product_id => total quantity (duplicate cart lines merged, cart order kept)
        $lines = collect($data['items'])
            ->groupBy('product_id')
            ->map(fn (Collection $g) => (int) $g->sum('quantity'));

        // The invoice number is derived from the day's last invoice; if two checkouts race
        // for the same number the unique index rejects one, and it simply tries again.
        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction(fn () => $this->createSale($lines, $data, $cashier));
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= self::MAX_ATTEMPTS) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Void a completed sale: restore the stock of every line and mark the sale void.
     *
     * @throws ValidationException when the sale is already void
     */
    public function void(Sale $sale, User $admin, string $reason): Sale
    {
        return DB::transaction(function () use ($sale, $admin, $reason) {
            $sale = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if ($sale->isVoid()) {
                throw ValidationException::withMessages(['reason' => 'Transaksi sudah dibatalkan.']);
            }

            $sale->load('items');

            $products = Product::withTrashed()
                ->whereIn('id', $sale->items->pluck('product_id')->unique())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($sale->items as $item) {
                $this->stock->applyMovement(
                    $products[$item->product_id],
                    $item->quantity,
                    StockMovementType::Void,
                    $admin,
                    sale: $sale,
                    note: "Void {$sale->invoice_number}",
                );
            }

            $sale->update([
                'status' => SaleStatus::Void,
                'voided_at' => now(),
                'voided_by' => $admin->id,
                'void_reason' => $reason,
            ]);

            return $sale;
        });
    }

    /**
     * Next invoice number for the given day: INV-YYYYMMDD-NNNN (sequence per day).
     * Must run inside the checkout transaction.
     */
    public function nextInvoiceNumber(CarbonInterface $date): string
    {
        $prefix = 'INV-'.$date->format('Ymd').'-';

        $last = Sale::where('invoice_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  Collection<int|string, int>  $lines  product_id => quantity
     * @param  array<string, mixed>  $data
     */
    private function createSale(Collection $lines, array $data, User $cashier): Sale
    {
        $products = Product::whereIn('id', $lines->keys())->lockForUpdate()->get()->keyBy('id');

        $subtotal = 0;
        foreach ($lines as $id => $qty) {
            $product = $products->get($id);

            if (! $product || ! $product->is_active) {
                throw ValidationException::withMessages(['items' => 'Produk tidak tersedia.']);
            }

            if ($product->stock < $qty) {
                throw ValidationException::withMessages([
                    'items' => "Stok {$product->name} tidak mencukupi (sisa {$product->stock} {$product->unit}).",
                ]);
            }

            $subtotal += $product->price * $qty;
        }

        // Discount (the POS screen uses the same formula for its live totals).
        $discountType = isset($data['discount_type']) ? DiscountType::from($data['discount_type']) : null;
        $discountValue = (float) ($data['discount_value'] ?? 0);
        $discountAmount = match ($discountType) {
            DiscountType::Nominal => min((int) round($discountValue), $subtotal),
            DiscountType::Percent => (int) round($subtotal * min($discountValue, 100) / 100),
            null => 0,
        };

        $taxable = $subtotal - $discountAmount;
        $taxPercent = (float) StoreSetting::current()->tax_percent;
        $taxAmount = (int) round($taxable * $taxPercent / 100);
        $total = $taxable + $taxAmount;

        $method = PaymentMethod::from($data['payment_method']);
        if ($method === PaymentMethod::Tunai) {
            $paid = (int) ($data['paid_amount'] ?? 0);

            if ($paid < $total) {
                throw ValidationException::withMessages(['paid_amount' => 'Uang diterima kurang dari total belanja.']);
            }
        } else {
            $paid = $total;
        }

        $invoice = $this->nextInvoiceNumber(now());

        $sale = Sale::create([
            'invoice_number' => $invoice,
            'user_id' => $cashier->id,
            'subtotal' => $subtotal,
            'discount_type' => $discountType,
            'discount_value' => $discountType ? $discountValue : 0,
            'discount_amount' => $discountAmount,
            'tax_percent' => $taxPercent,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'payment_method' => $method,
            'paid_amount' => $paid,
            'change_amount' => $paid - $total,
            'note' => $data['note'] ?? null,
            'status' => SaleStatus::Completed,
        ]);

        $sale->items()->createMany($lines->map(function (int $qty, $id) use ($products) {
            $product = $products[$id];

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'unit' => $product->unit,
                'price' => $product->price,
                'cost_price' => $product->cost_price,
                'quantity' => $qty,
                'subtotal' => $product->price * $qty,
            ];
        })->values()->all());

        foreach ($lines as $id => $qty) {
            $this->stock->applyMovement($products[$id], -$qty, StockMovementType::Sale, $cashier, sale: $sale, note: $invoice);
        }

        return $sale->load('items');
    }
}

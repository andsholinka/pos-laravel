<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only place that changes products.stock. Every change writes a stock_movements
 * row with the stock before and after, so the ledger always matches the product.
 */
class StockService
{
    /**
     * Apply a signed stock change to a product and log it.
     *
     * The caller MUST already be inside a DB transaction holding a lock on $locked
     * (Product::whereKey(..)->lockForUpdate()), so before/after cannot race.
     *
     * @throws ValidationException when the stock would go negative
     */
    public function applyMovement(
        Product $locked,
        int $delta,
        StockMovementType $type,
        ?User $user,
        ?Sale $sale = null,
        ?string $reason = null,
        ?string $note = null,
    ): StockMovement {
        $before = (int) $locked->stock;
        $after = $before + $delta;

        if ($after < 0) {
            throw ValidationException::withMessages([
                'quantity' => "Stok {$locked->name} tidak mencukupi (sisa {$before}).",
            ]);
        }

        $locked->forceFill(['stock' => $after])->save();

        return StockMovement::create([
            'product_id' => $locked->id,
            'user_id' => $user?->id,
            'sale_id' => $sale?->id,
            'type' => $type,
            'quantity' => $delta,
            'stock_before' => $before,
            'stock_after' => $after,
            'reason' => $reason,
            'note' => $note,
        ]);
    }

    /**
     * Receive goods: add $qty to the stock and optionally update the cost price.
     */
    public function stockIn(Product $product, int $qty, User $user, ?int $newCostPrice = null, ?string $note = null): StockMovement
    {
        return DB::transaction(function () use ($product, $qty, $user, $newCostPrice, $note) {
            $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();

            if ($newCostPrice !== null) {
                $locked->cost_price = $newCostPrice;
            }

            return $this->applyMovement($locked, $qty, StockMovementType::In, $user, note: $note);
        });
    }

    /**
     * Set the stock to the physically counted amount (stock opname, damaged goods, ...).
     *
     * @throws ValidationException when the counted stock equals the system stock
     */
    public function adjust(Product $product, int $actualStock, string $reason, User $user, ?string $note = null): StockMovement
    {
        return DB::transaction(function () use ($product, $actualStock, $reason, $user, $note) {
            $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $delta = $actualStock - (int) $locked->stock;

            if ($delta === 0) {
                throw ValidationException::withMessages([
                    'actual_stock' => 'Stok fisik sama dengan stok sistem, tidak ada yang disesuaikan.',
                ]);
            }

            return $this->applyMovement($locked, $delta, StockMovementType::Adjustment, $user, reason: $reason, note: $note);
        });
    }
}

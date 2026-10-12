<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementType;
use App\Http\Requests\StockAdjustmentRequest;
use App\Http\Requests\StockInRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function index(Request $request): View
    {
        $validTypes = array_column(StockMovementType::cases(), 'value');
        $filters = [
            'product_id' => $request->integer('product_id') ?: null,
            'type' => in_array($request->query('type'), $validTypes, true) ? $request->query('type') : null,
            'date_from' => $this->validDate($request->query('date_from')),
            'date_to' => $this->validDate($request->query('date_to')),
        ];

        $movements = StockMovement::query()
            ->with([
                'product' => fn ($q) => $q->withTrashed()->select('id', 'name', 'sku', 'unit', 'deleted_at'),
                'user:id,name',
                'sale:id,invoice_number',
            ])
            ->when($filters['product_id'], fn ($q, $id) => $q->where('product_id', $id))
            ->when($filters['type'], fn ($q, $type) => $q->where('type', $type))
            ->when($filters['date_from'], fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['date_to'], fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest()
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('stock.index', [
            'movements' => $movements,
            'filters' => $filters,
            'products' => Product::withTrashed()->orderBy('name')->get(['id', 'name', 'sku']),
            'types' => StockMovementType::cases(),
        ]);
    }

    public function createIn(Request $request): View
    {
        $selected = $request->integer('product') ?: null;

        return view('stock.create-in', [
            'products' => $this->productOptions($selected),
            'selected' => $selected,
        ]);
    }

    public function storeIn(StockInRequest $request, StockService $stock): RedirectResponse
    {
        $product = Product::findOrFail($request->validated('product_id'));
        $costPrice = $request->validated('cost_price');

        $movement = $stock->stockIn(
            $product,
            (int) $request->validated('quantity'),
            $request->user(),
            $costPrice !== null ? (int) $costPrice : null,
            $request->validated('note'),
        );

        return redirect()->route('stock.index')
            ->with('success', "Barang masuk {$product->name} +{$movement->quantity} {$product->unit} dicatat. Stok sekarang {$movement->stock_after}.");
    }

    public function createAdjustment(Request $request): View
    {
        $selected = $request->integer('product') ?: null;

        return view('stock.create-adjustment', [
            'products' => $this->productOptions($selected),
            'selected' => $selected,
            'reasons' => StockMovement::ADJUSTMENT_REASONS,
        ]);
    }

    public function storeAdjustment(StockAdjustmentRequest $request, StockService $stock): RedirectResponse
    {
        $product = Product::findOrFail($request->validated('product_id'));

        $movement = $stock->adjust(
            $product,
            (int) $request->validated('actual_stock'),
            $request->validated('reason'),
            $request->user(),
            $request->validated('note'),
        );

        $delta = ($movement->quantity > 0 ? '+' : '').$movement->quantity;

        return redirect()->route('stock.index')
            ->with('success', "Stok {$product->name} disesuaikan ({$delta}). Stok sekarang {$movement->stock_after}.");
    }

    /**
     * Active products for the stock forms, plus the preselected one even if it is inactive
     * (the <option> data-* attributes feed the live preview).
     */
    private function productOptions(?int $selected): Collection
    {
        return Product::query()
            ->where(fn ($q) => $q->where('is_active', true)->when($selected, fn ($q, $id) => $q->orWhere('id', $id)))
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'unit', 'stock', 'cost_price', 'is_active']);
    }

    private function validDate(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }
}

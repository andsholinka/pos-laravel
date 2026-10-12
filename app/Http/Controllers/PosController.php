<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PosController extends Controller
{
    /**
     * Cashier (POS) screen. All active products are sent once as JSON and filtered client-side.
     */
    public function index(): View
    {
        $products = Product::active()
            ->with('category:id,name,color')
            ->orderBy('name')
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'barcode' => $p->barcode,
                'unit' => $p->unit,
                'price' => $p->price,
                'stock' => $p->stock,
                'min_stock' => $p->min_stock,
                'category_id' => $p->category_id,
                'image_url' => $p->image_url,
                'initials' => $p->initials,
                'color_bg' => $p->category?->colorClasses()['bg'] ?? Category::COLORS['slate']['bg'],
                'color_text' => $p->category?->colorClasses()['text'] ?? Category::COLORS['slate']['text'],
            ])
            ->values();

        $categories = Category::query()
            ->whereHas('products', fn ($q) => $q->where('is_active', true))
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get()
            ->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'color_bg' => $c->colorClasses()['bg'],
                'color_text' => $c->colorClasses()['text'],
                'count' => $c->products_count,
            ])
            ->values();

        $config = [
            'products' => $products,
            'categories' => $categories,
            'taxPercent' => (float) StoreSetting::current()->tax_percent,
            'checkoutUrl' => route('pos.checkout'),
            'csrf' => csrf_token(),
            'salesIndexUrl' => route('sales.index'),
        ];

        return view('pos.index', ['config' => $config]);
    }

    /**
     * Checkout (JSON). Validation errors become a 422 JSON response automatically.
     */
    public function store(CheckoutRequest $request, CheckoutService $checkout): JsonResponse
    {
        $sale = $checkout->checkout($request->validated(), $request->user());

        $stocks = Product::whereIn('id', $sale->items->pluck('product_id'))->pluck('stock', 'id');

        return response()->json([
            'message' => "Transaksi {$sale->invoice_number} berhasil.",
            'sale' => [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'total' => $sale->total,
                'paid_amount' => $sale->paid_amount,
                'change_amount' => $sale->change_amount,
                'payment_method' => $sale->payment_method->value,
                'payment_label' => $sale->payment_method->label(),
                'created_at' => $sale->created_at->format('d/m/Y H:i'),
            ],
            'receipt_url' => route('sales.receipt', [$sale, 'print' => 1]),
            'stocks' => $stocks,
        ], 201);
    }
}

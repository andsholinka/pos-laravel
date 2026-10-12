<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\SaleItem;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public const STATUSES = [
        'aktif' => 'Aktif',
        'nonaktif' => 'Nonaktif',
        'menipis' => 'Stok Menipis',
        'habis' => 'Stok Habis',
    ];

    /** Shared (admin + kasir). Kasir does not see cost/margin columns or admin actions. */
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q')),
            'category' => $request->integer('category') ?: null,
            'status' => array_key_exists((string) $request->query('status'), self::STATUSES) ? $request->query('status') : null,
        ];

        $products = Product::query()
            ->with('category')
            ->search($filters['q'])
            ->when($filters['category'], fn ($q, $id) => $q->where('category_id', $id))
            ->when($filters['status'], fn ($q, $status) => match ($status) {
                'aktif' => $q->where('is_active', true),
                'nonaktif' => $q->where('is_active', false),
                // Same definition as the dashboard "Stok Menipis" card (includes out of stock).
                'menipis' => $q->lowStock(),
                'habis' => $q->where('stock', '<=', 0),
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'filters' => $filters,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'statuses' => self::STATUSES,
        ]);
    }

    /** Shared (admin + kasir). */
    public function show(Product $product): View
    {
        $product->load('category');

        $movements = $product->stockMovements()
            ->with(['user:id,name', 'sale:id,user_id,invoice_number'])
            ->latest()
            ->latest('id')
            ->paginate(10);

        $sold30 = (int) SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sale_items.product_id', $product->id)
            ->where('sales.status', SaleStatus::Completed->value)
            ->where('sales.created_at', '>=', now()->subDays(30))
            ->sum('sale_items.quantity');

        return view('products.show', compact('product', 'movements', 'sold30'));
    }

    public function create(): View
    {
        return view('products.create', [
            'product' => new Product(['unit' => 'pcs', 'min_stock' => 5, 'is_active' => true]),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** Creates the product and logs the initial stock as a stock-in, in one transaction. */
    public function store(ProductRequest $request, StockService $stock): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image', 'initial_stock']);
        $initialStock = (int) $request->validated('initial_stock');
        $imagePath = $request->hasFile('image') ? $request->file('image')->store('products', 'public') : null;

        try {
            $product = DB::transaction(function () use ($data, $imagePath, $initialStock, $stock, $request) {
                $product = Product::create([...$data, 'image_path' => $imagePath]);

                if ($initialStock > 0) {
                    $stock->stockIn($product, $initialStock, $request->user(), note: 'Stok awal');
                }

                return $product;
            });
        } catch (\Throwable $e) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            throw $e;
        }

        return redirect()->route('products.show', $product)
            ->with('success', "Produk {$product->name} berhasil ditambahkan.");
    }

    public function edit(Product $product): View
    {
        return view('products.edit', [
            'product' => $product,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** Stock is never changed here (it goes through Barang Masuk / Penyesuaian). */
    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image', 'initial_stock']);
        $oldImage = $product->image_path;

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        } elseif ($request->boolean('remove_image')) {
            $data['image_path'] = null;
        }

        $product->update($data);

        if ($oldImage && $oldImage !== $product->image_path) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('products.show', $product)
            ->with('success', "Produk {$product->name} berhasil diperbarui.");
    }

    /** Soft delete: sales and stock history keep pointing at the product. */
    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', "Produk {$product->name} berhasil dihapus.");
    }
}

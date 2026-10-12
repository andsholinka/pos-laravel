<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        $categories = Category::query()
            ->withCount('products')
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('categories.index', compact('categories', 'q'));
    }

    public function create(): View
    {
        return view('categories.create', ['category' => new Category(['color' => 'emerald'])]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = Category::create($request->validated());

        return redirect()->route('categories.index')
            ->with('success', "Kategori {$category->name} berhasil ditambahkan.");
    }

    public function edit(Category $category): View
    {
        return view('categories.edit', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('categories.index')
            ->with('success', "Kategori {$category->name} berhasil diperbarui.");
    }

    /** Blocked while the category still has products (including soft-deleted ones, FK restrict). */
    public function destroy(Category $category): RedirectResponse
    {
        $count = $category->products()->withTrashed()->count();

        if ($count > 0) {
            return back()->with('error', "Kategori masih memiliki {$count} produk.");
        }

        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', "Kategori {$category->name} berhasil dihapus.");
    }
}

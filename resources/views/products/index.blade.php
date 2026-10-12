@php($isAdmin = auth()->user()->can('admin'))
<x-app-layout title="Produk" subtitle="{{ $isAdmin ? 'Kelola daftar produk, harga, dan stok.' : 'Daftar produk, harga, dan ketersediaan stok.' }}">
    @if ($isAdmin)
        <x-slot name="actions">
            <a href="{{ route('stock.in.create') }}" class="btn btn-secondary">
                <x-icon name="arrow-down-tray" class="h-4 w-4" />
                Barang Masuk
            </a>
            <a href="{{ route('products.create') }}" class="btn btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                Tambah Produk
            </a>
        </x-slot>
    @endif

    <x-filter-bar :action="route('products.index')">
        <div class="sm:col-span-2">
            <x-input-label for="q" value="Cari produk" />
            <x-text-input id="q" name="q" type="search" :value="$filters['q']" placeholder="Nama, SKU, atau barcode" />
        </div>
        <div>
            <x-input-label for="category" value="Kategori" />
            <x-select-input id="category" name="category">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected($filters['category'] === $category->id)>{{ $category->name }}</option>
                @endforeach
            </x-select-input>
        </div>
        <div>
            <x-input-label for="status" value="Status" />
            <x-select-input id="status" name="status">
                <option value="">Semua status</option>
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
                @endforeach
            </x-select-input>
        </div>
    </x-filter-bar>

    <x-card :padding="false">
        @if ($products->isEmpty())
            @php($filtered = $filters['q'] !== '' || $filters['category'] || $filters['status'])
            <x-empty-state icon="cube"
                           title="{{ $filtered ? 'Produk tidak ditemukan' : 'Belum ada produk' }}"
                           description="{{ $filtered ? 'Coba ubah kata kunci atau filter.' : 'Tambahkan produk pertama agar bisa dijual di kasir.' }}">
                @if ($isAdmin && ! $filtered)
                    <x-slot name="action">
                        <a href="{{ route('products.create') }}" class="btn btn-primary">
                            <x-icon name="plus" class="h-4 w-4" />
                            Tambah Produk
                        </a>
                    </x-slot>
                @endif
            </x-empty-state>
        @else
            {{-- Desktop table --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th scope="col">Produk</th>
                            <th scope="col">Kategori</th>
                            @if ($isAdmin)
                                <th scope="col" class="text-right">Harga Beli</th>
                            @endif
                            <th scope="col" class="text-right">Harga Jual</th>
                            @if ($isAdmin)
                                <th scope="col" class="text-right">Margin</th>
                            @endif
                            <th scope="col">Stok</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-right"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            @php($c = $product->category->colorClasses())
                            <tr>
                                <td>
                                    <a href="{{ route('products.show', $product) }}" class="flex items-center gap-3 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                                        <x-product-thumb :product="$product" size="sm" />
                                        <span class="min-w-0">
                                            <span class="block max-w-xs truncate font-semibold text-slate-900 hover:text-primary-700">{{ $product->name }}</span>
                                            <span class="block text-xs text-slate-500">{{ $product->sku }}</span>
                                        </span>
                                    </a>
                                </td>
                                <td>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $c['bg'] }} {{ $c['text'] }} {{ $c['ring'] }}">{{ $product->category->name }}</span>
                                </td>
                                @if ($isAdmin)
                                    <td class="text-right tabular-nums text-slate-600">{{ rupiah($product->cost_price) }}</td>
                                @endif
                                <td class="text-right font-semibold tabular-nums text-slate-900">{{ rupiah($product->price) }}</td>
                                @if ($isAdmin)
                                    <td class="text-right tabular-nums">
                                        <span class="block text-slate-700">{{ rupiah($product->price - $product->cost_price) }}</span>
                                        <span class="block text-xs text-slate-500">{{ $product->price > 0 ? percent(($product->price - $product->cost_price) / $product->price * 100) : '-' }}</span>
                                    </td>
                                @endif
                                <td>@include('products._stock-badge')</td>
                                <td>
                                    @if ($product->is_active)
                                        <x-badge color="green">Aktif</x-badge>
                                    @else
                                        <x-badge color="slate">Nonaktif</x-badge>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex justify-end gap-1">
                                        <a href="{{ route('products.show', $product) }}" class="btn btn-ghost btn-icon" title="Detail">
                                            <x-icon name="eye" class="h-5 w-5" />
                                            <span class="sr-only">Detail {{ $product->name }}</span>
                                        </a>
                                        @if ($isAdmin)
                                            <a href="{{ route('products.edit', $product) }}" class="btn btn-ghost btn-icon" title="Edit">
                                                <x-icon name="pencil-square" class="h-5 w-5" />
                                                <span class="sr-only">Edit {{ $product->name }}</span>
                                            </a>
                                            <x-confirm-delete :action="route('products.destroy', $product)"
                                                              icon-only
                                                              title="Hapus produk {{ $product->name }}?"
                                                              message="Produk tidak lagi tampil di kasir. Riwayat penjualan dan stok tetap tersimpan."
                                                              label="Hapus {{ $product->name }}" />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile cards --}}
            <ul class="divide-y divide-slate-100 md:hidden">
                @foreach ($products as $product)
                    <li>
                        <a href="{{ route('products.show', $product) }}" class="flex items-center gap-3 p-4 hover:bg-slate-50">
                            <x-product-thumb :product="$product" size="md" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-slate-900">{{ $product->name }}</p>
                                <p class="text-xs text-slate-500">{{ $product->sku }} · {{ $product->category->name }}</p>
                                <div class="mt-1 flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-bold text-slate-900">{{ rupiah($product->price) }}</span>
                                    @include('products._stock-badge')
                                    @unless ($product->is_active)
                                        <x-badge color="slate">Nonaktif</x-badge>
                                    @endunless
                                </div>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="flex flex-col gap-2 border-t border-slate-100 px-4 py-3">
                @if ($products->hasPages())
                    {{ $products->links() }}
                @else
                    <p class="text-sm text-slate-600">Menampilkan {{ $products->total() }} produk</p>
                @endif
            </div>
        @endif
    </x-card>
</x-app-layout>

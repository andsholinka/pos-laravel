<x-app-layout title="Kategori" subtitle="Kelompokkan produk agar mudah dicari di kasir.">
    <x-slot name="actions">
        <a href="{{ route('categories.create') }}" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" />
            Tambah Kategori
        </a>
    </x-slot>

    <x-filter-bar :action="route('categories.index')">
        <div class="sm:col-span-2">
            <x-input-label for="q" value="Cari kategori" />
            <x-text-input id="q" name="q" type="search" :value="$q" placeholder="Nama kategori" />
        </div>
    </x-filter-bar>

    <x-card :padding="false">
        @if ($categories->isEmpty())
            <x-empty-state icon="tag"
                           title="{{ $q !== '' ? 'Kategori tidak ditemukan' : 'Belum ada kategori' }}"
                           description="{{ $q !== '' ? 'Coba kata kunci lain atau reset pencarian.' : 'Tambahkan kategori pertama untuk mulai mengelompokkan produk.' }}">
                <x-slot name="action">
                    <a href="{{ route('categories.create') }}" class="btn btn-primary">
                        <x-icon name="plus" class="h-4 w-4" />
                        Tambah Kategori
                    </a>
                </x-slot>
            </x-empty-state>
        @else
            {{-- Desktop table --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th scope="col">Kategori</th>
                            <th scope="col">Deskripsi</th>
                            <th scope="col" class="text-right">Jumlah Produk</th>
                            <th scope="col" class="text-right"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            @php($c = $category->colorClasses())
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 items-center justify-center rounded-lg {{ $c['bg'] }} {{ $c['text'] }}" aria-hidden="true">
                                            <x-icon name="tag" class="h-5 w-5" />
                                        </span>
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ $category->name }}</p>
                                            <p class="text-xs text-slate-500">{{ $category->slug }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="max-w-sm truncate text-slate-600">{{ $category->description ?: '-' }}</td>
                                <td class="text-right tabular-nums">
                                    <a href="{{ route('products.index', ['category' => $category->id]) }}" class="font-semibold text-primary-700 hover:underline">
                                        {{ $category->products_count }} produk
                                    </a>
                                </td>
                                <td>
                                    <div class="flex justify-end gap-1">
                                        <a href="{{ route('categories.edit', $category) }}" class="btn btn-ghost btn-icon" title="Edit">
                                            <x-icon name="pencil-square" class="h-5 w-5" />
                                            <span class="sr-only">Edit {{ $category->name }}</span>
                                        </a>
                                        @if ($category->products_count === 0)
                                            <x-confirm-delete :action="route('categories.destroy', $category)"
                                                              icon-only
                                                              title="Hapus kategori {{ $category->name }}?"
                                                              message="Kategori yang dihapus tidak dapat dikembalikan."
                                                              label="Hapus {{ $category->name }}" />
                                        @else
                                            <span class="btn btn-ghost btn-icon cursor-not-allowed text-slate-300" title="Tidak bisa dihapus: masih memiliki produk">
                                                <x-icon name="trash" class="h-5 w-5" />
                                                <span class="sr-only">Tidak bisa dihapus, masih memiliki produk</span>
                                            </span>
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
                @foreach ($categories as $category)
                    @php($c = $category->colorClasses())
                    <li class="flex items-center gap-3 p-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $c['bg'] }} {{ $c['text'] }}" aria-hidden="true">
                            <x-icon name="tag" class="h-5 w-5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-slate-900">{{ $category->name }}</p>
                            <p class="text-xs text-slate-500">{{ $category->products_count }} produk</p>
                        </div>
                        <a href="{{ route('categories.edit', $category) }}" class="btn btn-secondary btn-sm">Edit</a>
                    </li>
                @endforeach
            </ul>

            @if ($categories->hasPages())
                <div class="border-t border-slate-100 px-4 py-3">{{ $categories->links() }}</div>
            @endif
        @endif
    </x-card>
</x-app-layout>

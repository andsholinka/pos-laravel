<x-app-layout title="Stok" subtitle="Riwayat semua pergerakan stok: barang masuk, penjualan, penyesuaian, dan pembatalan.">
    <x-slot name="actions">
        <a href="{{ route('stock.adjustment.create') }}" class="btn btn-secondary">
            <x-icon name="adjustments-horizontal" class="h-4 w-4" />
            Penyesuaian
        </a>
        <a href="{{ route('stock.in.create') }}" class="btn btn-primary">
            <x-icon name="arrow-down-tray" class="h-4 w-4" />
            Barang Masuk
        </a>
    </x-slot>

    <x-filter-bar :action="route('stock.index')">
        <div>
            <x-input-label for="product_id" value="Produk" />
            <x-select-input id="product_id" name="product_id">
                <option value="">Semua produk</option>
                @foreach ($products as $p)
                    <option value="{{ $p->id }}" @selected($filters['product_id'] === $p->id)>{{ $p->name }} ({{ $p->sku }})</option>
                @endforeach
            </x-select-input>
        </div>
        <div>
            <x-input-label for="type" value="Jenis" />
            <x-select-input id="type" name="type">
                <option value="">Semua jenis</option>
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected($filters['type'] === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </x-select-input>
        </div>
        <div>
            <x-input-label for="date_from" value="Dari tanggal" />
            <x-text-input id="date_from" name="date_from" type="date" :value="$filters['date_from']" />
        </div>
        <div>
            <x-input-label for="date_to" value="Sampai tanggal" />
            <x-text-input id="date_to" name="date_to" type="date" :value="$filters['date_to']" />
        </div>
    </x-filter-bar>

    <x-card :padding="false">
        @if ($movements->isEmpty())
            <x-empty-state icon="archive-box" title="Belum ada pergerakan stok" description="Tidak ada data untuk filter ini. Coba ubah filter atau catat barang masuk." />
        @else
            {{-- Desktop table --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th scope="col">Waktu</th>
                            <th scope="col">Produk</th>
                            <th scope="col">Jenis</th>
                            <th scope="col" class="text-right">Jumlah</th>
                            <th scope="col" class="text-right">Stok</th>
                            <th scope="col">Keterangan</th>
                            <th scope="col">Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($movements as $m)
                            <tr>
                                <td class="whitespace-nowrap text-slate-600">{{ $m->created_at->translatedFormat('d M Y H:i') }}</td>
                                <td>
                                    @if ($m->product)
                                        @if ($m->product->trashed())
                                            <span class="font-semibold text-slate-900">{{ $m->product->name }}</span>
                                            <span class="block text-xs text-slate-500">{{ $m->product->sku }} · dihapus</span>
                                        @else
                                            <a href="{{ route('products.show', $m->product) }}" class="font-semibold text-slate-900 hover:text-primary-700">{{ $m->product->name }}</a>
                                            <span class="block text-xs text-slate-500">{{ $m->product->sku }}</span>
                                        @endif
                                    @else
                                        <span class="text-slate-500">-</span>
                                    @endif
                                </td>
                                <td>
                                    <x-badge :color="$m->type->color()">
                                        <x-icon :name="$m->type->icon()" class="h-3.5 w-3.5" />
                                        {{ $m->type->label() }}
                                    </x-badge>
                                </td>
                                <td @class(['whitespace-nowrap text-right font-semibold tabular-nums', 'text-emerald-700' => $m->quantity > 0, 'text-rose-700' => $m->quantity < 0])>
                                    {{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }} <span class="text-xs font-normal text-slate-500">{{ $m->product?->unit }}</span>
                                </td>
                                <td class="whitespace-nowrap text-right tabular-nums text-slate-600">{{ $m->stock_before }} → <span class="font-semibold text-slate-900">{{ $m->stock_after }}</span></td>
                                <td class="max-w-xs text-slate-600">
                                    @if ($m->sale)
                                        <a href="{{ route('sales.show', $m->sale) }}" class="font-medium text-slate-800 hover:text-primary-700">{{ $m->sale->invoice_number }}</a>
                                    @endif
                                    @if ($m->reasonLabel())
                                        <span class="font-medium text-slate-800">{{ $m->reasonLabel() }}</span>
                                    @endif
                                    @if ($m->note && (! $m->sale || $m->note !== $m->sale->invoice_number))
                                        <span class="block truncate">{{ $m->note }}</span>
                                    @endif
                                    @if (! $m->sale && ! $m->reasonLabel() && ! $m->note)
                                        -
                                    @endif
                                </td>
                                <td class="whitespace-nowrap text-slate-600">{{ $m->user?->name ?? 'Sistem' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile cards --}}
            <ul class="divide-y divide-slate-100 md:hidden">
                @foreach ($movements as $m)
                    <li class="flex items-start justify-between gap-3 p-4">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-900">{{ $m->product?->name ?? '-' }}</p>
                            <p class="text-xs text-slate-500">{{ $m->created_at->translatedFormat('d M Y H:i') }} · {{ $m->user?->name ?? 'Sistem' }}</p>
                            <x-badge :color="$m->type->color()" class="mt-1">{{ $m->type->label() }}</x-badge>
                        </div>
                        <div class="text-right">
                            <p @class(['font-bold tabular-nums', 'text-emerald-700' => $m->quantity > 0, 'text-rose-700' => $m->quantity < 0])>{{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}</p>
                            <p class="text-xs tabular-nums text-slate-500">{{ $m->stock_before }} → {{ $m->stock_after }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="border-t border-slate-100 px-4 py-3">
                @if ($movements->hasPages())
                    {{ $movements->links() }}
                @else
                    <p class="text-sm text-slate-600">Menampilkan {{ $movements->total() }} pergerakan</p>
                @endif
            </div>
        @endif
    </x-card>
</x-app-layout>

@php
    $isAdmin = auth()->user()->can('admin');
    $c = $product->category->colorClasses();
    $margin = $product->price - $product->cost_price;
@endphp
<x-app-layout title="{{ $product->name }}" subtitle="Detail produk dan riwayat pergerakan stok.">
    <x-slot name="actions">
        <a href="{{ route('products.index') }}" class="btn btn-secondary">
            <x-icon name="arrow-left" class="h-4 w-4" />
            Kembali
        </a>
        @if ($isAdmin)
            <a href="{{ route('products.edit', $product) }}" class="btn btn-primary">
                <x-icon name="pencil-square" class="h-4 w-4" />
                Edit Produk
            </a>
        @endif
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Details --}}
        <x-card class="lg:col-span-2">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
                <x-product-thumb :product="$product" size="lg" />
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $c['bg'] }} {{ $c['text'] }} {{ $c['ring'] }}">{{ $product->category->name }}</span>
                        @if ($product->is_active)
                            <x-badge color="green">Aktif</x-badge>
                        @else
                            <x-badge color="slate">Nonaktif</x-badge>
                        @endif
                        @include('products._stock-badge')
                    </div>
                    <h2 class="mt-2 text-xl font-bold text-slate-900">{{ $product->name }}</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-slate-500">SKU</dt>
                            <dd class="font-medium text-slate-900">{{ $product->sku }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Barcode</dt>
                            <dd class="font-medium text-slate-900">{{ $product->barcode ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Satuan</dt>
                            <dd class="font-medium text-slate-900">{{ $product->unit }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Stok Minimum</dt>
                            <dd class="font-medium text-slate-900">{{ $product->min_stock }} {{ $product->unit }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Harga Jual</dt>
                            <dd class="text-base font-bold text-slate-900">{{ rupiah($product->price) }}</dd>
                        </div>
                        @if ($isAdmin)
                            <div>
                                <dt class="text-slate-500">Harga Beli / Margin</dt>
                                <dd class="font-medium text-slate-900">
                                    {{ rupiah($product->cost_price) }}
                                    <span class="text-slate-500">· {{ rupiah($margin) }} ({{ $product->price > 0 ? percent($margin / $product->price * 100) : '-' }})</span>
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>
        </x-card>

        {{-- Stock summary --}}
        <div class="space-y-6">
            <x-stat-card label="Stok Saat Ini" value="{{ $product->stock }} {{ $product->unit }}" icon="archive-box"
                         :color="$product->stock <= 0 ? 'rose' : ($product->is_low_stock ? 'amber' : 'emerald')"
                         hint="Minimum {{ $product->min_stock }} {{ $product->unit }}" />
            <x-stat-card label="Terjual 30 Hari" value="{{ $sold30 }} {{ $product->unit }}" icon="arrow-trending-up" color="sky"
                         hint="Dari transaksi yang selesai" />
            @if ($isAdmin)
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('stock.in.create', ['product' => $product->id]) }}" class="btn btn-secondary">
                        <x-icon name="arrow-down-tray" class="h-4 w-4" />
                        Barang Masuk
                    </a>
                    <a href="{{ route('stock.adjustment.create', ['product' => $product->id]) }}" class="btn btn-secondary">
                        <x-icon name="adjustments-horizontal" class="h-4 w-4" />
                        Penyesuaian
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Stock history --}}
    <x-card title="Riwayat Stok" subtitle="Semua perubahan stok produk ini, terbaru di atas." :padding="false" class="mt-6">
        @if ($movements->isEmpty())
            <x-empty-state icon="archive-box" title="Belum ada pergerakan stok" description="Barang masuk, penjualan, dan penyesuaian akan tercatat di sini." />
        @else
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th scope="col">Waktu</th>
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
                                <td><x-badge :color="$m->type->color()">{{ $m->type->label() }}</x-badge></td>
                                <td @class(['text-right font-semibold tabular-nums', 'text-emerald-700' => $m->quantity > 0, 'text-rose-700' => $m->quantity < 0])>
                                    {{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}
                                </td>
                                <td class="whitespace-nowrap text-right tabular-nums text-slate-600">{{ $m->stock_before }} → <span class="font-semibold text-slate-900">{{ $m->stock_after }}</span></td>
                                <td class="max-w-xs text-slate-600">
                                    @if ($m->sale)
                                        <span class="font-medium text-slate-800">{{ $m->sale->invoice_number }}</span>
                                    @endif
                                    @if ($m->reasonLabel())
                                        <span class="font-medium text-slate-800">{{ $m->reasonLabel() }}</span>
                                    @endif
                                    @if ($m->note && (! $m->sale || $m->note !== $m->sale->invoice_number))
                                        <span class="block truncate">{{ $m->note }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap text-slate-600">{{ $m->user?->name ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($movements->hasPages())
                <div class="border-t border-slate-100 px-4 py-3">{{ $movements->links() }}</div>
            @endif
        @endif
    </x-card>
</x-app-layout>

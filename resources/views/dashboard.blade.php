@php
    $paymentColors = [
        'tunai' => ['hex' => '#10b981', 'dot' => 'bg-emerald-500'],
        'qris' => ['hex' => '#8b5cf6', 'dot' => 'bg-violet-500'],
        'transfer' => ['hex' => '#0ea5e9', 'dot' => 'bg-sky-500'],
    ];
    $paymentTotal = array_sum(array_column($payments, 'total'));
    $maxQty = max(1, (int) $topProducts->max('qty'));
@endphp

<x-app-layout title="Dashboard" subtitle="Selamat datang kembali, {{ auth()->user()->name }}. Berikut ringkasan toko hari ini.">
    <x-slot name="actions">
        @if (Route::has('reports.index'))
            <a href="{{ route('reports.index') }}" class="btn btn-secondary">
                <x-icon name="document-text" class="h-4 w-4" />
                Lihat Laporan
            </a>
        @endif
    </x-slot>

    {{-- Stat cards --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Penjualan Hari Ini" :value="rupiah($today['omzet'])" icon="banknotes" color="emerald"
                     :trend="$trend" :hint="$trend !== null ? 'dibanding kemarin' : 'Belum ada data kemarin'" />
        <x-stat-card label="Transaksi Hari Ini" :value="number_format($today['transactions'], 0, ',', '.')" icon="receipt-percent" color="sky"
                     :hint="number_format($today['items_sold'], 0, ',', '.').' item terjual'" />
        <x-stat-card label="Stok Menipis" :value="number_format($lowStockCount, 0, ',', '.').' produk'" icon="exclamation-triangle" color="amber"
                     hint="Lihat produk yang perlu diisi ulang" :href="route('products.index', ['status' => 'menipis'])" />
        <x-stat-card label="Total Produk" :value="number_format($productCount, 0, ',', '.')" icon="cube" color="rose"
                     :hint="'Aktif di '.$categoryCount.' kategori'" :href="route('products.index')" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        {{-- Sales chart --}}
        <x-card title="Grafik Penjualan" subtitle="Omzet dan jumlah transaksi harian" class="xl:col-span-2"
                x-data="salesChart({{ Js::from($chart) }})">
            <x-slot name="actions">
                <div class="inline-flex rounded-lg bg-slate-100 p-1" role="group" aria-label="Rentang grafik">
                    <template x-for="n in ranges" :key="n">
                        <button type="button"
                                class="rounded-md px-3 py-1 text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                                :class="range === n ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                                :aria-pressed="(range === n).toString()"
                                @click="setRange(n)"
                                x-text="n + ' Hari'"></button>
                    </template>
                </div>
            </x-slot>

            <div class="relative h-72">
                <canvas x-ref="canvas" role="img"
                        aria-label="Grafik omzet dan transaksi harian. Total omzet 30 hari {{ rupiah($series->sum('omzet')) }} dari {{ number_format($series->sum('transactions'), 0, ',', '.') }} transaksi."></canvas>
            </div>
        </x-card>

        {{-- Payment methods --}}
        <x-card title="Metode Pembayaran" subtitle="30 hari terakhir">
            @if ($paymentTotal > 0)
                <div class="relative mx-auto h-48 w-48"
                     x-data="doughnutChart({{ Js::from([
                         'labels' => array_column($payments, 'label'),
                         'data' => array_column($payments, 'total'),
                         'colors' => array_values(array_map(fn ($c) => $c['hex'], $paymentColors)),
                     ]) }})">
                    <canvas x-ref="canvas" role="img" aria-label="Diagram komposisi metode pembayaran"></canvas>
                </div>
                <ul class="mt-5 space-y-3">
                    @foreach ($payments as $method => $row)
                        <li class="flex items-center justify-between gap-3 text-sm">
                            <span class="flex items-center gap-2 text-slate-700">
                                <span class="h-2.5 w-2.5 rounded-full {{ $paymentColors[$method]['dot'] }}" aria-hidden="true"></span>
                                {{ $row['label'] }}
                                <span class="text-xs text-slate-500">({{ number_format($row['count'], 0, ',', '.') }} trx)</span>
                            </span>
                            <span class="text-right">
                                <span class="font-semibold tabular-nums text-slate-900">{{ rupiah($row['total']) }}</span>
                                <span class="ml-1 text-xs text-slate-500">{{ percent($row['total'] / $paymentTotal * 100) }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            @else
                <x-empty-state icon="banknotes" title="Belum ada pembayaran" description="Belum ada transaksi selesai dalam 30 hari terakhir." />
            @endif
        </x-card>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- Top products --}}
        <x-card title="Produk Terlaris" subtitle="Berdasarkan jumlah terjual, 30 hari terakhir">
            @if ($topProducts->isEmpty())
                <x-empty-state icon="chart-bar" title="Belum ada penjualan" description="Produk terlaris akan tampil setelah ada transaksi." />
            @else
                <ol class="space-y-4">
                    @foreach ($topProducts as $i => $row)
                        <li>
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="flex min-w-0 items-center gap-3">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-xs font-bold text-primary-700">{{ $i + 1 }}</span>
                                    <span class="truncate font-medium text-slate-900">{{ $row->product_name }}</span>
                                </span>
                                <span class="shrink-0 text-right">
                                    <span class="font-semibold tabular-nums text-slate-900">{{ number_format($row->qty, 0, ',', '.') }}</span>
                                    <span class="text-xs text-slate-500">terjual</span>
                                </span>
                            </div>
                            <div class="mt-2 flex items-center gap-3">
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100"
                                     role="progressbar" aria-label="Porsi penjualan {{ $row->product_name }}"
                                     aria-valuemin="0" aria-valuemax="{{ $maxQty }}" aria-valuenow="{{ $row->qty }}">
                                    <div class="h-full rounded-full bg-primary-500" style="width: {{ round($row->qty / $maxQty * 100, 1) }}%"></div>
                                </div>
                                <span class="w-28 shrink-0 text-right text-xs tabular-nums text-slate-600">{{ rupiah($row->revenue) }}</span>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </x-card>

        {{-- Low stock --}}
        <x-card title="Stok Menipis" subtitle="Produk aktif pada atau di bawah stok minimum" :padding="false">
            <x-slot name="actions">
                <a href="{{ route('products.index', ['status' => 'menipis']) }}" class="btn btn-ghost btn-sm">Lihat semua</a>
            </x-slot>

            @if ($lowStock->isEmpty())
                <x-empty-state icon="check-circle" title="Stok aman" description="Semua produk aktif berada di atas stok minimum." />
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($lowStock as $product)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <x-product-thumb :product="$product" size="sm" />
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('products.show', $product) }}" class="block truncate text-sm font-medium text-slate-900 hover:text-primary-700">{{ $product->name }}</a>
                                <p class="text-xs text-slate-500">{{ $product->category?->name }} · Min. {{ $product->min_stock }} {{ $product->unit }}</p>
                            </div>
                            @include('products._stock-badge')
                            <a href="{{ route('stock.in.create', ['product' => $product->id]) }}" class="btn btn-secondary btn-sm">
                                <x-icon name="plus" class="h-3.5 w-3.5" />
                                Stok Masuk
                                <span class="sr-only">untuk {{ $product->name }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>

    {{-- Recent transactions --}}
    <x-card title="Transaksi Terbaru" class="mt-6" :padding="false">
        <x-slot name="actions">
            <a href="{{ route('sales.index') }}" class="btn btn-ghost btn-sm">Lihat semua</a>
        </x-slot>

        @if ($recentSales->isEmpty())
            <x-empty-state icon="receipt-percent" title="Belum ada transaksi" description="Transaksi dari layar kasir akan tampil di sini." />
        @else
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th scope="col">No. Invoice</th>
                            <th scope="col">Waktu</th>
                            <th scope="col">Kasir</th>
                            <th scope="col">Metode</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentSales as $sale)
                            <tr>
                                <td class="whitespace-nowrap">
                                    <a href="{{ route('sales.show', $sale) }}" class="font-mono font-semibold text-slate-900 hover:text-primary-700">{{ $sale->invoice_number }}</a>
                                </td>
                                <td class="whitespace-nowrap text-slate-600">{{ $sale->created_at->translatedFormat('d M Y H:i') }}</td>
                                <td class="whitespace-nowrap text-slate-600">{{ $sale->user?->name }}</td>
                                <td><x-badge :color="$sale->payment_method->color()">{{ $sale->payment_method->label() }}</x-badge></td>
                                <td><x-badge :color="$sale->status->color()">{{ $sale->status->label() }}</x-badge></td>
                                <td @class(['whitespace-nowrap text-right font-semibold tabular-nums', 'text-slate-900' => ! $sale->isVoid(), 'text-slate-500 line-through' => $sale->isVoid()])>{{ rupiah($sale->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-app-layout>

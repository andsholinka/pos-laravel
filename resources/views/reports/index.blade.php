@php
    $s = $summary;
    $isMonthly = $group === 'monthly';
    $exportQuery = request()->only(['from', 'to', 'group']);
    $period = $from->translatedFormat('d M Y').($from->isSameDay($to) ? '' : ' – '.$to->translatedFormat('d M Y'));
    $hasData = $s['transactions'] > 0;
    $paymentTotal = array_sum(array_column($payments, 'total'));
    $paymentBars = ['tunai' => 'bg-emerald-500', 'qris' => 'bg-violet-500', 'transfer' => 'bg-sky-500'];
    $num = fn ($n) => number_format($n, 0, ',', '.');
@endphp

<x-app-layout title="Laporan Penjualan" :subtitle="'Periode '.$period.' · '.($isMonthly ? 'per bulan' : 'per hari')">
    <x-slot name="actions">
        <a href="{{ route('reports.pdf', $exportQuery) }}" class="btn btn-secondary">
            <x-icon name="arrow-down-tray" class="h-4 w-4" />
            Export PDF
        </a>
        <a href="{{ route('reports.csv', $exportQuery) }}" class="btn btn-secondary">
            <x-icon name="arrow-down-tray" class="h-4 w-4" />
            Export CSV
        </a>
    </x-slot>

    <x-filter-bar :action="route('reports.index')" class="mb-3">
        <div>
            <x-input-label for="from" value="Dari" />
            <x-text-input id="from" name="from" type="date" :value="old('from', $from->format('Y-m-d'))" />
            <x-input-error :messages="$errors->get('from')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="to" value="Sampai" />
            <x-text-input id="to" name="to" type="date" :value="old('to', $to->format('Y-m-d'))" />
            <x-input-error :messages="$errors->get('to')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="group" value="Kelompokkan" />
            <x-select-input id="group" name="group">
                <option value="">Otomatis</option>
                {{-- old(): after a failed filter (redirected here without a query) keep what was submitted. --}}
                <option value="daily" @selected(old('group', request('group')) === 'daily')>Harian</option>
                <option value="monthly" @selected(old('group', request('group')) === 'monthly')>Bulanan</option>
            </x-select-input>
            <x-input-error :messages="$errors->get('group')" class="mt-1" />
        </div>
    </x-filter-bar>

    <nav class="mb-6 flex flex-wrap items-center gap-2" aria-label="Rentang cepat">
        <span class="text-xs font-medium text-slate-500">Rentang cepat:</span>
        @foreach ($quickRanges as $range)
            <a href="{{ $range['url'] }}"
               @if ($range['active']) aria-current="page" @endif
               @class([
                   'rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500',
                   'bg-primary-600 text-white ring-primary-600' => $range['active'],
                   'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50' => ! $range['active'],
               ])>{{ $range['label'] }}</a>
        @endforeach
    </nav>

    @if (! $hasData)
        <x-card>
            <x-empty-state icon="chart-bar" title="Tidak ada penjualan pada periode ini"
                           description="Belum ada transaksi selesai antara {{ $period }}. Coba pilih rentang tanggal lain." />
        </x-card>
    @else
        {{-- Summary cards --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <x-stat-card label="Omzet" :value="rupiah($s['omzet'])" icon="banknotes" color="emerald"
                         hint="Total dibayar pelanggan (termasuk pajak)" />
            <x-stat-card label="Penjualan Bersih" :value="rupiah($s['net_sales'])" icon="receipt-percent" color="sky"
                         :hint="'Setelah diskon '.rupiah($s['discount'])" />
            <x-stat-card label="HPP" :value="rupiah($s['cogs'])" icon="archive-box" color="amber"
                         hint="Harga pokok barang terjual" />
            <x-stat-card label="Laba Kotor" :value="rupiah($s['gross_profit'])" icon="arrow-trending-up" color="emerald"
                         :hint="'Margin '.percent($s['margin'])" />
            <x-stat-card label="Jumlah Transaksi" :value="$num($s['transactions'])" icon="shopping-cart" color="sky"
                         :hint="$num($s['items_sold']).' item terjual'" />
            <x-stat-card label="Rata-rata / Transaksi" :value="rupiah($s['avg_ticket'])" icon="chart-bar" color="rose"
                         hint="Omzet dibagi jumlah transaksi" />
        </div>

        {{-- Chart --}}
        <x-card title="Omzet vs Laba Kotor" :subtitle="$isMonthly ? 'Per bulan' : 'Per hari'" class="mt-6"
                x-data="salesChart({{ Js::from($chart) }})">
            <div class="relative h-72">
                <canvas x-ref="canvas" role="img"
                        aria-label="Grafik omzet dan laba kotor {{ $isMonthly ? 'per bulan' : 'per hari' }}. Rincian angka ada pada tabel di bawah."></canvas>
            </div>
        </x-card>

        {{-- Period table --}}
        <x-card title="Rincian {{ $isMonthly ? 'per Bulan' : 'per Hari' }}" class="mt-6" :padding="false">
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th scope="col">{{ $isMonthly ? 'Bulan' : 'Tanggal' }}</th>
                            <th scope="col" class="!text-right">Transaksi</th>
                            <th scope="col" class="!text-right">Penjualan Bersih</th>
                            <th scope="col" class="!text-right">Diskon</th>
                            <th scope="col" class="!text-right">Pajak</th>
                            <th scope="col" class="!text-right">Omzet</th>
                            <th scope="col" class="!text-right">HPP</th>
                            <th scope="col" class="!text-right">Laba Kotor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td class="whitespace-nowrap font-medium text-slate-900">
                                    {{ $isMonthly ? $row['label'] : \Carbon\CarbonImmutable::parse($row['date'])->translatedFormat('D, d M Y') }}
                                </td>
                                <td class="text-right tabular-nums">{{ $num($row['transactions']) }}</td>
                                <td class="whitespace-nowrap text-right tabular-nums">{{ rupiah($row['net_sales']) }}</td>
                                <td class="whitespace-nowrap text-right tabular-nums">{{ rupiah($row['discount']) }}</td>
                                <td class="whitespace-nowrap text-right tabular-nums">{{ rupiah($row['tax']) }}</td>
                                <td class="whitespace-nowrap text-right font-semibold tabular-nums text-slate-900">{{ rupiah($row['omzet']) }}</td>
                                <td class="whitespace-nowrap text-right tabular-nums">{{ rupiah($row['cogs']) }}</td>
                                <td class="whitespace-nowrap text-right font-semibold tabular-nums text-emerald-700">{{ rupiah($row['gross_profit']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 border-slate-200 bg-slate-50 text-sm font-bold text-slate-900">
                        <tr>
                            <th scope="row" class="px-4 py-3 text-left">Total</th>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $num($s['transactions']) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ rupiah($s['net_sales']) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ rupiah($s['discount']) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ rupiah($s['tax']) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ rupiah($s['omzet']) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ rupiah($s['cogs']) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-emerald-700">{{ rupiah($s['gross_profit']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-card>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            {{-- Top products --}}
            <x-card title="Produk Terlaris" subtitle="10 produk dengan jumlah terjual terbanyak" class="lg:col-span-2" :padding="false">
                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead>
                            <tr>
                                <th scope="col" class="w-12">#</th>
                                <th scope="col">Produk</th>
                                <th scope="col" class="!text-right">Qty</th>
                                <th scope="col" class="!text-right">Omzet</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topProducts as $i => $product)
                                <tr>
                                    <td>
                                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary-50 text-xs font-bold text-primary-700">{{ $i + 1 }}</span>
                                    </td>
                                    <td class="font-medium text-slate-900">{{ $product->product_name }}</td>
                                    <td class="text-right tabular-nums">{{ $num($product->qty) }}</td>
                                    <td class="whitespace-nowrap text-right font-semibold tabular-nums text-slate-900">{{ rupiah($product->revenue) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>

            {{-- Payment breakdown --}}
            <x-card title="Metode Pembayaran">
                <ul class="space-y-4">
                    @foreach ($payments as $method => $payment)
                        @php $share = $paymentTotal > 0 ? $payment['total'] / $paymentTotal * 100 : 0; @endphp
                        <li>
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="font-medium text-slate-700">{{ $payment['label'] }}</span>
                                <span class="font-semibold tabular-nums text-slate-900">{{ rupiah($payment['total']) }}</span>
                            </div>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100"
                                 role="progressbar" aria-label="Porsi {{ $payment['label'] }}"
                                 aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round($share) }}">
                                <div class="h-full rounded-full {{ $paymentBars[$method] ?? 'bg-slate-400' }}" style="width: {{ round($share, 1) }}%"></div>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">{{ $num($payment['count']) }} transaksi · {{ percent($share) }}</p>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        </div>
    @endif
</x-app-layout>

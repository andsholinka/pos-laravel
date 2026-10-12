{{-- Sales report for dompdf: plain HTML + inline CSS 2.1 (tables only, no Tailwind/Vite). A4 portrait. --}}
@php
    $s = $summary;
    $isMonthly = $group === 'monthly';
    $num = fn ($n) => number_format($n, 0, ',', '.');
    $period = $from->translatedFormat('d F Y').($from->isSameDay($to) ? '' : ' - '.$to->translatedFormat('d F Y'));
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan {{ $period }}</title>
    <style>
        @page { margin: 28px 32px 48px 32px; }
        * { font-family: "DejaVu Sans", sans-serif; }
        body { font-size: 9.5px; color: #1e293b; margin: 0; }
        h1 { font-size: 16px; margin: 0 0 2px 0; color: #047857; }
        h2 { font-size: 11px; margin: 18px 0 6px 0; color: #0f172a; }
        .muted { color: #64748b; }
        .right { text-align: right; }
        .header { width: 100%; border-bottom: 2px solid #059669; padding-bottom: 8px; }
        .header td { vertical-align: top; }
        .store { font-size: 12px; font-weight: bold; }
        .logo { max-height: 48px; max-width: 120px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #f1f5f9; color: #475569; font-size: 8.5px; text-transform: uppercase; text-align: left; padding: 5px 6px; border-bottom: 1px solid #cbd5e1; }
        table.data td { padding: 4px 6px; border-bottom: 1px solid #e2e8f0; }
        table.data th.right, table.data td.right { text-align: right; }
        table.data tr.total td { font-weight: bold; background: #ecfdf5; border-top: 1.5px solid #059669; border-bottom: none; }
        table.summary { width: 100%; border-collapse: separate; border-spacing: 6px; margin: 0 -6px; }
        table.summary td { width: 33%; border: 1px solid #e2e8f0; padding: 8px 10px; background: #f8fafc; }
        .label { font-size: 8.5px; color: #64748b; text-transform: uppercase; }
        .value { font-size: 13px; font-weight: bold; color: #0f172a; margin-top: 2px; }
        .profit { color: #047857; }
        .footer { position: fixed; bottom: -30px; left: 0; right: 0; font-size: 8px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="footer">
        {{ $store->store_name }} · Laporan Penjualan {{ $period }} · Dicetak {{ now()->translatedFormat('d F Y H:i') }}
    </div>

    <table class="header">
        <tr>
            <td style="width: 60%;">
                @if ($logo)
                    <img src="{{ $logo }}" alt="Logo" class="logo"><br>
                @endif
                <div class="store">{{ $store->store_name }}</div>
                @if ($store->address)<div class="muted">{{ $store->address }}</div>@endif
                @if ($store->phone)<div class="muted">Telp. {{ $store->phone }}</div>@endif
            </td>
            <td class="right">
                <h1>Laporan Penjualan</h1>
                <div>Periode: <strong>{{ $period }}</strong></div>
                <div class="muted">Dikelompokkan {{ $isMonthly ? 'per bulan' : 'per hari' }}</div>
                <div class="muted">Dicetak: {{ now()->translatedFormat('d F Y H:i') }}</div>
            </td>
        </tr>
    </table>

    <h2>Ringkasan</h2>
    <table class="summary">
        <tr>
            <td><div class="label">Omzet</div><div class="value">{{ rupiah($s['omzet']) }}</div></td>
            <td><div class="label">Penjualan Bersih</div><div class="value">{{ rupiah($s['net_sales']) }}</div></td>
            <td><div class="label">HPP</div><div class="value">{{ rupiah($s['cogs']) }}</div></td>
        </tr>
        <tr>
            <td><div class="label">Laba Kotor (margin {{ percent($s['margin']) }})</div><div class="value profit">{{ rupiah($s['gross_profit']) }}</div></td>
            <td><div class="label">Jumlah Transaksi</div><div class="value">{{ $num($s['transactions']) }} <span class="muted" style="font-size: 9px; font-weight: normal;">({{ $num($s['items_sold']) }} item)</span></div></td>
            <td><div class="label">Rata-rata / Transaksi</div><div class="value">{{ rupiah($s['avg_ticket']) }}</div></td>
        </tr>
    </table>

    <h2>Rincian {{ $isMonthly ? 'per Bulan' : 'per Hari' }}</h2>
    <table class="data">
        <thead>
            <tr>
                <th>{{ $isMonthly ? 'Bulan' : 'Tanggal' }}</th>
                <th class="right">Trx</th>
                <th class="right">Penj. Bersih</th>
                <th class="right">Diskon</th>
                <th class="right">Pajak</th>
                <th class="right">Omzet</th>
                <th class="right">HPP</th>
                <th class="right">Laba Kotor</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $isMonthly ? $row['label'] : \Carbon\CarbonImmutable::parse($row['date'])->translatedFormat('d M Y') }}</td>
                    <td class="right">{{ $num($row['transactions']) }}</td>
                    <td class="right">{{ $num($row['net_sales']) }}</td>
                    <td class="right">{{ $num($row['discount']) }}</td>
                    <td class="right">{{ $num($row['tax']) }}</td>
                    <td class="right">{{ $num($row['omzet']) }}</td>
                    <td class="right">{{ $num($row['cogs']) }}</td>
                    <td class="right">{{ $num($row['gross_profit']) }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td>TOTAL</td>
                <td class="right">{{ $num($s['transactions']) }}</td>
                <td class="right">{{ $num($s['net_sales']) }}</td>
                <td class="right">{{ $num($s['discount']) }}</td>
                <td class="right">{{ $num($s['tax']) }}</td>
                <td class="right">{{ $num($s['omzet']) }}</td>
                <td class="right">{{ $num($s['cogs']) }}</td>
                <td class="right">{{ $num($s['gross_profit']) }}</td>
            </tr>
        </tbody>
    </table>
    <p class="muted" style="margin-top: 4px;">Nilai dalam Rupiah. Hanya transaksi berstatus selesai; transaksi yang dibatalkan tidak dihitung.</p>

    <table style="width: 100%; margin-top: 6px;">
        <tr>
            <td style="width: 62%; vertical-align: top; padding-right: 12px;">
                <h2>Produk Terlaris</h2>
                @if ($topProducts->isEmpty())
                    <p class="muted">Belum ada produk terjual pada periode ini.</p>
                @else
                    <table class="data">
                        <thead>
                            <tr>
                                <th style="width: 22px;">#</th>
                                <th>Produk</th>
                                <th class="right">Qty</th>
                                <th class="right">Omzet</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topProducts as $i => $product)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $product->product_name }}</td>
                                    <td class="right">{{ $num($product->qty) }}</td>
                                    <td class="right">{{ $num($product->revenue) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </td>
            <td style="vertical-align: top;">
                <h2>Metode Pembayaran</h2>
                <table class="data">
                    <thead>
                        <tr>
                            <th>Metode</th>
                            <th class="right">Trx</th>
                            <th class="right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr>
                                <td>{{ $payment['label'] }}</td>
                                <td class="right">{{ $num($payment['count']) }}</td>
                                <td class="right">{{ $num($payment['total']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

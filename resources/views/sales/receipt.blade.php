{{--
    Thermal receipt (58mm / 80mm). Standalone page with inline CSS only (no Vite) so it prints
    exactly at the paper width. ?paper=58|80 (default from store settings), ?print=1 auto-prints.
    $store comes from the view composer.
--}}
@php
    $isWide = $paper === '80';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk {{ $sale->invoice_number }} · {{ $store->store_name }}</title>
    <style>
        @page { size: {{ $paper }}mm auto; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { background: #e2e8f0; font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; }

        .toolbar { position: sticky; top: 0; display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; align-items: center; padding: 12px; background: #0f172a; }
        .toolbar a, .toolbar button { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border: 0; border-radius: 8px; font: 600 13px/1 system-ui, -apple-system, 'Segoe UI', sans-serif; text-decoration: none; cursor: pointer; color: #e2e8f0; background: #334155; }
        .toolbar a:hover, .toolbar button:hover { background: #475569; }
        .toolbar a:focus-visible, .toolbar button:focus-visible { outline: 2px solid #34d399; outline-offset: 2px; }
        .toolbar .primary { background: #059669; color: #fff; }
        .toolbar .primary:hover { background: #047857; }
        .toolbar .active { background: #f8fafc; color: #0f172a; }
        .toolbar .group { display: inline-flex; gap: 4px; padding: 3px; border-radius: 10px; background: #1e293b; }

        .paper { width: {{ $isWide ? '80mm' : '58mm' }}; margin: 24px auto; background: #fff; box-shadow: 0 10px 30px rgba(15, 23, 42, .18); }
        .receipt { width: {{ $isWide ? '72mm' : '48mm' }}; margin: 0 auto; padding: 3mm 0; font-family: 'Courier New', ui-monospace, monospace; font-size: {{ $isWide ? '12px' : '11px' }}; line-height: 1.35; color: #000; }
        .center { text-align: center; }
        .bold { font-weight: 700; }
        .upper { text-transform: uppercase; }
        .logo { display: block; max-height: 40px; max-width: 100%; margin: 0 auto 4px; filter: grayscale(100%); }
        .store-name { font-size: 1.25em; font-weight: 700; text-transform: uppercase; }
        .sep { border: 0; border-top: 1px dashed #000; margin: 6px 0; }
        .row { display: flex; justify-content: space-between; gap: 6px; }
        .row > span:last-child { text-align: right; white-space: nowrap; }
        .item { margin-bottom: 3px; }
        .item-name { word-break: break-word; }
        .total { font-size: 1.2em; font-weight: 700; }
        .void { margin: 6px 0; padding: 4px; border: 2px solid #000; text-align: center; font-size: 1.4em; font-weight: 700; letter-spacing: 2px; }
        .footer { white-space: normal; }

        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
            .paper { width: auto; margin: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    <nav class="toolbar no-print" aria-label="Aksi struk">
        <button type="button" class="primary" onclick="window.print()">Cetak</button>
        <span class="group" role="group" aria-label="Ukuran kertas">
            <a href="{{ route('sales.receipt', [$sale, 'paper' => 58]) }}" @class(['active' => ! $isWide]) @if (! $isWide) aria-current="true" @endif>58mm</a>
            <a href="{{ route('sales.receipt', [$sale, 'paper' => 80]) }}" @class(['active' => $isWide]) @if ($isWide) aria-current="true" @endif>80mm</a>
        </span>
        <button type="button" onclick="window.close()">Tutup</button>
    </nav>

    <main class="paper">
        <div class="receipt">
            <div class="center">
                @if ($store->logo_url)
                    <img src="{{ $store->logo_url }}" alt="" class="logo">
                @endif
                <div class="store-name">{{ $store->store_name }}</div>
                @if ($store->address)
                    <div>{{ $store->address }}</div>
                @endif
                @if ($store->phone)
                    <div>Telp. {{ $store->phone }}</div>
                @endif
            </div>

            <hr class="sep">

            <div class="row"><span>No</span><span>{{ $sale->invoice_number }}</span></div>
            <div class="row"><span>Tanggal</span><span>{{ $sale->created_at->format('d/m/Y H:i') }}</span></div>
            <div class="row"><span>Kasir</span><span>{{ $sale->user?->name }}</span></div>

            @if ($sale->isVoid())
                <div class="void">DIBATALKAN</div>
                @if ($sale->void_reason)
                    <div class="center">Alasan: {{ $sale->void_reason }}</div>
                @endif
            @endif

            <hr class="sep">

            @foreach ($sale->items as $item)
                <div class="item">
                    <div class="item-name">{{ $item->product_name }}</div>
                    <div class="row">
                        <span>{{ $item->quantity }} x {{ number_format($item->price, 0, ',', '.') }}</span>
                        <span>{{ number_format($item->subtotal, 0, ',', '.') }}</span>
                    </div>
                </div>
            @endforeach

            <hr class="sep">

            <div class="row"><span>Subtotal</span><span>{{ rupiah($sale->subtotal) }}</span></div>
            @if ($sale->discount_amount > 0)
                <div class="row">
                    <span>Diskon{{ $sale->discount_type === \App\Enums\DiscountType::Percent ? ' ('.percent((float) $sale->discount_value, 2).')' : '' }}</span>
                    <span>-{{ rupiah($sale->discount_amount) }}</span>
                </div>
            @endif
            @if ($sale->tax_amount > 0)
                <div class="row"><span>Pajak ({{ percent((float) $sale->tax_percent, 2) }})</span><span>{{ rupiah($sale->tax_amount) }}</span></div>
            @endif
            <div class="row total"><span>TOTAL</span><span>{{ rupiah($sale->total) }}</span></div>

            <hr class="sep">

            <div class="row"><span>Metode</span><span>{{ $sale->payment_method->label() }}</span></div>
            <div class="row"><span>Bayar</span><span>{{ rupiah($sale->paid_amount) }}</span></div>
            <div class="row bold"><span>Kembalian</span><span>{{ rupiah($sale->change_amount) }}</span></div>

            @if ($sale->note)
                <div style="margin-top: 4px">Catatan: {{ $sale->note }}</div>
            @endif

            <hr class="sep">

            @if ($store->receipt_footer)
                <div class="center footer">{!! nl2br(e($store->receipt_footer)) !!}</div>
            @endif
            <div class="center" style="margin-top: 4px">Jumlah item: {{ $sale->items->sum('quantity') }}</div>
        </div>
    </main>

    @if ($autoPrint)
        <script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
    @endif
</body>
</html>

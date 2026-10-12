<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportFilterRequest;
use App\Models\StoreSetting;
use App\Services\SalesReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly SalesReportService $reports) {}

    public function index(ReportFilterRequest $request): View
    {
        $data = $this->data($request);
        $rows = $data['rows'];

        return view('reports.index', $data + [
            'quickRanges' => $this->quickRanges($data['from'], $data['to']),
            'chart' => [
                'labels' => $rows->pluck('label')->all(),
                'omzet' => $rows->pluck('omzet')->all(),
                'second' => $rows->pluck('gross_profit')->all(),
                'secondLabel' => 'Laba Kotor',
                'secondIsMoney' => true,
            ],
        ]);
    }

    public function pdf(ReportFilterRequest $request): Response
    {
        $data = $this->data($request);

        return Pdf::loadView('reports.pdf', $data + ['logo' => $this->logoDataUri()])
            ->setPaper('a4', 'portrait')
            // Embed only the glyphs used instead of the whole DejaVu font (~880 KB -> tens of KB).
            ->setOption('isFontSubsettingEnabled', true)
            ->download($this->filename($data, 'pdf'));
    }

    public function csv(ReportFilterRequest $request): StreamedResponse
    {
        $data = $this->data($request);
        $store = StoreSetting::current();

        return response()->streamDownload(function () use ($data, $store) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM so Excel detects the encoding; ';' is the Indonesian Excel list separator.
            fwrite($out, "\xEF\xBB\xBF");
            // Text cells starting with = + - @ are prefixed with ' so Excel never runs them as formulas.
            $put = fn (array $row) => fputcsv($out, array_map(
                fn ($cell) => is_string($cell) && preg_match('/^[=+\-@]/', $cell) ? "'".$cell : $cell,
                $row,
            ), ';', '"', '');
            $s = $data['summary'];
            $periodLabel = $data['group'] === 'monthly' ? 'Bulan' : 'Tanggal';

            $put(['Laporan Penjualan']);
            $put([$store->store_name]);
            $put(['Periode', $data['from']->format('d/m/Y').' - '.$data['to']->format('d/m/Y')]);
            $put(['']);

            $put([$periodLabel, 'Transaksi', 'Penjualan Bersih', 'Diskon', 'Pajak', 'Omzet', 'HPP', 'Laba Kotor']);
            foreach ($data['rows'] as $row) {
                $put([
                    $data['group'] === 'monthly' ? $row['label'] : $row['date'],
                    $row['transactions'], $row['net_sales'], $row['discount'], $row['tax'],
                    $row['omzet'], $row['cogs'], $row['gross_profit'],
                ]);
            }
            $put(['TOTAL', $s['transactions'], $s['net_sales'], $s['discount'], $s['tax'], $s['omzet'], $s['cogs'], $s['gross_profit']]);
            $put(['']);

            $put(['Ringkasan']);
            $put(['Omzet', $s['omzet']]);
            $put(['Penjualan Bersih', $s['net_sales']]);
            $put(['Diskon', $s['discount']]);
            $put(['Pajak', $s['tax']]);
            $put(['HPP', $s['cogs']]);
            $put(['Laba Kotor', $s['gross_profit']]);
            $put(['Margin (%)', str_replace('.', ',', (string) $s['margin'])]);
            $put(['Jumlah Transaksi', $s['transactions']]);
            $put(['Item Terjual', $s['items_sold']]);
            $put(['Rata-rata per Transaksi', $s['avg_ticket']]);
            $put(['']);

            $put(['Metode Pembayaran', 'Transaksi', 'Total']);
            foreach ($data['payments'] as $payment) {
                $put([$payment['label'], $payment['count'], $payment['total']]);
            }
            $put(['']);

            $put(['Produk Terlaris']);
            $put(['Peringkat', 'Produk', 'Qty', 'Omzet']);
            foreach ($data['topProducts'] as $i => $product) {
                $put([$i + 1, $product->product_name, $product->qty, $product->revenue]);
            }

            fclose($out);
        }, $this->filename($data, 'csv'), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Everything the report page, PDF and CSV render.
     *
     * @return array<string, mixed>
     */
    private function data(ReportFilterRequest $request): array
    {
        [$from, $to] = $request->range();
        $group = $request->group();

        return [
            'from' => $from,
            'to' => $to,
            'group' => $group,
            'summary' => $this->reports->summary($from, $to),
            'rows' => $group === 'monthly'
                ? $this->reports->monthlySeries($from, $to)
                : $this->reports->dailySeries($from, $to),
            'topProducts' => $this->reports->topProducts($from, $to, 10),
            'payments' => $this->reports->paymentBreakdown($from, $to),
        ];
    }

    /** @param array<string, mixed> $data */
    private function filename(array $data, string $extension): string
    {
        return "laporan-penjualan-{$data['from']->format('Y-m-d')}_{$data['to']->format('Y-m-d')}.{$extension}";
    }

    /**
     * Quick range links for the filter bar.
     *
     * @return list<array{label: string, url: string, active: bool}>
     */
    private function quickRanges(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $today = CarbonImmutable::today();
        $lastMonth = $today->subMonthNoOverflow();

        $ranges = [
            'Hari Ini' => [$today, $today],
            '7 Hari' => [$today->subDays(6), $today],
            'Bulan Ini' => [$today->startOfMonth(), $today],
            'Bulan Lalu' => [$lastMonth->startOfMonth(), $lastMonth->endOfMonth()->startOfDay()],
            '3 Bulan' => [$today->subMonthsNoOverflow(2)->startOfMonth(), $today],
        ];

        return collect($ranges)->map(fn (array $r, string $label) => [
            'label' => $label,
            'url' => route('reports.index', ['from' => $r[0]->format('Y-m-d'), 'to' => $r[1]->format('Y-m-d')]),
            'active' => $r[0]->isSameDay($from) && $r[1]->isSameDay($to),
        ])->values()->all();
    }

    /** Store logo as a data URI for dompdf (PNG/JPEG/GIF only), or null. */
    private function logoDataUri(): ?string
    {
        $path = StoreSetting::current()->logo_path;
        $file = $path ? storage_path('app/public/'.$path) : null;

        if (! $file || ! is_file($file)) {
            return null;
        }

        $mime = mime_content_type($file);

        if (! in_array($mime, ['image/png', 'image/jpeg', 'image/gif'], true)) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($file));
    }
}

<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sales aggregates for the dashboard and reports.
 *
 * Every method takes an inclusive day range (start/end of day are applied here) and only
 * counts completed sales. Grouping uses DATE(created_at), which works on SQLite and MySQL;
 * timestamps are stored in the app timezone, so DATE() is the local day.
 */
class SalesReportService
{
    /**
     * Totals for the range.
     *
     * @return array{transactions: int, omzet: int, net_sales: int, discount: int, tax: int, cogs: int, gross_profit: int, items_sold: int, avg_ticket: int, margin: float}
     */
    public function summary(CarbonInterface $from, CarbonInterface $to): array
    {
        $range = $this->range($from, $to);

        $sales = Sale::query()
            ->completed()
            ->whereBetween('created_at', $range)
            ->toBase()
            ->selectRaw('COUNT(*) as transactions')
            ->selectRaw('COALESCE(SUM(subtotal), 0) as subtotal')
            ->selectRaw('COALESCE(SUM(discount_amount), 0) as discount')
            ->selectRaw('COALESCE(SUM(tax_amount), 0) as tax')
            ->selectRaw('COALESCE(SUM(total), 0) as omzet')
            ->first();

        $items = $this->itemsQuery($range)
            ->selectRaw('COALESCE(SUM(sale_items.cost_price * sale_items.quantity), 0) as cogs')
            ->selectRaw('COALESCE(SUM(sale_items.quantity), 0) as items_sold')
            ->first();

        $transactions = (int) $sales->transactions;
        $omzet = (int) $sales->omzet;
        $discount = (int) $sales->discount;
        $netSales = (int) $sales->subtotal - $discount;
        $cogs = (int) $items->cogs;
        $grossProfit = $netSales - $cogs;

        return [
            'transactions' => $transactions,
            'omzet' => $omzet,
            'net_sales' => $netSales,
            'discount' => $discount,
            'tax' => (int) $sales->tax,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'items_sold' => (int) $items->items_sold,
            'avg_ticket' => $transactions > 0 ? (int) round($omzet / $transactions) : 0,
            'margin' => $netSales > 0 ? round($grossProfit / $netSales * 100, 1) : 0.0,
        ];
    }

    /**
     * One row per day in the range (missing days are zero-filled), ordered by date.
     *
     * @return Collection<int, array{date: string, label: string, transactions: int, omzet: int, net_sales: int, discount: int, tax: int, cogs: int, gross_profit: int}>
     */
    public function dailySeries(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $range = $this->range($from, $to);

        $sales = Sale::query()
            ->completed()
            ->whereBetween('created_at', $range)
            ->toBase()
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw('COUNT(*) as transactions')
            ->selectRaw('SUM(subtotal) as subtotal')
            ->selectRaw('SUM(discount_amount) as discount')
            ->selectRaw('SUM(tax_amount) as tax')
            ->selectRaw('SUM(total) as omzet')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $cogs = $this->itemsQuery($range)
            ->selectRaw('DATE(sales.created_at) as day')
            ->selectRaw('SUM(sale_items.cost_price * sale_items.quantity) as cogs')
            ->groupBy('day')
            ->get()
            ->pluck('cogs', 'day');

        $rows = collect();

        foreach (CarbonPeriod::create($range[0]->startOfDay(), '1 day', $range[1]->startOfDay()) as $day) {
            $key = $day->format('Y-m-d');
            $row = $sales->get($key);
            $discount = (int) ($row->discount ?? 0);
            $netSales = (int) ($row->subtotal ?? 0) - $discount;
            $dayCogs = (int) ($cogs[$key] ?? 0);

            $rows->push([
                'date' => $key,
                'label' => $day->translatedFormat('d M'),
                'transactions' => (int) ($row->transactions ?? 0),
                'omzet' => (int) ($row->omzet ?? 0),
                'net_sales' => $netSales,
                'discount' => $discount,
                'tax' => (int) ($row->tax ?? 0),
                'cogs' => $dayCogs,
                'gross_profit' => $netSales - $dayCogs,
            ]);
        }

        return $rows;
    }

    /**
     * Daily rows rolled up per calendar month (in PHP, so no driver-specific SQL).
     *
     * @return Collection<int, array{date: string, label: string, transactions: int, omzet: int, net_sales: int, discount: int, tax: int, cogs: int, gross_profit: int}>
     */
    public function monthlySeries(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $sumKeys = ['transactions', 'omzet', 'net_sales', 'discount', 'tax', 'cogs', 'gross_profit'];

        return $this->dailySeries($from, $to)
            ->groupBy(fn (array $row) => substr($row['date'], 0, 7))
            ->map(function (Collection $days, string $month) use ($sumKeys) {
                $row = [
                    'date' => $month,
                    'label' => CarbonImmutable::parse($month.'-01')->translatedFormat('F Y'),
                ];

                foreach ($sumKeys as $key) {
                    $row[$key] = (int) $days->sum($key);
                }

                return $row;
            })
            ->values();
    }

    /**
     * Best sellers by quantity.
     *
     * @return Collection<int, SaleItem> rows with product_id, product_name, qty, revenue
     */
    public function topProducts(CarbonInterface $from, CarbonInterface $to, int $limit = 10): Collection
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', SaleStatus::Completed->value)
            ->whereBetween('sales.created_at', $this->range($from, $to))
            ->groupBy('sale_items.product_id', 'sale_items.product_name')
            ->selectRaw('sale_items.product_id, sale_items.product_name, SUM(sale_items.quantity) as qty, SUM(sale_items.subtotal) as revenue')
            ->orderByDesc('qty')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->each(function (SaleItem $row) {
                $row->qty = (int) $row->qty;
                $row->revenue = (int) $row->revenue;
            });
    }

    /**
     * Count and total per payment method, zero-filled for all methods.
     *
     * @return array<string, array{label: string, count: int, total: int}>
     */
    public function paymentBreakdown(CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = Sale::query()
            ->completed()
            ->whereBetween('created_at', $this->range($from, $to))
            ->toBase()
            ->select('payment_method')
            ->selectRaw('COUNT(*) as count, SUM(total) as total')
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method');

        $result = [];

        foreach (PaymentMethod::cases() as $method) {
            $row = $rows->get($method->value);
            $result[$method->value] = [
                'label' => $method->label(),
                'count' => (int) ($row->count ?? 0),
                'total' => (int) ($row->total ?? 0),
            ];
        }

        return $result;
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    private function range(CarbonInterface $from, CarbonInterface $to): array
    {
        return [
            CarbonImmutable::instance($from)->startOfDay(),
            CarbonImmutable::instance($to)->endOfDay(),
        ];
    }

    /** sale_items joined to completed sales within the range. */
    private function itemsQuery(array $range): QueryBuilder
    {
        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', SaleStatus::Completed->value)
            ->whereBetween('sales.created_at', $range);
    }
}

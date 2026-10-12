<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SalesReportService;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Admin dashboard: today's stats, 30-day chart, best sellers, payment mix, low stock, latest sales.
     */
    public function __invoke(SalesReportService $reports): View
    {
        $today = CarbonImmutable::today();
        $yesterday = $today->subDay();
        $monthAgo = $today->subDays(29);

        $todaySummary = $reports->summary($today, $today);
        $yesterdayOmzet = $reports->summary($yesterday, $yesterday)['omzet'];
        $trend = $yesterdayOmzet > 0
            ? round(($todaySummary['omzet'] - $yesterdayOmzet) / $yesterdayOmzet * 100, 1)
            : null;

        $series = $reports->dailySeries($monthAgo, $today);

        return view('dashboard', [
            'today' => $todaySummary,
            'trend' => $trend,
            'lowStockCount' => Product::active()->lowStock()->count(),
            'productCount' => Product::active()->count(),
            'categoryCount' => Category::count(),
            'chart' => [
                'labels' => $series->pluck('label')->all(),
                'omzet' => $series->pluck('omzet')->all(),
                'second' => $series->pluck('transactions')->all(),
                'secondLabel' => 'Transaksi',
                'secondIsMoney' => false,
                'ranges' => [7, 30],
                'range' => 7,
            ],
            'series' => $series,
            'topProducts' => $reports->topProducts($monthAgo, $today, 5),
            'payments' => $reports->paymentBreakdown($monthAgo, $today),
            'lowStock' => Product::active()
                ->lowStock()
                ->with('category')
                ->orderBy('stock')
                ->orderBy('name')
                ->limit(config('pos.low_stock_dashboard_limit', 8))
                ->get(),
            'recentSales' => Sale::query()
                ->with('user:id,name')
                ->latest()
                ->latest('id')
                ->limit(5)
                ->get(),
        ]);
    }
}

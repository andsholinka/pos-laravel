<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Http\Requests\VoidSaleRequest;
use App\Models\Sale;
use App\Models\StoreSetting;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SaleController extends Controller
{
    /**
     * Transaction history. A kasir only ever sees their own sales.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();

        $methods = array_column(PaymentMethod::cases(), 'value');
        $statuses = array_column(SaleStatus::cases(), 'value');

        $filters = [
            'date_from' => $this->validDate($request->query('date_from')),
            'date_to' => $this->validDate($request->query('date_to')),
            'user_id' => $isAdmin ? ($request->integer('user_id') ?: null) : $user->id,
            'payment_method' => in_array($request->query('payment_method'), $methods, true) ? $request->query('payment_method') : null,
            'status' => in_array($request->query('status'), $statuses, true) ? $request->query('status') : null,
            'q' => is_string($request->query('q')) ? trim($request->query('q')) : null,
        ];

        $query = Sale::query()->filter($filters);

        $summary = (clone $query)->completed()
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')
            ->first();

        $sales = $query
            ->with('user:id,name')
            ->withCount('items')
            ->latest()
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('sales.index', [
            'sales' => $sales,
            'filters' => $filters,
            'summary' => ['count' => (int) $summary->count, 'total' => (int) $summary->total],
            'cashiers' => $isAdmin ? User::orderBy('name')->get(['id', 'name']) : collect(),
            'methods' => PaymentMethod::cases(),
            'statuses' => SaleStatus::cases(),
            'isAdmin' => $isAdmin,
        ]);
    }

    public function show(Sale $sale): View
    {
        Gate::authorize('view', $sale);

        $sale->load(['items', 'user:id,name', 'voidedBy:id,name']);

        return view('sales.show', ['sale' => $sale]);
    }

    /**
     * Standalone thermal receipt (?paper=58|80, ?print=1 auto-prints).
     */
    public function receipt(Request $request, Sale $sale): View
    {
        Gate::authorize('view', $sale);

        $sale->load(['items', 'user:id,name']);

        $paper = in_array($request->query('paper'), ['58', '80'], true)
            ? $request->query('paper')
            : (string) StoreSetting::current()->receipt_paper;

        return view('sales.receipt', [
            'sale' => $sale,
            'paper' => $paper,
            'autoPrint' => $request->boolean('print'),
        ]);
    }

    public function void(VoidSaleRequest $request, Sale $sale, CheckoutService $checkout): RedirectResponse
    {
        try {
            $checkout->void($sale, $request->user(), $request->validated('reason'));
        } catch (ValidationException $e) {
            // Already void (double submit / stale tab): the void form is no longer rendered,
            // so surface the message as a toast rather than a field error.
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Transaksi {$sale->invoice_number} berhasil dibatalkan, stok dikembalikan.");
    }

    private function validDate(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }
}

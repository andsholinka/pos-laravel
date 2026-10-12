<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Deterministic ~2.5 months of demo sales with a full stock simulation.
 *
 * Every stock change is written as a stock_movements row (in / sale / void / adjustment) with
 * consistent before/after values, and products.stock is set to the simulated final value.
 * Rows are bulk-inserted with explicit ids inside one transaction.
 */
class SalesHistorySeeder extends Seeder
{
    private const DAYS = 75;

    /** Days at the end of the period during which the showcase products are not restocked. */
    private const NO_RESTOCK_DAYS = 14;

    /**
     * Low-stock showcase products (never product id 1, which the smoke checkout uses).
     * The first two are forced to 0 ("Habis"), the rest end at or below min_stock ("Sisa N").
     */
    private const SHOWCASE_SKUS = ['MNM-005', 'FRZ-002', 'MKN-004', 'SNK-001', 'SMB-003', 'PRW-002'];

    private const ADMIN_ID = 1;

    private const KASIR_ID = 2;

    private const KASIR2_ID = 3;

    private const VOID_REASONS = ['Salah input produk', 'Pelanggan batal membeli'];

    /** @var array<int, array{id: int, sku: string, name: string, unit: string, price: int, cost: int, min: int, active: bool, pop: int, start: int, showcase: bool}> */
    private array $products = [];

    /** @var array<int, int> product id => running stock */
    private array $stock = [];

    /** @var list<array<string, mixed>> */
    private array $movements = [];

    private int $movementId = 0;

    /** @var list<int> */
    private array $weightedIds = [];

    /** @var list<int> */
    private array $cumulative = [];

    private int $totalWeight = 0;

    public function run(): void
    {
        mt_srand(20250101);

        $this->loadProducts();

        $now = now();
        $nowTs = $now->getTimestamp();
        $start = $now->copy()->startOfDay()->subDays(self::DAYS);

        $sales = [];
        $items = [];
        $saleId = 0;
        $itemId = 0;
        $voids = 0;

        // ~6 random stock-opname / damaged-goods adjustments on non-showcase products.
        $plainIds = array_values(array_filter($this->weightedIds, fn ($id) => ! $this->products[$id]['showcase']));
        $adjustments = [];
        for ($k = 0; $k < 6; $k++) {
            $reason = mt_rand(0, 1) ? 'stok_opname' : 'rusak';
            $adjustments[mt_rand(5, 70)][] = [
                'pid' => $plainIds[mt_rand(0, count($plainIds) - 1)],
                'reason' => $reason,
                'delta' => $reason === 'rusak' ? -mt_rand(1, 3) : (mt_rand(0, 1) ? 1 : -1) * mt_rand(1, 3),
            ];
        }

        // Day -76, 08:00: opening stock for every product.
        $openingTs = $start->copy()->subDay()->setTime(8, 0)->getTimestamp();
        foreach ($this->products as $id => $p) {
            $this->move($id, $p['start'], 'in', self::ADMIN_ID, null, null, 'Stok awal', $openingTs);
        }

        for ($d = 0; $d <= self::DAYS; $d++) {
            $date = $start->copy()->addDays($d);
            $dayTs = $date->getTimestamp();
            $isToday = $d === self::DAYS;
            $showcaseFrozen = $d > self::DAYS - self::NO_RESTOCK_DAYS;

            // 07:00 restock of anything running low.
            $restockTs = $dayTs + 7 * 3600;
            if (! $isToday || $restockTs <= $nowTs) {
                foreach ($this->products as $id => $p) {
                    if (! $p['active'] || ($p['showcase'] && $showcaseFrozen)) {
                        continue;
                    }
                    if ($this->stock[$id] < $p['min'] * 2) {
                        $this->move($id, $p['start'], 'in', self::ADMIN_ID, null, null, 'Pembelian dari supplier', $restockTs);
                    }
                }
            }

            $times = $this->saleTimes($d, $date->isWeekend(), $dayTs, $isToday, $nowTs);

            $seq = 0;
            $pendingVoids = [];

            foreach ($times as $ts) {
                $this->flushVoids($pendingVoids, $ts);

                $lines = $this->pickLines();
                if ($lines === []) {
                    continue;
                }

                $saleId++;
                $seq++;
                $invoice = 'INV-'.date('Ymd', $ts).'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
                $at = date('Y-m-d H:i:s', $ts);

                $subtotal = array_sum(array_map(fn ($l) => $l['price'] * $l['qty'], $lines));
                [$discountType, $discountValue, $discountAmount] = $this->discount($subtotal);
                $total = $subtotal - $discountAmount;

                $r = mt_rand(1, 100);
                $method = $r <= 60 ? 'tunai' : ($r <= 90 ? 'qris' : 'transfer');
                $paid = $method === 'tunai' ? $this->cashPaid($total) : $total;

                $r = mt_rand(1, 100);
                $cashier = $r <= 55 ? self::KASIR_ID : ($r <= 90 ? self::KASIR2_ID : self::ADMIN_ID);

                $sale = [
                    'id' => $saleId,
                    'invoice_number' => $invoice,
                    'user_id' => $cashier,
                    'subtotal' => $subtotal,
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                    'discount_amount' => $discountAmount,
                    'tax_percent' => 0,
                    'tax_amount' => 0,
                    'total' => $total,
                    'payment_method' => $method,
                    'paid_amount' => $paid,
                    'change_amount' => $paid - $total,
                    'note' => null,
                    'status' => 'completed',
                    'voided_at' => null,
                    'voided_by' => null,
                    'void_reason' => null,
                    'created_at' => $at,
                    'updated_at' => $at,
                ];

                foreach ($lines as $l) {
                    $items[] = [
                        'id' => ++$itemId,
                        'sale_id' => $saleId,
                        'product_id' => $l['id'],
                        'product_name' => $l['name'],
                        'sku' => $l['sku'],
                        'unit' => $l['unit'],
                        'price' => $l['price'],
                        'cost_price' => $l['cost'],
                        'quantity' => $l['qty'],
                        'subtotal' => $l['price'] * $l['qty'],
                        'created_at' => $at,
                        'updated_at' => $at,
                    ];
                    $this->move($l['id'], -$l['qty'], 'sale', $cashier, $saleId, null, $invoice, $ts);
                }

                // ~1.5% of past sales are voided 10 minutes later (stock restored then).
                if (! $isToday && mt_rand(1, 1000) <= 15) {
                    $voidTs = $ts + 600;
                    $voidAt = date('Y-m-d H:i:s', $voidTs);
                    $sale['status'] = 'void';
                    $sale['voided_at'] = $voidAt;
                    $sale['voided_by'] = self::ADMIN_ID;
                    $sale['void_reason'] = self::VOID_REASONS[mt_rand(0, 1)];
                    $sale['updated_at'] = $voidAt;
                    $pendingVoids[] = ['ts' => $voidTs, 'sale_id' => $saleId, 'invoice' => $invoice, 'lines' => $lines];
                    $voids++;
                }

                $sales[] = $sale;
            }

            $this->flushVoids($pendingVoids, null);

            // 21:45: scheduled adjustments for this day.
            foreach ($adjustments[$d] ?? [] as $adj) {
                if ($this->stock[$adj['pid']] + $adj['delta'] < 0) {
                    continue;
                }
                $note = $adj['reason'] === 'rusak' ? 'Kemasan rusak' : 'Selisih stok opname';
                $this->move($adj['pid'], $adj['delta'], 'adjustment', self::ADMIN_ID, null, $adj['reason'], $note, $dayTs + 21 * 3600 + 45 * 60);
            }
        }

        $this->finalizeShowcase($nowTs);

        DB::transaction(function () use ($sales, $items) {
            foreach (array_chunk($sales, 500) as $chunk) {
                DB::table('sales')->insert($chunk);
            }
            foreach (array_chunk($items, 500) as $chunk) {
                DB::table('sale_items')->insert($chunk);
            }
            foreach (array_chunk($this->movements, 500) as $chunk) {
                DB::table('stock_movements')->insert($chunk);
            }
            foreach ($this->stock as $id => $stock) {
                DB::table('products')->where('id', $id)->update(['stock' => $stock]);
            }
        });

        $this->command?->info(sprintf(
            'SalesHistorySeeder: %d transaksi (%d dibatalkan), %d item, %d pergerakan stok.',
            count($sales), $voids, count($items), count($this->movements),
        ));
    }

    private function loadProducts(): void
    {
        $meta = collect(ProductSeeder::PRODUCTS)->keyBy(1);

        foreach (Product::query()->orderBy('id')->get() as $p) {
            $m = $meta[$p->sku];
            $this->products[$p->id] = [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'unit' => $p->unit,
                'price' => $p->price,
                'cost' => $p->cost_price,
                'min' => $p->min_stock,
                'active' => $p->is_active,
                'pop' => $m[7],
                'start' => $m[8],
                'showcase' => in_array($p->sku, self::SHOWCASE_SKUS, true),
            ];
            $this->stock[$p->id] = 0;

            if ($p->is_active) {
                $this->totalWeight += $m[7];
                $this->weightedIds[] = $p->id;
                $this->cumulative[] = $this->totalWeight;
            }
        }

        if ($this->products[1]['showcase'] ?? false) {
            throw new RuntimeException('Product id 1 must not be a low-stock showcase product.');
        }
    }

    /**
     * Sorted unix timestamps of the day's sales (07:01-21:30, more on weekends, gentle growth).
     *
     * @return list<int>
     */
    private function saleTimes(int $d, bool $weekend, int $dayTs, bool $isToday, int $nowTs): array
    {
        $n = mt_rand(16, 30) * ($weekend ? 1.3 : 1.0) * (1 + 0.25 * $d / self::DAYS);
        $n = (int) round($n);

        $from = 7 * 3600 + 60;
        $to = 21 * 3600 + 30 * 60;
        $times = [];
        for ($i = 0; $i < $n; $i++) {
            $times[] = $dayTs + mt_rand($from, $to);
        }

        if ($isToday) {
            if ($nowTs >= $dayTs + $from) {
                $times = array_values(array_filter($times, fn ($t) => $t <= $nowTs));
            } else {
                // Before opening hours: a few early sales, if there is room for them.
                $times = [];
                $earliest = $dayTs + 30 * 60;
                $latest = $nowTs - 5 * 60;
                if ($latest > $earliest) {
                    for ($i = 0; $i < 3; $i++) {
                        $times[] = mt_rand($earliest, $latest);
                    }
                }
            }
        }

        sort($times);

        return $times;
    }

    /**
     * 1-5 distinct products weighted by popularity; lines without enough stock are skipped.
     *
     * @return list<array{id: int, name: string, sku: string, unit: string, price: int, cost: int, qty: int}>
     */
    private function pickLines(): array
    {
        $want = mt_rand(1, 5);
        $picked = [];
        for ($attempt = 0; count($picked) < $want && $attempt < 20; $attempt++) {
            $picked[$this->pickWeighted()] = true;
        }

        $lines = [];
        foreach (array_keys($picked) as $id) {
            $p = $this->products[$id];
            $qty = in_array($p['unit'], ['kg', 'pack'], true) ? 1 : mt_rand(1, 3);
            if ($this->stock[$id] < $qty) {
                continue;
            }
            $lines[] = [
                'id' => $id,
                'name' => $p['name'],
                'sku' => $p['sku'],
                'unit' => $p['unit'],
                'price' => $p['price'],
                'cost' => $p['cost'],
                'qty' => $qty,
            ];
        }

        return $lines;
    }

    private function pickWeighted(): int
    {
        $r = mt_rand(1, $this->totalWeight);
        foreach ($this->cumulative as $i => $limit) {
            if ($r <= $limit) {
                return $this->weightedIds[$i];
            }
        }

        return $this->weightedIds[array_key_last($this->weightedIds)];
    }

    /**
     * ~12% of sales get a discount (5%/10% or Rp 1.000/2.000/5.000).
     *
     * @return array{0: string|null, 1: int, 2: int} [type, value, amount]
     */
    private function discount(int $subtotal): array
    {
        if (mt_rand(1, 100) > 12) {
            return [null, 0, 0];
        }

        [$type, $value] = [['percent', 5], ['percent', 10], ['nominal', 1000], ['nominal', 2000], ['nominal', 5000]][mt_rand(0, 4)];

        if ($type === 'percent') {
            return [$type, $value, (int) round($subtotal * $value / 100)];
        }

        // Only give a flat discount on baskets clearly larger than it.
        if ($subtotal <= $value * 2) {
            return [null, 0, 0];
        }

        return [$type, $value, min($value, $subtotal)];
    }

    /** Cash handed over: exact, or rounded up to a sensible note (random among the first 3 options). */
    private function cashPaid(int $total): int
    {
        $candidates = [$total, (int) (ceil($total / 5000) * 5000), (int) (ceil($total / 10000) * 10000)];
        foreach ([20000, 50000, 100000] as $note) {
            if ($note >= $total) {
                $candidates[] = $note;
            }
        }
        $candidates = array_values(array_unique($candidates));
        sort($candidates);

        return $candidates[mt_rand(0, min(2, count($candidates) - 1))];
    }

    /**
     * Write the void movements (+qty) that are due by $until (null = all).
     *
     * @param  list<array{ts: int, sale_id: int, invoice: string, lines: list<array{id: int, qty: int}>}>  $pending
     */
    private function flushVoids(array &$pending, ?int $until): void
    {
        foreach ($pending as $k => $v) {
            if ($until !== null && $v['ts'] > $until) {
                continue;
            }
            foreach ($v['lines'] as $l) {
                $this->move($l['id'], $l['qty'], 'void', self::ADMIN_ID, $v['sale_id'], null, "Void {$v['invoice']}", $v['ts']);
            }
            unset($pending[$k]);
        }
    }

    /** Make sure the dashboard shows both "Habis" and "Sisa N" products. */
    private function finalizeShowcase(int $nowTs): void
    {
        $forcedReasons = [['rusak', 'Kemasan rusak'], ['kadaluarsa', 'Melewati tanggal kadaluarsa']];

        foreach (self::SHOWCASE_SKUS as $i => $sku) {
            $p = collect($this->products)->firstWhere('sku', $sku);
            $id = $p['id'];
            $current = $this->stock[$id];

            if ($i < 2) {
                if ($current > 0) {
                    [$reason, $note] = $forcedReasons[$i];
                    $this->move($id, -$current, 'adjustment', self::ADMIN_ID, null, $reason, $note, $nowTs);
                }

                continue;
            }

            if ($current > $p['min'] || $current === 0) {
                $target = mt_rand(max(1, intdiv($p['min'], 2)), $p['min']);
                if ($target !== $current) {
                    $this->move($id, $target - $current, 'adjustment', self::ADMIN_ID, null, 'stok_opname', 'Selisih stok opname', $nowTs);
                }
            }
        }
    }

    private function move(int $pid, int $delta, string $type, int $userId, ?int $saleId, ?string $reason, ?string $note, int $ts): void
    {
        $before = $this->stock[$pid];
        $after = $before + $delta;
        $this->stock[$pid] = $after;
        $at = date('Y-m-d H:i:s', $ts);

        $this->movements[] = [
            'id' => ++$this->movementId,
            'product_id' => $pid,
            'user_id' => $userId,
            'sale_id' => $saleId,
            'type' => $type,
            'quantity' => $delta,
            'stock_before' => $before,
            'stock_after' => $after,
            'reason' => $reason,
            'note' => $note,
            'created_at' => $at,
            'updated_at' => $at,
        ];
    }
}

<x-app-layout title="Riwayat Transaksi" :subtitle="$isAdmin ? 'Semua transaksi penjualan toko.' : 'Transaksi penjualan yang Anda proses.'">
    <x-slot name="actions">
        <a href="{{ route('pos.index') }}" class="btn btn-primary">
            <x-icon name="shopping-cart" class="h-4 w-4" />
            Buka Kasir
        </a>
    </x-slot>

    <x-filter-bar :action="route('sales.index')">
        <div>
            <x-input-label for="q" value="No. Invoice" />
            <x-text-input id="q" name="q" type="search" placeholder="INV-..." :value="$filters['q']" />
        </div>
        <div>
            <x-input-label for="date_from" value="Dari tanggal" />
            <x-text-input id="date_from" name="date_from" type="date" :value="$filters['date_from']" />
        </div>
        <div>
            <x-input-label for="date_to" value="Sampai tanggal" />
            <x-text-input id="date_to" name="date_to" type="date" :value="$filters['date_to']" />
        </div>
        @if ($isAdmin)
            <div>
                <x-input-label for="user_id" value="Kasir" />
                <x-select-input id="user_id" name="user_id">
                    <option value="">Semua kasir</option>
                    @foreach ($cashiers as $cashier)
                        <option value="{{ $cashier->id }}" @selected($filters['user_id'] === $cashier->id)>{{ $cashier->name }}</option>
                    @endforeach
                </x-select-input>
            </div>
        @endif
        <div>
            <x-input-label for="payment_method" value="Metode" />
            <x-select-input id="payment_method" name="payment_method">
                <option value="">Semua metode</option>
                @foreach ($methods as $method)
                    <option value="{{ $method->value }}" @selected($filters['payment_method'] === $method->value)>{{ $method->label() }}</option>
                @endforeach
            </x-select-input>
        </div>
        <div>
            <x-input-label for="status" value="Status" />
            <x-select-input id="status" name="status">
                <option value="">Semua status</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </x-select-input>
        </div>
    </x-filter-bar>

    {{-- Summary of the filtered result (completed sales only) --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2">
        <x-stat-card label="Transaksi Selesai" value="{{ number_format($summary['count'], 0, ',', '.') }}" icon="receipt-percent" color="sky"
                     hint="Sesuai filter, tidak termasuk yang dibatalkan" />
        <x-stat-card label="Total Penjualan" value="{{ rupiah($summary['total']) }}" icon="banknotes" color="emerald"
                     hint="Jumlah total transaksi selesai pada filter ini" />
    </div>

    <x-card :padding="false">
        @if ($sales->isEmpty())
            <x-empty-state icon="receipt-percent" title="Belum ada transaksi" description="Tidak ada transaksi untuk filter ini. Coba ubah filter atau mulai transaksi di layar kasir.">
                <x-slot name="action">
                    <a href="{{ route('pos.index') }}" class="btn btn-primary">
                        <x-icon name="shopping-cart" class="h-4 w-4" />
                        Buka Kasir
                    </a>
                </x-slot>
            </x-empty-state>
        @else
            {{-- Desktop table --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th scope="col">No. Invoice</th>
                            <th scope="col">Waktu</th>
                            <th scope="col">Kasir</th>
                            <th scope="col" class="text-right">Item</th>
                            <th scope="col" class="text-right">Total</th>
                            <th scope="col">Metode</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-right"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sales as $sale)
                            <tr @class(['opacity-70' => $sale->isVoid()])>
                                <td class="whitespace-nowrap">
                                    <a href="{{ route('sales.show', $sale) }}" class="font-mono font-semibold text-slate-900 hover:text-primary-700">{{ $sale->invoice_number }}</a>
                                </td>
                                <td class="whitespace-nowrap text-slate-600">{{ $sale->created_at->translatedFormat('d M Y H:i') }}</td>
                                <td class="whitespace-nowrap text-slate-600">{{ $sale->user?->name }}</td>
                                <td class="text-right tabular-nums text-slate-600">{{ $sale->items_count }}</td>
                                <td @class(['whitespace-nowrap text-right font-semibold tabular-nums', 'text-slate-900' => ! $sale->isVoid(), 'text-slate-500 line-through' => $sale->isVoid()])>{{ rupiah($sale->total) }}</td>
                                <td>
                                    <x-badge :color="$sale->payment_method->color()">
                                        <x-icon :name="$sale->payment_method->icon()" class="h-3.5 w-3.5" />
                                        {{ $sale->payment_method->label() }}
                                    </x-badge>
                                </td>
                                <td><x-badge :color="$sale->status->color()">{{ $sale->status->label() }}</x-badge></td>
                                <td class="whitespace-nowrap text-right">
                                    <a href="{{ route('sales.receipt', $sale) }}" target="_blank" rel="noopener" class="btn btn-ghost btn-icon">
                                        <x-icon name="printer" class="h-5 w-5" />
                                        <span class="sr-only">Cetak struk {{ $sale->invoice_number }}</span>
                                    </a>
                                    <a href="{{ route('sales.show', $sale) }}" class="btn btn-ghost btn-icon">
                                        <x-icon name="eye" class="h-5 w-5" />
                                        <span class="sr-only">Detail {{ $sale->invoice_number }}</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile cards --}}
            <ul class="divide-y divide-slate-100 md:hidden">
                @foreach ($sales as $sale)
                    <li>
                        <a href="{{ route('sales.show', $sale) }}" class="flex items-start justify-between gap-3 p-4 hover:bg-slate-50">
                            <div class="min-w-0">
                                <p class="truncate font-mono text-sm font-semibold text-slate-900">{{ $sale->invoice_number }}</p>
                                <p class="text-xs text-slate-500">{{ $sale->created_at->translatedFormat('d M Y H:i') }} · {{ $sale->user?->name }}</p>
                                <div class="mt-1.5 flex flex-wrap gap-1">
                                    <x-badge :color="$sale->payment_method->color()">{{ $sale->payment_method->label() }}</x-badge>
                                    <x-badge :color="$sale->status->color()">{{ $sale->status->label() }}</x-badge>
                                </div>
                            </div>
                            <div class="text-right">
                                <p @class(['font-bold tabular-nums', 'text-slate-900' => ! $sale->isVoid(), 'text-slate-500 line-through' => $sale->isVoid()])>{{ rupiah($sale->total) }}</p>
                                <p class="text-xs text-slate-500">{{ $sale->items_count }} produk</p>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="border-t border-slate-100 px-4 py-3">
                @if ($sales->hasPages())
                    {{ $sales->links() }}
                @else
                    <p class="text-sm text-slate-600">Menampilkan {{ $sales->total() }} transaksi</p>
                @endif
            </div>
        @endif
    </x-card>
</x-app-layout>

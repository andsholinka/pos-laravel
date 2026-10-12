@php
    $canVoid = auth()->user()->can('void', $sale);
    $receiptUrl = route('sales.receipt', $sale);
@endphp
<x-app-layout title="Transaksi {{ $sale->invoice_number }}" subtitle="Detail transaksi penjualan.">
    <x-slot name="actions">
        <a href="{{ route('sales.index') }}" class="btn btn-secondary">
            <x-icon name="arrow-left" class="h-4 w-4" />
            Kembali
        </a>
        <button type="button" class="btn btn-secondary"
                onclick="window.open(@js($receiptUrl), 'struk', 'width=420,height=700')">
            <x-icon name="printer" class="h-4 w-4" />
            Cetak Ulang Struk
        </button>
        @if ($canVoid)
            <button type="button" class="btn btn-danger" x-data x-on:click.prevent="$dispatch('open-modal', 'void-sale')">
                <x-icon name="no-symbol" class="h-4 w-4" />
                Batalkan Transaksi
            </button>
        @endif
    </x-slot>

    @if ($sale->isVoid())
        <div class="mb-6 flex items-start gap-3 rounded-2xl bg-rose-50 p-4 text-sm text-rose-800 ring-1 ring-rose-200" role="status">
            <x-icon name="x-circle" class="mt-0.5 h-5 w-5 shrink-0" />
            <div>
                <p class="font-semibold">Transaksi ini telah dibatalkan</p>
                <p class="mt-0.5">
                    {{ $sale->voided_at?->translatedFormat('d M Y H:i') }} oleh {{ $sale->voidedBy?->name ?? '-' }}.
                    Alasan: {{ $sale->void_reason ?: '-' }}. Stok seluruh produk sudah dikembalikan.
                </p>
            </div>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Items --}}
        <x-card title="Daftar Produk" :subtitle="$sale->items->count().' produk · '.$sale->items->sum('quantity').' item'" :padding="false" class="lg:col-span-2">
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th scope="col">Produk</th>
                            <th scope="col" class="text-right">Harga</th>
                            <th scope="col" class="text-right">Jumlah</th>
                            <th scope="col" class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sale->items as $item)
                            <tr>
                                <td>
                                    <span class="font-semibold text-slate-900">{{ $item->product_name }}</span>
                                    <span class="block text-xs text-slate-500">{{ $item->sku }}</span>
                                </td>
                                <td class="whitespace-nowrap text-right tabular-nums">{{ rupiah($item->price) }}</td>
                                <td class="whitespace-nowrap text-right tabular-nums">{{ $item->quantity }} {{ $item->unit }}</td>
                                <td class="whitespace-nowrap text-right font-semibold tabular-nums text-slate-900">{{ rupiah($item->subtotal) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <dl class="space-y-2 border-t border-slate-100 px-4 py-4 text-sm sm:ml-auto sm:max-w-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-600">Subtotal</dt>
                    <dd class="tabular-nums text-slate-900">{{ rupiah($sale->subtotal) }}</dd>
                </div>
                @if ($sale->discount_amount > 0)
                    <div class="flex justify-between">
                        <dt class="text-slate-600">
                            Diskon
                            @if ($sale->discount_type === \App\Enums\DiscountType::Percent)
                                ({{ percent((float) $sale->discount_value, 2) }})
                            @endif
                        </dt>
                        <dd class="tabular-nums text-rose-600">− {{ rupiah($sale->discount_amount) }}</dd>
                    </div>
                @endif
                @if ($sale->tax_amount > 0)
                    <div class="flex justify-between">
                        <dt class="text-slate-600">Pajak ({{ percent((float) $sale->tax_percent, 2) }})</dt>
                        <dd class="tabular-nums text-slate-900">{{ rupiah($sale->tax_amount) }}</dd>
                    </div>
                @endif
                <div class="flex items-end justify-between border-t border-dashed border-slate-300 pt-2">
                    <dt class="font-bold text-slate-900">Total</dt>
                    <dd @class(['text-xl font-extrabold tabular-nums', 'text-slate-900' => ! $sale->isVoid(), 'text-slate-500 line-through' => $sale->isVoid()])>{{ rupiah($sale->total) }}</dd>
                </div>
            </dl>
        </x-card>

        {{-- Info + payment --}}
        <div class="space-y-6">
            <x-card title="Informasi">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">No. Invoice</dt>
                        <dd class="font-mono font-semibold text-slate-900">{{ $sale->invoice_number }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Waktu</dt>
                        <dd class="text-right font-medium text-slate-900">{{ $sale->created_at->translatedFormat('l, d M Y H:i') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Kasir</dt>
                        <dd class="font-medium text-slate-900">{{ $sale->user?->name ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Status</dt>
                        <dd><x-badge :color="$sale->status->color()">{{ $sale->status->label() }}</x-badge></dd>
                    </div>
                    @if ($sale->note)
                        <div>
                            <dt class="text-slate-500">Catatan</dt>
                            <dd class="mt-0.5 text-slate-900">{{ $sale->note }}</dd>
                        </div>
                    @endif
                </dl>
            </x-card>

            <x-card title="Pembayaran">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Metode</dt>
                        <dd>
                            <x-badge :color="$sale->payment_method->color()">
                                <x-icon :name="$sale->payment_method->icon()" class="h-3.5 w-3.5" />
                                {{ $sale->payment_method->label() }}
                            </x-badge>
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Dibayar</dt>
                        <dd class="font-semibold tabular-nums text-slate-900">{{ rupiah($sale->paid_amount) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Kembalian</dt>
                        <dd class="font-semibold tabular-nums text-emerald-700">{{ rupiah($sale->change_amount) }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>
    </div>

    @if ($canVoid)
        <x-modal name="void-sale" :show="$errors->has('reason')" maxWidth="md" focusable>
            <form method="POST" action="{{ route('sales.void', $sale) }}" class="p-6">
                @csrf

                <div class="flex items-start gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                        <x-icon name="exclamation-triangle" class="h-6 w-6" />
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Batalkan transaksi {{ $sale->invoice_number }}?</h2>
                        <p class="mt-1 text-sm text-slate-600">Stok semua produk pada transaksi ini akan dikembalikan dan transaksi tidak dihitung di laporan. Tindakan ini tidak dapat diurungkan.</p>
                    </div>
                </div>

                <div class="mt-5">
                    <x-input-label for="reason" value="Alasan pembatalan" />
                    <x-textarea-input id="reason" name="reason" rows="3" required minlength="5" maxlength="255"
                                      placeholder="Mis. salah input produk" :value="old('reason')" />
                    <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                    <x-danger-button>Batalkan Transaksi</x-danger-button>
                </div>
            </form>
        </x-modal>
    @endif
</x-app-layout>

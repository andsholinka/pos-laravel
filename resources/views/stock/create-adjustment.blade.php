@php($selectedId = (int) old('product_id', $selected))
<x-app-layout title="Penyesuaian Stok" subtitle="Samakan stok sistem dengan hasil hitung fisik (stok opname, barang rusak, kadaluarsa, dll).">
    <form method="POST" action="{{ route('stock.adjustment.store') }}"
          x-data="{
              stock: null, unit: '',
              actual: @js((string) old('actual_stock', '')),
              reason: @js(old('reason', 'stok_opname')),
              sync(el) {
                  const o = el.selectedOptions[0];
                  this.stock = o && o.value ? parseInt(o.dataset.stock, 10) : null;
                  this.unit = o ? (o.dataset.unit || '') : '';
              },
              get delta() {
                  const a = parseInt(this.actual, 10);
                  return this.stock === null || isNaN(a) ? null : a - this.stock;
              },
          }"
          x-init="sync($refs.product)"
          class="grid gap-6 lg:grid-cols-3">
        @csrf
        <x-card title="Data Penyesuaian" class="lg:col-span-2">
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-input-label for="product_id" value="Produk" />
                    <x-select-input id="product_id" name="product_id" required x-ref="product" @change="sync($event.target)">
                        <option value="">Pilih produk</option>
                        @foreach ($products as $p)
                            <option value="{{ $p->id }}" data-stock="{{ $p->stock }}" data-unit="{{ $p->unit }}" data-cost="{{ $p->cost_price }}" @selected($selectedId === $p->id)>
                                {{ $p->name }} ({{ $p->sku }}) · stok {{ $p->stock }}{{ $p->is_active ? '' : ' · nonaktif' }}
                            </option>
                        @endforeach
                    </x-select-input>
                    <x-input-error class="mt-2" :messages="$errors->get('product_id')" />
                </div>
                <div>
                    <x-input-label for="actual_stock" value="Stok Fisik (hasil hitung)" />
                    <x-text-input id="actual_stock" name="actual_stock" type="number" inputmode="numeric" min="0" max="100000" step="1" required x-model="actual" placeholder="0" />
                    <x-input-error class="mt-2" :messages="$errors->get('actual_stock')" />
                </div>
                <div>
                    <x-input-label for="reason" value="Alasan" />
                    <x-select-input id="reason" name="reason" required x-model="reason">
                        @foreach ($reasons as $key => $label)
                            <option value="{{ $key }}" @selected(old('reason', 'stok_opname') === $key)>{{ $label }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error class="mt-2" :messages="$errors->get('reason')" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="note">
                        Catatan <span x-show="reason !== 'lainnya'">(opsional)</span><span x-show="reason === 'lainnya'" x-cloak class="text-rose-600">(wajib)</span>
                    </x-input-label>
                    <x-textarea-input id="note" name="note" rows="2" maxlength="255" :value="old('note')" x-bind:required="reason === 'lainnya'" placeholder="Contoh: Hasil stok opname akhir bulan" />
                    <x-input-error class="mt-2" :messages="$errors->get('note')" />
                </div>
            </div>
        </x-card>

        <div class="space-y-6">
            <x-card title="Pratinjau Stok">
                <dl class="space-y-3 text-sm" aria-live="polite">
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-600">Stok sistem</dt>
                        <dd class="font-semibold tabular-nums text-slate-900"><span x-text="stock ?? '-'"></span> <span x-text="unit"></span></dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-600">Selisih</dt>
                        <dd class="font-semibold tabular-nums"
                            :class="delta > 0 ? 'text-emerald-700' : (delta < 0 ? 'text-rose-700' : 'text-slate-500')"
                            x-text="delta === null ? '-' : (delta > 0 ? '+' + delta : delta)"></dd>
                    </div>
                    <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                        <dt class="font-semibold text-slate-900">Stok sesudah</dt>
                        <dd class="text-xl font-bold tabular-nums text-primary-700"><span x-text="delta === null ? '-' : stock + delta"></span> <span class="text-sm font-medium" x-text="unit"></span></dd>
                    </div>
                </dl>
                <p x-show="delta === 0" x-cloak class="mt-4 rounded-lg bg-amber-50 p-3 text-xs text-amber-800">Stok fisik sama dengan stok sistem, tidak ada yang perlu disesuaikan.</p>
            </x-card>
            <div class="flex justify-end gap-2">
                <a href="{{ route('stock.index') }}" class="btn btn-secondary">Batal</a>
                <x-primary-button>
                    <x-icon name="check" class="h-4 w-4" />
                    Simpan Penyesuaian
                </x-primary-button>
            </div>
        </div>
    </form>
</x-app-layout>

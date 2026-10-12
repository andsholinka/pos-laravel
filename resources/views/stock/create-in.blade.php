@php($selectedId = (int) old('product_id', $selected))
<x-app-layout title="Barang Masuk" subtitle="Catat penerimaan barang dari supplier. Stok bertambah otomatis.">
    <form method="POST" action="{{ route('stock.in.store') }}"
          x-data="{
              stock: null, unit: '', cost: null,
              qty: @js((string) old('quantity', '')),
              sync(el) {
                  const o = el.selectedOptions[0];
                  this.stock = o && o.value ? parseInt(o.dataset.stock, 10) : null;
                  this.unit = o ? (o.dataset.unit || '') : '';
                  this.cost = o && o.value ? parseInt(o.dataset.cost, 10) : null;
              },
              get after() { return this.stock === null ? null : this.stock + (parseInt(this.qty, 10) || 0); },
          }"
          x-init="sync($refs.product)"
          class="grid gap-6 lg:grid-cols-3">
        @csrf
        <x-card title="Data Barang Masuk" class="lg:col-span-2">
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
                    <x-input-label for="quantity" value="Jumlah Masuk" />
                    <x-text-input id="quantity" name="quantity" type="number" inputmode="numeric" min="1" max="100000" step="1" required x-model="qty" placeholder="0" />
                    <x-input-error class="mt-2" :messages="$errors->get('quantity')" />
                </div>
                <div>
                    <x-input-label for="cost_price" value="Harga Beli Baru (opsional)" />
                    <x-money-input id="cost_price" name="cost_price" :value="old('cost_price')" x-bind:placeholder="cost !== null ? cost : ''" aria-describedby="cost-hint" />
                    <p id="cost-hint" class="form-hint">Kosongkan jika harga beli tidak berubah<span x-show="cost !== null" x-cloak> (saat ini <span x-text="rupiah(cost)"></span>)</span>.</p>
                    <x-input-error class="mt-2" :messages="$errors->get('cost_price')" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="note" value="Catatan (opsional)" />
                    <x-textarea-input id="note" name="note" rows="2" maxlength="255" :value="old('note')" placeholder="Contoh: Pembelian dari supplier, nomor nota, dll." />
                    <x-input-error class="mt-2" :messages="$errors->get('note')" />
                </div>
            </div>
        </x-card>

        <div class="space-y-6">
            <x-card title="Pratinjau Stok">
                <dl class="space-y-3 text-sm" aria-live="polite">
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-600">Stok sekarang</dt>
                        <dd class="font-semibold tabular-nums text-slate-900"><span x-text="stock ?? '-'"></span> <span x-text="unit"></span></dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-600">Masuk</dt>
                        <dd class="font-semibold tabular-nums text-emerald-700">+<span x-text="parseInt(qty, 10) || 0"></span></dd>
                    </div>
                    <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                        <dt class="font-semibold text-slate-900">Stok sesudah</dt>
                        <dd class="text-xl font-bold tabular-nums text-primary-700"><span x-text="after ?? '-'"></span> <span class="text-sm font-medium" x-text="unit"></span></dd>
                    </div>
                </dl>
            </x-card>
            <div class="flex justify-end gap-2">
                <a href="{{ route('stock.index') }}" class="btn btn-secondary">Batal</a>
                <x-primary-button>
                    <x-icon name="check" class="h-4 w-4" />
                    Simpan Barang Masuk
                </x-primary-button>
            </div>
        </div>
    </form>
</x-app-layout>

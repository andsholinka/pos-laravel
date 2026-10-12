{{--
    Shared create/edit fields. Expects $product (App\Models\Product) and $categories.
    Stock is only set on create (initial_stock, logged as a stock-in); on edit it is read-only.
--}}
@php($editing = $product->exists)
<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <x-card title="Informasi Produk">
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-input-label for="name" value="Nama Produk" />
                    <x-text-input id="name" name="name" type="text" :value="old('name', $product->name)" required maxlength="150" autofocus placeholder="Contoh: Indomie Goreng" />
                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                </div>
                <div>
                    <x-input-label for="sku" value="SKU" />
                    <x-text-input id="sku" name="sku" type="text" :value="old('sku', $product->sku)" required maxlength="50" placeholder="MKN-001" aria-describedby="sku-hint" />
                    <p id="sku-hint" class="form-hint">Kode unik: huruf, angka, tanda - _ atau titik.</p>
                    <x-input-error class="mt-2" :messages="$errors->get('sku')" />
                </div>
                <div>
                    <x-input-label for="barcode" value="Barcode (opsional)" />
                    <x-text-input id="barcode" name="barcode" type="text" inputmode="numeric" :value="old('barcode', $product->barcode)" maxlength="50" placeholder="Scan atau ketik barcode" />
                    <x-input-error class="mt-2" :messages="$errors->get('barcode')" />
                </div>
                <div>
                    <x-input-label for="category_id" value="Kategori" />
                    <x-select-input id="category_id" name="category_id" required>
                        <option value="">Pilih kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) old('category_id', $product->category_id) === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error class="mt-2" :messages="$errors->get('category_id')" />
                </div>
                <div>
                    <x-input-label for="unit" value="Satuan" />
                    <x-select-input id="unit" name="unit" required>
                        @foreach (\App\Models\Product::UNITS as $unit)
                            <option value="{{ $unit }}" @selected(old('unit', $product->unit) === $unit)>{{ $unit }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error class="mt-2" :messages="$errors->get('unit')" />
                </div>
            </div>
        </x-card>

        <x-card title="Harga & Stok">
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="cost_price" value="Harga Beli (HPP)" />
                    <x-money-input id="cost_price" name="cost_price" :value="old('cost_price', $product->cost_price ?? 0)" required />
                    <x-input-error class="mt-2" :messages="$errors->get('cost_price')" />
                </div>
                <div>
                    <x-input-label for="price" value="Harga Jual" />
                    <x-money-input id="price" name="price" :value="old('price', $product->price)" required />
                    <x-input-error class="mt-2" :messages="$errors->get('price')" />
                </div>
                <div>
                    <x-input-label for="min_stock" value="Stok Minimum" />
                    <x-text-input id="min_stock" name="min_stock" type="number" inputmode="numeric" min="0" max="100000" step="1" :value="old('min_stock', $product->min_stock)" required aria-describedby="min-stock-hint" />
                    <p id="min-stock-hint" class="form-hint">Produk ditandai "menipis" saat stok mencapai angka ini.</p>
                    <x-input-error class="mt-2" :messages="$errors->get('min_stock')" />
                </div>
                @if ($editing)
                    <div>
                        <span class="form-label">Stok Saat Ini</span>
                        <div class="flex items-center gap-3 rounded-lg bg-slate-50 px-3 py-2 ring-1 ring-inset ring-slate-200">
                            <span class="text-lg font-bold tabular-nums text-slate-900">{{ $product->stock }}</span>
                            <span class="text-sm text-slate-500">{{ $product->unit }}</span>
                        </div>
                        <p class="form-hint">
                            Ubah stok lewat
                            <a href="{{ route('stock.in.create', ['product' => $product->id]) }}" class="font-semibold text-primary-700 hover:underline">Barang Masuk</a>
                            atau
                            <a href="{{ route('stock.adjustment.create', ['product' => $product->id]) }}" class="font-semibold text-primary-700 hover:underline">Penyesuaian</a>.
                        </p>
                    </div>
                @else
                    <div>
                        <x-input-label for="initial_stock" value="Stok Awal" />
                        <x-text-input id="initial_stock" name="initial_stock" type="number" inputmode="numeric" min="0" max="100000" step="1" :value="old('initial_stock', 0)" aria-describedby="initial-stock-hint" />
                        <p id="initial-stock-hint" class="form-hint">Dicatat sebagai barang masuk "Stok awal".</p>
                        <x-input-error class="mt-2" :messages="$errors->get('initial_stock')" />
                    </div>
                @endif
            </div>
        </x-card>
    </div>

    <div class="space-y-6">
        <x-card title="Gambar Produk">
            <div x-data="imagePreview(@js($product->image_url))" class="space-y-4">
                <div class="flex aspect-square w-full items-center justify-center overflow-hidden rounded-xl bg-slate-50 ring-1 ring-inset ring-slate-200">
                    <template x-if="url && ! removed">
                        <img :src="url" alt="Pratinjau gambar produk" class="h-full w-full object-cover">
                    </template>
                    <div x-show="! url || removed" class="flex flex-col items-center gap-2 text-slate-400">
                        <x-icon name="photo" class="h-10 w-10" />
                        <span class="text-xs">Belum ada gambar</span>
                    </div>
                </div>
                <div>
                    <x-input-label for="image" value="Unggah gambar" />
                    <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" x-ref="file" @change="pick($event)"
                           aria-describedby="image-hint"
                           class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-primary-700 hover:file:bg-primary-100">
                    <p id="image-hint" class="form-hint">JPG, PNG, atau WEBP, maksimal 2 MB.</p>
                    <x-input-error class="mt-2" :messages="$errors->get('image')" />
                </div>
                @if ($editing && $product->image_path)
                    <x-checkbox id="remove_image" name="remove_image" label="Hapus gambar" hint="Kembali ke ikon inisial produk." x-model="removed" />
                @endif
            </div>
        </x-card>

        <x-card title="Status">
            <x-checkbox id="is_active" name="is_active" label="Produk aktif" hint="Produk nonaktif tidak tampil di layar kasir."
                        :checked="(bool) old('is_active', $product->is_active ?? true)" />
        </x-card>
    </div>
</div>

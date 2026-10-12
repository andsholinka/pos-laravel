{{-- Store settings (admin). The outer imagePreview scope holds the logo; the inner scope binds the fields for the live receipt preview. --}}
<x-app-layout title="Pengaturan Toko" subtitle="Identitas toko, pajak, dan tampilan struk.">
    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data"
          x-data="imagePreview(@js($setting->logo_url))">
        @csrf
        @method('PUT')
        <div class="grid gap-6 xl:grid-cols-3"
             x-data="{
                 name: @js(old('store_name', $setting->store_name)),
                 address: @js(old('address', $setting->address)),
                 phone: @js(old('phone', $setting->phone)),
                 tax: @js((string) old('tax_percent', (float) $setting->tax_percent)),
                 paper: @js((string) old('receipt_paper', $setting->receipt_paper ?: '58')),
                 footer: @js(old('receipt_footer', $setting->receipt_footer)),
                 subtotal: 11000,
                 get taxAmount() { return Math.round(this.subtotal * (parseFloat(this.tax) || 0) / 100); },
                 get total() { return this.subtotal + this.taxAmount; },
             }">
            <div class="space-y-6 xl:col-span-2">
                <x-card title="Informasi Toko" subtitle="Tampil di sidebar, halaman login, layar kasir, dan struk.">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-input-label for="store_name" value="Nama Toko" />
                            <x-text-input id="store_name" name="store_name" type="text" x-model="name" :value="old('store_name', $setting->store_name)" required maxlength="100" />
                            <x-input-error class="mt-2" :messages="$errors->get('store_name')" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="address" value="Alamat" />
                            <x-textarea-input id="address" name="address" rows="2" maxlength="255" x-model="address" :value="old('address', $setting->address)" />
                            <x-input-error class="mt-2" :messages="$errors->get('address')" />
                        </div>
                        <div>
                            <x-input-label for="phone" value="Nomor Telepon" />
                            <x-text-input id="phone" name="phone" type="tel" x-model="phone" :value="old('phone', $setting->phone)" maxlength="30" placeholder="08xx-xxxx-xxxx" />
                            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                        </div>
                        <div class="sm:col-span-2">
                            <span class="form-label">Logo Toko</span>
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                                <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-slate-50 ring-1 ring-inset ring-slate-200">
                                    <template x-if="url && ! removed">
                                        <img :src="url" alt="Pratinjau logo toko" class="h-full w-full object-cover">
                                    </template>
                                    <x-icon name="building-storefront" class="h-8 w-8 text-slate-400" x-show="! url || removed" />
                                </div>
                                <div class="min-w-0 flex-1 space-y-3">
                                    <div>
                                        <label for="logo" class="sr-only">Unggah logo</label>
                                        <input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp" @change="pick($event)" aria-describedby="logo-hint"
                                               class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-primary-700 hover:file:bg-primary-100">
                                        <p id="logo-hint" class="form-hint">JPG, PNG, atau WEBP, maksimal 1 MB. Sebaiknya persegi.</p>
                                        <x-input-error class="mt-2" :messages="$errors->get('logo')" />
                                    </div>
                                    @if ($setting->logo_path)
                                        <x-checkbox id="remove_logo" name="remove_logo" label="Hapus logo" x-model="removed" />
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </x-card>

                <x-card title="Transaksi & Struk">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <x-input-label for="tax_percent" value="Pajak (PPN)" />
                            <div class="relative">
                                <x-text-input id="tax_percent" name="tax_percent" type="number" inputmode="decimal" min="0" max="100" step="0.01" x-model="tax"
                                              :value="old('tax_percent', (float) $setting->tax_percent)" required class="pr-10 tabular-nums" aria-describedby="tax-hint" />
                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-sm font-medium text-slate-500" aria-hidden="true">%</span>
                            </div>
                            <p id="tax-hint" class="form-hint">Isi 0 untuk menonaktifkan pajak.</p>
                            <x-input-error class="mt-2" :messages="$errors->get('tax_percent')" />
                        </div>
                        <fieldset>
                            <legend class="form-label">Ukuran Kertas Struk</legend>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach (['58' => 'Printer kecil', '80' => 'Printer standar'] as $size => $hint)
                                    <label class="flex cursor-pointer flex-col rounded-lg border border-slate-200 px-3 py-2 text-sm transition hover:bg-slate-50 has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-primary-500">
                                        <span class="flex items-center gap-2">
                                            <input type="radio" name="receipt_paper" value="{{ $size }}" x-model="paper" class="h-4 w-4 border-slate-300 text-primary-600 focus:ring-primary-500"
                                                   @checked((string) old('receipt_paper', $setting->receipt_paper ?: '58') === (string) $size)>
                                            <span class="font-semibold text-slate-900">{{ $size }} mm</span>
                                        </span>
                                        <span class="ml-6 text-xs text-slate-500">{{ $hint }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <x-input-error class="mt-2" :messages="$errors->get('receipt_paper')" />
                        </fieldset>
                        <div class="sm:col-span-2">
                            <x-input-label for="receipt_footer" value="Catatan Kaki Struk" />
                            <x-textarea-input id="receipt_footer" name="receipt_footer" rows="3" maxlength="255" x-model="footer" :value="old('receipt_footer', $setting->receipt_footer)" placeholder="Terima kasih telah berbelanja!" />
                            <x-input-error class="mt-2" :messages="$errors->get('receipt_footer')" />
                        </div>
                    </div>
                </x-card>

                <div class="flex justify-end">
                    <x-primary-button>
                        <x-icon name="check" class="h-4 w-4" />
                        Simpan Pengaturan
                    </x-primary-button>
                </div>
            </div>

            {{-- Live mini receipt --}}
            <div>
                <div class="xl:sticky xl:top-24">
                    <p class="mb-3 text-sm font-semibold text-slate-700">Pratinjau Struk <span class="font-normal text-slate-500" x-text="'(' + paper + ' mm)'"></span></p>
                    <div class="rounded-2xl bg-slate-200/70 p-4">
                        <div class="mx-auto bg-white px-4 py-5 font-mono text-[11px] leading-snug text-slate-900 shadow-md transition-all"
                             :class="paper === '80' ? 'max-w-[20rem]' : 'max-w-[15rem]'"
                             aria-label="Pratinjau struk" role="img">
                            <div class="text-center">
                                <template x-if="url && ! removed">
                                    <img :src="url" alt="" class="mx-auto mb-2 h-10 w-auto object-contain grayscale">
                                </template>
                                <p class="font-bold uppercase" x-text="name || 'Nama Toko'"></p>
                                <p class="whitespace-pre-line" x-show="address" x-text="address"></p>
                                <p x-show="phone" x-text="'Telp. ' + phone"></p>
                            </div>
                            <div class="my-2 border-t border-dashed border-slate-900"></div>
                            <div class="flex justify-between"><span>INV-{{ now()->format('Ymd') }}-0001</span></div>
                            <div class="flex justify-between"><span>{{ now()->format('d/m/Y H:i') }}</span><span>{{ auth()->user()->name }}</span></div>
                            <div class="my-2 border-t border-dashed border-slate-900"></div>
                            <p>Indomie Goreng</p>
                            <div class="flex justify-between"><span>2 x 3.500</span><span>7.000</span></div>
                            <p>Aqua 600ml</p>
                            <div class="flex justify-between"><span>1 x 4.000</span><span>4.000</span></div>
                            <div class="my-2 border-t border-dashed border-slate-900"></div>
                            <div class="flex justify-between"><span>Subtotal</span><span x-text="rupiah(subtotal)"></span></div>
                            <div class="flex justify-between" x-show="taxAmount > 0"><span x-text="'Pajak (' + (parseFloat(tax) || 0).toLocaleString('id-ID') + '%)'"></span><span x-text="rupiah(taxAmount)"></span></div>
                            <div class="flex justify-between text-xs font-bold"><span>TOTAL</span><span x-text="rupiah(total)"></span></div>
                            <div class="flex justify-between"><span>Tunai</span><span x-text="rupiah(Math.ceil(total / 5000) * 5000)"></span></div>
                            <div class="flex justify-between"><span>Kembalian</span><span x-text="rupiah(Math.ceil(total / 5000) * 5000 - total)"></span></div>
                            <div class="my-2 border-t border-dashed border-slate-900"></div>
                            <p class="whitespace-pre-line text-center" x-text="footer || ''"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-app-layout>

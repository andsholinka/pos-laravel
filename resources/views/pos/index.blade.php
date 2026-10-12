{{--
    Cashier screen. All state and logic live in resources/js/pos.js (Alpine posApp).
    Shortcuts: F2 search, F4 discount, F8 clear cart, F9 pay, Enter confirm, Esc close/clear.
--}}
@php
    $methods = \App\Enums\PaymentMethod::cases();
    $kbd = 'rounded border border-slate-300 bg-white px-1.5 py-0.5 font-mono text-[11px] font-semibold text-slate-700 shadow-sm';
@endphp
<x-pos-layout>
    <div x-data="posApp(@js($config))"
         x-on:keydown.window="onKeydown($event)"
         class="relative h-full lg:grid lg:grid-cols-[minmax(0,1fr)_400px]">

        {{-- ============ Left panel: search, categories, products ============ --}}
        <section class="flex h-full min-h-0 flex-col" aria-label="Daftar produk">
            <div class="shrink-0 space-y-3 border-b border-slate-200 bg-white p-3 sm:p-4">
                <div class="relative">
                    <label for="pos-search" class="sr-only">Cari produk atau scan barcode</label>
                    <x-icon name="magnifying-glass" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                    <input id="pos-search"
                           x-ref="search"
                           type="search"
                           x-model="query"
                           autofocus
                           autocomplete="off"
                           spellcheck="false"
                           aria-label="Cari produk atau scan barcode"
                           placeholder="Cari nama / SKU / scan barcode… (F2)"
                           x-on:keydown.enter.prevent="onSearchEnter()"
                           x-on:keydown.arrow-down.prevent="moveHighlight(1)"
                           x-on:keydown.arrow-up.prevent="moveHighlight(-1)"
                           class="block w-full rounded-xl border-slate-300 py-3 pl-12 pr-12 text-base text-slate-800 shadow-sm placeholder:text-slate-400 focus:border-primary-500 focus:ring-primary-500 [&::-webkit-search-cancel-button]:hidden">
                    <button type="button"
                            x-show="query"
                            x-cloak
                            x-on:click="query = ''; focusSearch()"
                            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                        <x-icon name="x-mark" class="h-5 w-5" />
                        <span class="sr-only">Hapus pencarian</span>
                    </button>
                </div>

                <div class="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1" role="group" aria-label="Filter kategori">
                    <button type="button"
                            x-on:click="activeCategory = null"
                            :aria-pressed="activeCategory === null"
                            :class="activeCategory === null ? 'bg-primary-600 text-white ring-primary-600' : 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50'"
                            class="inline-flex shrink-0 items-center gap-2 rounded-full px-3.5 py-1.5 text-sm font-semibold ring-1 ring-inset transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                        Semua
                        <span class="rounded-full bg-black/10 px-1.5 text-xs tabular-nums" x-text="products.length"></span>
                    </button>
                    <template x-for="c in categories" :key="c.id">
                        <button type="button"
                                x-on:click="activeCategory = c.id"
                                :aria-pressed="activeCategory === c.id"
                                :class="activeCategory === c.id ? 'bg-primary-600 text-white ring-primary-600' : 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50'"
                                class="inline-flex shrink-0 items-center gap-2 rounded-full px-3.5 py-1.5 text-sm font-semibold ring-1 ring-inset transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                            <span x-text="c.name"></span>
                            <span class="rounded-full px-1.5 text-xs tabular-nums"
                                  :class="activeCategory === c.id ? 'bg-black/10' : c.color_bg + ' ' + c.color_text"
                                  x-text="c.count"></span>
                        </button>
                    </template>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto p-3 pb-24 sm:p-4 sm:pb-24 lg:pb-4">
                <div x-show="filtered.length" class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                    <template x-for="(p, index) in filtered" :key="p.id">
                        <button type="button"
                                :id="'product-' + p.id"
                                x-on:click="pick(p)"
                                :disabled="p.stock <= 0"
                                :aria-label="p.name + ', ' + rupiah(p.price) + ', ' + stockBadge(p).text"
                                :class="showHighlight && index === highlight ? 'ring-2 ring-primary-500' : 'ring-1 ring-slate-200'"
                                class="group relative flex flex-col overflow-hidden rounded-2xl bg-white text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0 disabled:hover:shadow-sm">
                            <div class="relative h-20 w-full sm:h-24">
                                <template x-if="p.image_url">
                                    <img :src="p.image_url" alt="" loading="lazy" class="h-full w-full object-cover">
                                </template>
                                <template x-if="!p.image_url">
                                    <div class="flex h-full w-full items-center justify-center text-2xl font-extrabold tracking-tight"
                                         :class="p.color_bg + ' ' + p.color_text"
                                         x-text="p.initials"></div>
                                </template>
                                <span x-show="qtyInCart(p.id) > 0"
                                      class="absolute right-2 top-2 rounded-full bg-primary-600 px-2 py-0.5 text-xs font-bold text-white shadow"
                                      x-text="qtyInCart(p.id) + ' di keranjang'"></span>
                            </div>
                            <div class="flex flex-1 flex-col gap-0.5 p-3">
                                <p class="line-clamp-2 text-sm font-semibold leading-snug text-slate-900" x-text="p.name"></p>
                                <p class="text-xs text-slate-500" x-text="p.sku"></p>
                                <div class="mt-auto flex flex-wrap items-center justify-between gap-1 pt-2">
                                    <span class="text-sm font-bold tabular-nums text-primary-700" x-text="rupiah(p.price)"></span>
                                    <span class="whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset"
                                          :class="stockBadge(p).cls"
                                          x-text="stockBadge(p).text"></span>
                                </div>
                            </div>
                        </button>
                    </template>
                </div>

                <div x-show="!filtered.length" x-cloak class="flex flex-col items-center justify-center px-6 py-16 text-center">
                    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-200 text-slate-500">
                        <x-icon name="magnifying-glass" class="h-7 w-7" />
                    </span>
                    <p class="mt-4 font-semibold text-slate-900">Produk tidak ditemukan</p>
                    <p class="mt-1 text-sm text-slate-600">Coba kata kunci lain, scan ulang barcode, atau pilih kategori "Semua".</p>
                </div>
            </div>

            <div class="hidden shrink-0 flex-wrap items-center gap-x-5 gap-y-1 border-t border-slate-200 bg-white px-4 py-2 text-xs text-slate-600 lg:flex" aria-label="Pintasan keyboard">
                <span><kbd class="{{ $kbd }}">F2</kbd> Cari</span>
                <span><kbd class="{{ $kbd }}">↑</kbd> <kbd class="{{ $kbd }}">↓</kbd> Pilih</span>
                <span><kbd class="{{ $kbd }}">Enter</kbd> Tambah</span>
                <span><kbd class="{{ $kbd }}">F4</kbd> Diskon</span>
                <span><kbd class="{{ $kbd }}">F8</kbd> Kosongkan</span>
                <span><kbd class="{{ $kbd }}">F9</kbd> Bayar</span>
                <span><kbd class="{{ $kbd }}">Esc</kbd> Tutup / hapus cari</span>
            </div>
        </section>

        {{-- ============ Right panel: cart (bottom sheet on mobile) ============ --}}
        <div x-show="showCartMobile" x-cloak x-transition.opacity
             x-on:click="showCartMobile = false"
             class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden" aria-hidden="true"></div>

        <aside id="pos-cart"
               aria-label="Keranjang belanja"
               x-cloak
               :class="showCartMobile ? 'translate-y-0' : 'translate-y-full'"
               class="fixed inset-x-0 bottom-0 z-40 flex max-h-[85vh] flex-col rounded-t-2xl bg-white shadow-2xl transition-transform duration-200 lg:static lg:z-auto lg:h-full lg:max-h-none lg:translate-y-0 lg:rounded-none lg:border-l lg:border-slate-200 lg:shadow-none">
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Keranjang</h2>
                    <p class="text-xs text-slate-500"><span x-text="itemCount"></span> item · <span x-text="cart.length"></span> produk</p>
                </div>
                <div class="flex items-center gap-1">
                    <button type="button" x-on:click="clear()" :disabled="!cart.length"
                            class="btn btn-ghost btn-sm text-rose-600 hover:bg-rose-50 hover:text-rose-700">
                        <x-icon name="trash" class="h-4 w-4" />
                        Kosongkan <span class="hidden lg:inline">(F8)</span>
                    </button>
                    <button type="button" x-on:click="showCartMobile = false" class="btn btn-ghost btn-icon lg:hidden">
                        <x-icon name="x-mark" class="h-5 w-5" />
                        <span class="sr-only">Tutup keranjang</span>
                    </button>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto">
                <div x-show="!cart.length" class="flex flex-col items-center justify-center px-6 py-12 text-center">
                    <span class="relative flex h-20 w-20 items-center justify-center rounded-full bg-primary-50 text-primary-600">
                        <x-icon name="shopping-cart" class="h-10 w-10" />
                    </span>
                    <p class="mt-4 font-semibold text-slate-900">Keranjang masih kosong</p>
                    <p class="mt-1 max-w-xs text-sm text-slate-600">Klik produk, ketik nama, atau scan barcode untuk mulai transaksi.</p>
                </div>

                <ul x-show="cart.length" x-cloak class="divide-y divide-slate-100">
                    <template x-for="item in cart" :key="item.id">
                        <li class="px-4 py-3">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900" x-text="item.name"></p>
                                    <p class="text-xs text-slate-500" x-text="rupiah(item.price) + ' / ' + item.unit"></p>
                                </div>
                                <button type="button" x-on:click="remove(item.id)"
                                        class="-mr-2 -mt-1 rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                                        :aria-label="'Hapus ' + item.name">
                                    <x-icon name="trash" class="h-4 w-4" />
                                </button>
                            </div>
                            <div class="mt-2 flex items-center justify-between gap-3">
                                <div class="inline-flex items-center overflow-hidden rounded-lg ring-1 ring-slate-300">
                                    <button type="button" x-on:click="dec(item.id)" :disabled="item.qty <= 1"
                                            class="flex h-8 w-8 items-center justify-center text-slate-600 hover:bg-slate-100 focus:outline-none focus-visible:bg-slate-100 disabled:opacity-40"
                                            :aria-label="'Kurangi ' + item.name">
                                        <x-icon name="minus" class="h-4 w-4" />
                                    </button>
                                    <input type="number" min="1" :max="item.stock" step="1" inputmode="numeric"
                                           :value="item.qty"
                                           :aria-label="'Jumlah ' + item.name"
                                           x-on:change="setQty(item.id, $event.target.value); $event.target.value = item.qty"
                                           x-on:keydown.enter.prevent.stop="$event.target.blur()"
                                           class="h-8 w-12 border-0 border-x border-slate-300 p-0 text-center text-sm font-semibold tabular-nums text-slate-900 focus:ring-2 focus:ring-inset focus:ring-primary-500 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                                    <button type="button" x-on:click="inc(item.id)" :disabled="item.qty >= item.stock"
                                            class="flex h-8 w-8 items-center justify-center text-slate-600 hover:bg-slate-100 focus:outline-none focus-visible:bg-slate-100 disabled:opacity-40"
                                            :aria-label="'Tambah ' + item.name">
                                        <x-icon name="plus" class="h-4 w-4" />
                                    </button>
                                </div>
                                <span class="text-sm font-bold tabular-nums text-slate-900" x-text="rupiah(item.price * item.qty)"></span>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>

            <div class="shrink-0 space-y-3 border-t border-slate-200 bg-slate-50 p-4">
                <div>
                    <label for="pos-discount" class="form-label">Diskon <span class="font-normal text-slate-500">(F4)</span></label>
                    <div class="flex gap-2">
                        <div class="inline-flex shrink-0 rounded-lg bg-white p-0.5 ring-1 ring-inset ring-slate-300" role="group" aria-label="Jenis diskon">
                            <button type="button" x-on:click="discountType = 'nominal'"
                                    :aria-pressed="discountType === 'nominal'"
                                    :class="discountType === 'nominal' ? 'bg-primary-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
                                    class="rounded-md px-3 py-1.5 text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">Rp</button>
                            <button type="button" x-on:click="discountType = 'percent'"
                                    :aria-pressed="discountType === 'percent'"
                                    :class="discountType === 'percent' ? 'bg-primary-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
                                    class="rounded-md px-3 py-1.5 text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">%</button>
                        </div>
                        <input id="pos-discount" x-ref="discount" type="number" min="0" step="any"
                               :max="discountType === 'percent' ? 100 : null"
                               inputmode="decimal" placeholder="0"
                               x-model.number="discountValue"
                               class="form-input tabular-nums">
                    </div>
                </div>

                <dl class="space-y-1.5 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-slate-600">Subtotal</dt>
                        <dd class="font-medium tabular-nums text-slate-900" x-text="rupiah(subtotal)"></dd>
                    </div>
                    <div class="flex justify-between" x-show="discountAmount > 0" x-cloak>
                        <dt class="text-slate-600">Diskon <span x-show="discountType === 'percent'" x-text="'(' + Math.min(discountValue, 100) + '%)'"></span></dt>
                        <dd class="font-medium tabular-nums text-rose-600" x-text="'− ' + rupiah(discountAmount)"></dd>
                    </div>
                    <div class="flex justify-between" x-show="taxPercent > 0" x-cloak>
                        <dt class="text-slate-600" x-text="taxLabel"></dt>
                        <dd class="font-medium tabular-nums text-slate-900" x-text="rupiah(taxAmount)"></dd>
                    </div>
                    <div class="flex items-end justify-between border-t border-dashed border-slate-300 pt-2">
                        <dt class="text-base font-bold text-slate-900">Total</dt>
                        <dd class="text-2xl font-extrabold tabular-nums text-slate-900" x-text="rupiah(total)"></dd>
                    </div>
                </dl>

                <button type="button" x-on:click="openPayment()" :disabled="!cart.length"
                        class="btn btn-primary w-full py-3.5 text-base">
                    <x-icon name="banknotes" class="h-5 w-5" />
                    Bayar
                    <span class="rounded bg-white/20 px-1.5 py-0.5 text-xs">F9</span>
                </button>
            </div>
        </aside>

        {{-- Mobile bottom bar --}}
        <div class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white p-3 shadow-[0_-4px_12px_rgba(15,23,42,0.06)] lg:hidden">
            <div class="flex items-center gap-3">
                <button type="button" x-on:click="showCartMobile = true"
                        aria-controls="pos-cart" :aria-expanded="showCartMobile"
                        class="flex min-w-0 flex-1 items-center gap-3 rounded-xl p-1 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                    <span class="relative flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-700">
                        <x-icon name="shopping-cart" class="h-6 w-6" />
                        <span x-show="itemCount > 0" x-cloak
                              class="absolute -right-1.5 -top-1.5 min-w-[1.25rem] rounded-full bg-primary-600 px-1 text-center text-xs font-bold text-white"
                              x-text="itemCount"></span>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-xs text-slate-500">Lihat keranjang · Total</span>
                        <span class="block truncate text-lg font-extrabold tabular-nums text-slate-900" x-text="rupiah(total)"></span>
                    </span>
                </button>
                <button type="button" x-on:click="openPayment()" :disabled="!cart.length" class="btn btn-primary px-6 py-3 text-base">
                    Bayar
                </button>
            </div>
        </div>

        {{-- ============ Payment modal ============ --}}
        <div x-show="showPayment" x-cloak class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:py-12">
            <div x-show="showPayment" x-transition.opacity x-on:click="closePayment()"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" aria-hidden="true"></div>

            <div x-ref="paymentDialog"
                 x-show="showPayment"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 role="dialog" aria-modal="true" aria-labelledby="payment-title"
                 x-on:keydown.tab="trapFocus($event, $refs.paymentDialog)"
                 class="relative mx-auto w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 id="payment-title" class="text-lg font-bold text-slate-900">Pembayaran</h2>
                    <button type="button" x-on:click="closePayment()" class="btn btn-ghost btn-icon -mr-2">
                        <x-icon name="x-mark" class="h-5 w-5" />
                        <span class="sr-only">Tutup</span>
                    </button>
                </div>

                <div class="space-y-5 px-5 py-5">
                    <div class="rounded-2xl bg-gradient-to-br from-primary-600 to-primary-700 px-5 py-4 text-white">
                        <p class="text-sm text-primary-100">Total Belanja · <span x-text="itemCount"></span> item</p>
                        <p class="mt-1 text-3xl font-extrabold tabular-nums sm:text-4xl" x-text="rupiah(total)"></p>
                    </div>

                    <fieldset>
                        <legend class="form-label">Metode Pembayaran</legend>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach ($methods as $method)
                                <label class="flex cursor-pointer flex-col items-center gap-1.5 rounded-xl px-2 py-3 text-sm font-semibold transition has-[:focus-visible]:outline has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-primary-500"
                                       :class="paymentMethod === '{{ $method->value }}' ? 'bg-primary-50 text-primary-700 ring-2 ring-primary-500' : 'bg-white text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50'">
                                    <input type="radio" name="payment_method" value="{{ $method->value }}" class="sr-only"
                                           :checked="paymentMethod === '{{ $method->value }}'"
                                           x-on:change="selectMethod('{{ $method->value }}')">
                                    <x-icon :name="$method->icon()" class="h-6 w-6" />
                                    {{ $method->label() }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div x-show="paymentMethod === 'tunai'" class="space-y-3">
                        <div>
                            <label for="pos-cash" class="form-label">Uang Diterima</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-lg font-semibold text-slate-500">Rp</span>
                                <input id="pos-cash" x-ref="cash" type="text" inputmode="numeric" autocomplete="off" placeholder="0"
                                       :value="paidAmount"
                                       x-on:input="onCashInput($event)"
                                       class="block w-full rounded-xl border-slate-300 py-3 pl-12 text-2xl font-bold tabular-nums text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2" role="group" aria-label="Uang cepat">
                            <template x-for="v in quickCash" :key="v">
                                <button type="button" x-on:click="setCash(v)"
                                        :class="paid === v ? 'bg-primary-600 text-white ring-primary-600' : 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50'"
                                        class="rounded-lg px-3 py-2 text-sm font-semibold tabular-nums ring-1 ring-inset transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                                        x-text="quickCashLabel(v)"></button>
                            </template>
                        </div>
                        <div class="flex items-center justify-between rounded-xl px-4 py-3"
                             :class="change >= 0 ? 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'"
                             aria-live="polite">
                            <span class="text-sm font-semibold" x-text="change >= 0 ? 'Kembalian' : 'Kurang'"></span>
                            <span class="text-2xl font-extrabold tabular-nums" x-text="rupiah(Math.abs(change))"></span>
                        </div>
                    </div>

                    <div x-show="paymentMethod !== 'tunai'" x-cloak class="flex items-start gap-3 rounded-xl bg-sky-50 px-4 py-3 text-sm text-sky-800 ring-1 ring-sky-200">
                        <x-icon name="information-circle" class="mt-0.5 h-5 w-5 shrink-0" />
                        <p>Pastikan pembayaran <strong x-text="paymentMethod === 'qris' ? 'QRIS' : 'transfer'"></strong> sebesar <strong x-text="rupiah(total)"></strong> sudah diterima sebelum memproses.</p>
                    </div>

                    <div>
                        <label for="pos-note" class="form-label">Catatan <span class="font-normal text-slate-500">(opsional)</span></label>
                        <input id="pos-note" type="text" maxlength="255" x-model="note" placeholder="Mis. nama pelanggan" class="form-input">
                    </div>

                    <div x-show="errors.length" x-cloak role="alert" class="flex items-start gap-3 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200">
                        <x-icon name="exclamation-triangle" class="mt-0.5 h-5 w-5 shrink-0" />
                        <ul class="space-y-0.5">
                            <template x-for="(message, i) in errors" :key="i">
                                <li x-text="message"></li>
                            </template>
                        </ul>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50 px-5 py-4 sm:flex-row sm:justify-end">
                    <button type="button" x-on:click="closePayment()" :disabled="processing" class="btn btn-secondary">Batal (Esc)</button>
                    <button type="button" x-ref="payButton" x-on:click="submit()" :disabled="!canPay" class="btn btn-primary px-6 py-3 text-base">
                        <svg x-show="processing" x-cloak class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <x-icon x-show="!processing" name="check" class="h-5 w-5" />
                        <span x-text="processing ? 'Memproses…' : 'Proses Pembayaran (Enter)'">Proses Pembayaran (Enter)</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ============ Success modal ============ --}}
        <div x-show="showSuccess" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div x-show="showSuccess" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" aria-hidden="true"></div>

            <div x-ref="successDialog"
                 x-show="showSuccess"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 role="dialog" aria-modal="true" aria-labelledby="success-title"
                 x-on:keydown.tab="trapFocus($event, $refs.successDialog)"
                 class="relative w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-xl ring-1 ring-slate-200">
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                    <x-icon name="check-circle" class="h-10 w-10" />
                </span>
                <h2 id="success-title" class="mt-4 text-lg font-bold text-slate-900">Transaksi Berhasil</h2>
                <p class="mt-0.5 font-mono text-sm text-slate-500" x-text="lastSale?.sale?.invoice_number"></p>

                <dl class="mt-5 space-y-2 rounded-xl bg-slate-50 p-4 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-slate-600">Total</dt>
                        <dd class="font-semibold tabular-nums text-slate-900" x-text="rupiah(lastSale?.sale?.total)"></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-600">Metode</dt>
                        <dd class="font-semibold text-slate-900" x-text="lastSale?.sale?.payment_label"></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-600">Dibayar</dt>
                        <dd class="font-semibold tabular-nums text-slate-900" x-text="rupiah(lastSale?.sale?.paid_amount)"></dd>
                    </div>
                    <div class="flex items-end justify-between border-t border-dashed border-slate-300 pt-2">
                        <dt class="font-semibold text-slate-700">Kembalian</dt>
                        <dd class="text-2xl font-extrabold tabular-nums text-emerald-600" x-text="rupiah(lastSale?.sale?.change_amount)"></dd>
                    </div>
                </dl>

                <div class="mt-5 grid gap-2">
                    <button type="button" x-ref="newTransaction" x-on:click="newTransaction()" class="btn btn-primary py-3 text-base">
                        <x-icon name="plus" class="h-5 w-5" />
                        Transaksi Baru (Enter)
                    </button>
                    <button type="button" x-on:click="printReceipt()" class="btn btn-secondary py-2.5">
                        <x-icon name="printer" class="h-5 w-5" />
                        Cetak Struk
                    </button>
                    <a :href="salesIndexUrl" class="mt-1 text-sm font-medium text-primary-700 hover:text-primary-800">Lihat riwayat transaksi</a>
                </div>
            </div>
        </div>

        {{-- Toast (live region always present; content toggles) --}}
        <div class="pointer-events-none fixed inset-x-0 bottom-24 z-[60] flex justify-center px-4 lg:bottom-14" role="status" aria-live="polite">
            <div x-show="toast" x-cloak x-transition.opacity
                 class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold text-white shadow-lg"
                 :class="toast?.type === 'error' ? 'bg-rose-600' : 'bg-slate-900'">
                <x-icon name="information-circle" class="h-4 w-4" />
                <span x-text="toast?.message"></span>
            </div>
        </div>
    </div>
</x-pos-layout>

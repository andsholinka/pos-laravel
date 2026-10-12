/**
 * Alpine component for the cashier (POS) screen: x-data="posApp(@js($config))".
 *
 * Products are loaded once and filtered client-side. Totals use the same rounding as
 * CheckoutService (server is still the source of truth: it recomputes everything).
 */

const MAX_RENDERED = 60;
const CASH_NOTES = [5000, 10000, 20000, 50000, 100000];
const LG_QUERY = '(min-width: 1024px)';

export default function posApp(config) {
    // Kept outside the reactive object on purpose (timers are not state).
    let toastTimer = null;

    return {
        products: config.products,
        categories: config.categories,
        taxPercent: Number(config.taxPercent) || 0,
        checkoutUrl: config.checkoutUrl,
        csrf: config.csrf,
        salesIndexUrl: config.salesIndexUrl,

        activeCategory: null,
        query: '',
        highlight: 0,
        keyboardNav: false,
        cart: [],
        discountType: 'nominal',
        discountValue: 0,
        paymentMethod: 'tunai',
        paidAmount: '',
        note: '',
        showPayment: false,
        showSuccess: false,
        showCartMobile: false,
        processing: false,
        errors: [],
        lastSale: null,
        toast: null,

        init() {
            this.$watch('query', () => {
                this.highlight = 0;
                this.keyboardNav = false;
            });
            this.$watch('activeCategory', () => {
                this.highlight = 0;
            });
            this.$nextTick(() => this.focusSearch());
        },

        // ---- Getters -------------------------------------------------------------------

        get filtered() {
            const q = this.query.trim().toLowerCase();

            return this.products
                .filter((p) => this.activeCategory === null || p.category_id === this.activeCategory)
                .filter((p) => q === ''
                    || p.name.toLowerCase().includes(q)
                    || p.sku.toLowerCase().includes(q)
                    || (p.barcode && p.barcode.toLowerCase().includes(q)))
                .slice(0, MAX_RENDERED);
        },

        get showHighlight() {
            return this.query.trim() !== '' || this.keyboardNav;
        },

        get subtotal() {
            return this.cart.reduce((sum, i) => sum + i.price * i.qty, 0);
        },

        get discountAmount() {
            const value = Math.max(0, Number(this.discountValue) || 0);
            if (value <= 0) {
                return 0;
            }
            if (this.discountType === 'percent') {
                return Math.round(this.subtotal * Math.min(value, 100) / 100);
            }

            return Math.min(Math.round(value), this.subtotal);
        },

        get taxAmount() {
            return Math.round((this.subtotal - this.discountAmount) * this.taxPercent / 100);
        },

        get taxLabel() {
            return `Pajak (${String(this.taxPercent).replace('.', ',')}%)`;
        },

        get total() {
            return this.subtotal - this.discountAmount + this.taxAmount;
        },

        get paid() {
            return parseInt(String(this.paidAmount).replace(/\D/g, ''), 10) || 0;
        },

        get change() {
            return this.paid - this.total;
        },

        get itemCount() {
            return this.cart.reduce((sum, i) => sum + i.qty, 0);
        },

        get canPay() {
            return this.cart.length > 0
                && !this.processing
                && (this.paymentMethod !== 'tunai' || this.paid >= this.total);
        },

        get quickCash() {
            const total = this.total;
            const values = [total, ...CASH_NOTES.map((x) => Math.ceil(total / x) * x)];

            return [...new Set(values)].filter((v) => v > 0).sort((a, b) => a - b).slice(0, 5);
        },

        get anyModalOpen() {
            return this.showPayment || this.showSuccess;
        },

        // ---- Formatting ----------------------------------------------------------------

        rupiah(n) {
            return window.rupiah(n);
        },

        qtyInCart(id) {
            return this.cart.find((i) => i.id === id)?.qty ?? 0;
        },

        stockBadge(p) {
            if (p.stock <= 0) {
                return { text: 'Habis', cls: 'bg-rose-50 text-rose-700 ring-rose-600/20' };
            }
            if (p.stock <= p.min_stock) {
                return { text: `Sisa ${p.stock}`, cls: 'bg-amber-50 text-amber-800 ring-amber-600/20' };
            }

            return { text: `Stok ${p.stock}`, cls: 'bg-slate-100 text-slate-600 ring-slate-500/20' };
        },

        // ---- Cart ----------------------------------------------------------------------

        add(p) {
            if (p.stock <= 0) {
                this.notify(`Stok ${p.name} habis`, 'error');
                return;
            }

            const line = this.cart.find((i) => i.id === p.id);
            if (line) {
                if (line.qty + 1 > p.stock) {
                    this.notify(`Stok ${p.name} tidak mencukupi (sisa ${p.stock})`, 'error');
                    return;
                }
                line.qty++;
            } else {
                this.cart.push({ id: p.id, name: p.name, sku: p.sku, unit: p.unit, price: p.price, stock: p.stock, qty: 1 });
            }
        },

        /** Tile click: add, and on desktop keep the search focused for the next scan. */
        pick(p) {
            this.add(p);
            if (window.matchMedia(LG_QUERY).matches) {
                this.focusSearch();
            }
        },

        inc(id) {
            const line = this.cart.find((i) => i.id === id);
            if (!line) {
                return;
            }
            if (line.qty + 1 > line.stock) {
                this.notify(`Stok ${line.name} tidak mencukupi (sisa ${line.stock})`, 'error');
                return;
            }
            line.qty++;
        },

        dec(id) {
            const line = this.cart.find((i) => i.id === id);
            if (line && line.qty > 1) {
                line.qty--;
            }
        },

        setQty(id, value) {
            const line = this.cart.find((i) => i.id === id);
            if (!line) {
                return;
            }

            const n = parseInt(value, 10);
            if (Number.isNaN(n) || n <= 0) {
                this.remove(id);
                return;
            }
            if (n > line.stock) {
                this.notify(`Stok ${line.name} tidak mencukupi (sisa ${line.stock})`, 'error');
            }
            line.qty = Math.min(n, line.stock);
        },

        remove(id) {
            this.cart = this.cart.filter((i) => i.id !== id);
        },

        clear() {
            if (this.cart.length === 0) {
                return;
            }
            if (confirm('Kosongkan keranjang?')) {
                this.resetCart();
                this.focusSearch();
            }
        },

        resetCart() {
            this.cart = [];
            this.discountValue = 0;
            this.discountType = 'nominal';
            this.note = '';
            this.paidAmount = '';
            this.errors = [];
        },

        // ---- Search / keyboard ---------------------------------------------------------

        focusSearch() {
            this.$refs.search?.focus();
        },

        focusDiscount() {
            if (!window.matchMedia(LG_QUERY).matches) {
                this.showCartMobile = true;
            }
            this.$nextTick(() => {
                this.$refs.discount?.focus();
                this.$refs.discount?.select();
            });
        },

        /**
         * Enter in the search box. Barcode scanners type the code and press Enter, so an
         * exact barcode/SKU match wins; otherwise the highlighted result is added.
         */
        onSearchEnter() {
            const q = this.query.trim().toLowerCase();
            const exact = q === ''
                ? null
                : this.products.find((p) => (p.barcode && p.barcode.toLowerCase() === q) || p.sku.toLowerCase() === q);

            if (exact) {
                this.add(exact);
                this.query = '';
            } else if (this.showHighlight && this.filtered[this.highlight]) {
                this.add(this.filtered[this.highlight]);
                this.query = '';
            } else if (q !== '') {
                this.notify('Produk tidak ditemukan', 'error');
            }

            this.focusSearch();
        },

        moveHighlight(step) {
            const count = this.filtered.length;
            if (count === 0) {
                return;
            }
            // The first arrow press only reveals the highlight on the first tile.
            this.highlight = this.showHighlight ? (this.highlight + step + count) % count : 0;
            this.keyboardNav = true;

            const id = this.filtered[this.highlight]?.id;
            this.$nextTick(() => document.getElementById(`product-${id}`)?.scrollIntoView({ block: 'nearest' }));
        },

        onKeydown(e) {
            switch (e.key) {
                case 'F2':
                    e.preventDefault();
                    if (!this.anyModalOpen) {
                        this.showCartMobile = false;
                        this.focusSearch();
                    }
                    break;
                case 'F4':
                    e.preventDefault();
                    if (!this.anyModalOpen) {
                        this.focusDiscount();
                    }
                    break;
                case 'F8':
                    e.preventDefault();
                    if (!this.anyModalOpen) {
                        this.clear();
                    }
                    break;
                case 'F9':
                    e.preventDefault();
                    if (!this.anyModalOpen) {
                        this.openPayment();
                    }
                    break;
                case 'Escape':
                    if (this.showSuccess) {
                        this.newTransaction();
                    } else if (this.showPayment) {
                        this.closePayment();
                    } else if (this.showCartMobile) {
                        this.showCartMobile = false;
                    } else {
                        this.query = '';
                        this.focusSearch();
                    }
                    break;
                case 'Enter':
                    // A focused button (Batal, quick cash, Cetak Struk, ...) handles Enter natively.
                    if (!this.anyModalOpen || e.target instanceof HTMLButtonElement) {
                        return;
                    }
                    e.preventDefault();
                    if (this.showSuccess) {
                        this.newTransaction();
                    } else {
                        this.submit();
                    }
                    break;
            }
        },

        /** Keep Tab focus inside an open dialog. */
        trapFocus(e, container) {
            const focusables = [...container.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), textarea, select, [tabindex]:not([tabindex="-1"])')]
                .filter((el) => el.offsetParent !== null);
            if (focusables.length === 0) {
                return;
            }

            const first = focusables[0];
            const last = focusables[focusables.length - 1];
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        },

        // ---- Payment -------------------------------------------------------------------

        openPayment() {
            if (this.cart.length === 0) {
                this.notify('Keranjang masih kosong', 'error');
                this.focusSearch();
                return;
            }

            this.paidAmount = '';
            this.errors = [];
            this.showCartMobile = false;
            this.showPayment = true;
            this.focusPaymentField();
        },

        closePayment() {
            if (this.processing) {
                return;
            }
            this.showPayment = false;
            this.focusSearch();
        },

        selectMethod(method) {
            this.paymentMethod = method;
            this.errors = [];
            this.focusPaymentField();
        },

        focusPaymentField() {
            this.$nextTick(() => {
                if (this.paymentMethod === 'tunai') {
                    this.$refs.cash?.focus();
                } else {
                    this.$refs.payButton?.focus();
                }
            });
        },

        onCashInput(e) {
            const digits = e.target.value.replace(/\D/g, '').replace(/^0+(?=\d)/, '');
            this.paidAmount = digits === '' ? '' : new Intl.NumberFormat('id-ID').format(Number(digits));
            e.target.value = this.paidAmount;
        },

        quickCashLabel(value) {
            return value === this.total ? 'Uang Pas' : this.rupiah(value);
        },

        setCash(value) {
            this.paidAmount = new Intl.NumberFormat('id-ID').format(value);
            this.$refs.cash?.focus();
        },

        async submit() {
            if (!this.canPay) {
                if (!this.processing && this.paymentMethod === 'tunai' && this.paid < this.total) {
                    this.errors = ['Uang diterima kurang dari total belanja.'];
                }
                return;
            }

            this.processing = true;
            this.errors = [];

            const payload = {
                items: this.cart.map((i) => ({ product_id: i.id, quantity: i.qty })),
                discount_type: this.discountAmount > 0 ? this.discountType : null,
                discount_value: this.discountAmount > 0 ? Number(this.discountValue) || 0 : 0,
                payment_method: this.paymentMethod,
                paid_amount: this.paymentMethod === 'tunai' ? this.paid : null,
                note: this.note.trim() || null,
            };

            try {
                const res = await fetch(this.checkoutUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });

                let data = {};
                try {
                    data = await res.json();
                } catch {
                    data = {};
                }

                if (res.status === 201) {
                    this.onCheckoutSuccess(data);
                } else if (res.status === 422) {
                    const messages = Object.values(data.errors || {}).flat();
                    this.errors = messages.length ? messages : [data.message || 'Data transaksi tidak valid.'];
                } else if (res.status === 419) {
                    this.errors = ['Sesi berakhir, silakan muat ulang halaman.'];
                } else {
                    this.errors = ['Terjadi kesalahan, coba lagi.'];
                }
            } catch {
                this.errors = ['Terjadi kesalahan, coba lagi.'];
            } finally {
                this.processing = false;
            }
        },

        onCheckoutSuccess(data) {
            const stocks = data.stocks || {};
            this.products.forEach((p) => {
                if (stocks[p.id] !== undefined) {
                    p.stock = Number(stocks[p.id]);
                }
            });

            this.lastSale = data;
            this.resetCart();
            this.showPayment = false;
            this.showSuccess = true;
            this.$nextTick(() => this.$refs.newTransaction?.focus());
        },

        printReceipt() {
            if (this.lastSale?.receipt_url) {
                window.open(this.lastSale.receipt_url, 'struk', 'width=420,height=700');
            }
        },

        newTransaction() {
            this.showSuccess = false;
            this.lastSale = null;
            this.query = '';
            this.$nextTick(() => this.focusSearch());
        },

        // ---- Toast ---------------------------------------------------------------------

        notify(message, type = 'info') {
            this.toast = { message, type };
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => {
                this.toast = null;
            }, 2500);
        },
    };
}

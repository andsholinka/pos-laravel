import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import posApp from './pos';

window.Alpine = Alpine;
// Expose Chart.js globally so Blade views (dashboard/reports) can render charts.
window.Chart = Chart;

// Rupiah formatter shared by Alpine components and chart tooltips: 15000 -> "Rp 15.000".
window.rupiah = (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n || 0));

// Live preview for an <input type="file"> image (product image, store logo).
// Usage: x-data="imagePreview('current-url-or-null')" + @change="pick($event)" on the input.
Alpine.data('imagePreview', (initialUrl = null) => ({
    url: initialUrl,
    removed: false,
    pick(event) {
        const file = event.target.files?.[0];
        if (this.url && this.url.startsWith('blob:')) {
            URL.revokeObjectURL(this.url);
        }
        this.url = file ? URL.createObjectURL(file) : initialUrl;
        if (file) {
            this.removed = false;
        }
    },
}));

// Cashier screen (resources/views/pos/index.blade.php).
Alpine.data('posApp', posApp);

// Register Alpine.data components above this line; start() must run last.
Alpine.start();

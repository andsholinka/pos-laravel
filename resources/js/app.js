import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
// Expose Chart.js globally so Blade views (dashboard/reports) can render charts.
window.Chart = Chart;

// Rupiah formatter shared by Alpine components and chart tooltips: 15000 -> "Rp 15.000".
window.rupiah = (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n || 0));

// Register Alpine.data components above this line; start() must run last.
Alpine.start();

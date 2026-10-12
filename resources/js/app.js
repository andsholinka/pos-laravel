import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
// Expose Chart.js globally so Blade views (dashboard/reports) can render charts.
window.Chart = Chart;

Alpine.start();

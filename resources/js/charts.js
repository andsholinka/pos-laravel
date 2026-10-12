import Chart from 'chart.js/auto';

const EMERALD = 'rgb(16, 185, 129)';
const EMERALD_BAR = 'rgba(16, 185, 129, 0.75)';
const SKY = 'rgb(14, 165, 233)';
const VIOLET = 'rgb(139, 92, 246)';
const GRID = 'rgba(148, 163, 184, 0.18)';
const TICK = 'rgb(100, 116, 139)';

const rupiah = (n) => (window.rupiah ? window.rupiah(n) : 'Rp ' + Math.round(n || 0));

/** Short axis label: 1.250.000 -> "Rp 1,3 jt", 45.000 -> "Rp 45 rb". */
const compactRupiah = (n) => {
    const v = Math.abs(n);
    const fmt = (x) => new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(x);
    if (v >= 1_000_000_000) return 'Rp ' + fmt(n / 1_000_000_000) + ' M';
    if (v >= 1_000_000) return 'Rp ' + fmt(n / 1_000_000) + ' jt';
    if (v >= 1_000) return 'Rp ' + fmt(n / 1_000) + ' rb';
    return 'Rp ' + fmt(n);
};

const number = (n) => new Intl.NumberFormat('id-ID').format(Math.round(n || 0));

/**
 * Bar (omzet) + line (transaksi or laba kotor) chart.
 *
 * Usage: x-data="salesChart({ labels, omzet, second, secondLabel, secondIsMoney, ranges: [7, 30], range: 7 })"
 * with a <canvas x-ref="canvas"> inside. `ranges`/`range` are optional: when given, setRange(n)
 * shows only the last n points. The Chart instance lives in a closure (not on the Alpine
 * component) so Alpine never wraps it in a reactive proxy.
 */
export function salesChart(config) {
    let chart = null;
    const secondIsMoney = Boolean(config.secondIsMoney);

    return {
        range: config.range ?? config.labels.length,
        ranges: config.ranges ?? [],

        init() {
            chart = new Chart(this.$refs.canvas, {
                type: 'bar',
                data: { labels: [], datasets: [] },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, color: TICK } },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => {
                                    const money = ctx.dataset.yAxisID === 'y';
                                    return ` ${ctx.dataset.label}: ${money ? rupiah(ctx.parsed.y) : number(ctx.parsed.y)}`;
                                },
                            },
                        },
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: TICK, maxRotation: 0, autoSkip: true } },
                        y: {
                            beginAtZero: true,
                            grid: { color: GRID },
                            ticks: { color: TICK, callback: (v) => compactRupiah(v) },
                        },
                        y1: {
                            display: !secondIsMoney,
                            beginAtZero: true,
                            position: 'right',
                            grid: { drawOnChartArea: false },
                            ticks: { color: TICK, precision: 0 },
                        },
                    },
                },
            });

            this.apply();
        },

        setRange(n) {
            this.range = n;
            this.apply();
        },

        apply() {
            if (!chart) return;
            const n = Math.min(this.range, config.labels.length);
            const last = (arr) => arr.slice(arr.length - n);

            chart.data.labels = last(config.labels);
            chart.data.datasets = [
                {
                    type: 'bar',
                    label: 'Omzet',
                    data: last(config.omzet),
                    backgroundColor: EMERALD_BAR,
                    hoverBackgroundColor: EMERALD,
                    borderRadius: 6,
                    maxBarThickness: 36,
                    yAxisID: 'y',
                    order: 2,
                },
                {
                    type: 'line',
                    label: config.secondLabel ?? 'Transaksi',
                    data: last(config.second),
                    borderColor: secondIsMoney ? VIOLET : SKY,
                    backgroundColor: secondIsMoney ? VIOLET : SKY,
                    borderWidth: 2,
                    pointRadius: n > 31 ? 0 : 3,
                    tension: 0.35,
                    yAxisID: secondIsMoney ? 'y' : 'y1',
                    order: 1,
                },
            ];
            chart.update();
        },

        destroy() {
            chart?.destroy();
            chart = null;
        },
    };
}

/**
 * Doughnut chart. Usage: x-data="doughnutChart({ labels, data, colors })" with <canvas x-ref="canvas">.
 * Values are Rupiah; the legend is rendered in Blade.
 */
export function doughnutChart(config) {
    let chart = null;

    return {
        init() {
            chart = new Chart(this.$refs.canvas, {
                type: 'doughnut',
                data: {
                    labels: config.labels,
                    datasets: [{ data: config.data, backgroundColor: config.colors, borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: (ctx) => ` ${ctx.label}: ${rupiah(ctx.parsed)}` } },
                    },
                },
            });
        },

        destroy() {
            chart?.destroy();
            chart = null;
        },
    };
}

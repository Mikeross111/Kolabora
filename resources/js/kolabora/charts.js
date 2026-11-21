// grafik batang kolabora (phase 21). chart.js dari paket npm lokal (bukan cdn); modul ini dimuat
// lewat import dinamis di app.js hanya bila halaman punya canvas[data-kb-chart], jadi halaman lain tidak menanggungnya.
// data: data-kb-chart = [{label, total}, …]; nama seri: data-kb-chart-label.
// warna dan font dari token design system; tanpa animasi (guardrail 8).
import { Chart, BarController, BarElement, CategoryScale, LinearScale, Tooltip } from 'chart.js';

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip);

export function initCharts(root = document) {
    const css = getComputedStyle(document.documentElement);
    const token = (name) => css.getPropertyValue(name).trim();

    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.color = token('--kb-text-secondary');

    root.querySelectorAll('canvas[data-kb-chart]').forEach((canvas) => {
        if (Chart.getChart(canvas)) return;

        let points;
        try {
            points = JSON.parse(canvas.dataset.kbChart);
        } catch {
            return;
        }

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: points.map((point) => point.label),
                datasets: [{
                    label: canvas.dataset.kbChartLabel || '',
                    data: points.map((point) => point.total),
                    backgroundColor: token('--kb-brand'),
                    borderRadius: 4,
                    maxBarThickness: 40,
                }],
            },
            options: {
                animation: false,
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: token('--kb-border-default') } },
                },
            },
        });
        canvas.dataset.kbChartReady = 'true';
    });
}

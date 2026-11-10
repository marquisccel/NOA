import {
    Chart,
    CategoryScale,
    Legend,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
} from 'chart.js';

// Hanya modul garis yang dimuat, dan hanya di halaman laporan: halaman entri harian tetap ringan.
Chart.register(CategoryScale, Legend, LineController, LineElement, LinearScale, PointElement, Tooltip);

const sumber = document.getElementById('data-grafik');

if (sumber) {
    const data = JSON.parse(sumber.textContent);

    const buat = (id, satuan, seri) => {
        const kanvas = document.getElementById(id);

        if (!kanvas) {
            return;
        }

        new Chart(kanvas, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: seri.map((s) => ({
                    label: s.label,
                    data: s.data,
                    borderColor: s.warna,
                    backgroundColor: s.warna,
                    borderDash: s.putus ? [6, 4] : [],
                    borderWidth: 2,
                    pointRadius: 2,
                    spanGaps: true,
                    tension: 0.2,
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                scales: {
                    x: { title: { display: true, text: 'Umur (hari)' } },
                    y: { beginAtZero: true, title: { display: true, text: satuan } },
                },
                plugins: { legend: { position: 'bottom' } },
            },
        });
    };

    buat('grafik-bobot', 'Bobot (g/ekor)', [
        { label: 'Bobot aktual', data: data.bobot, warna: '#4338ca' },
        { label: 'Bobot standar', data: data.bobot_standar, warna: '#6b7280', putus: true },
    ]);

    buat('grafik-deplesi', 'Deplesi kumulatif (%)', [
        { label: 'Deplesi kumulatif', data: data.deplesi, warna: '#b45309' },
    ]);
}

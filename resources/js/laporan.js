import {
    Chart,
    CategoryScale,
    Filler,
    Legend,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
} from 'chart.js';

// Hanya modul garis yang dimuat, dan hanya di halaman yang butuh grafik: halaman entri harian tetap ringan.
Chart.register(CategoryScale, Filler, Legend, LineController, LineElement, LinearScale, PointElement, Tooltip);

const gelap = () => document.documentElement.dataset.theme === 'dark';
const warna = () => ({
    teks: gelap() ? '#a7aea9' : '#5c625f',
    garis: gelap() ? 'rgba(255,255,255,0.08)' : 'rgba(126,132,127,0.18)',
    merek: gelap() ? '#5fd0a8' : '#1f6b5b',
    emas: gelap() ? '#e7c16b' : '#c99a2e',
    abu: gelap() ? '#8a918c' : '#7e847f',
    bahaya: gelap() ? '#f2807a' : '#d14343',
});

const dasar = (satuanX, satuanY, legenda = true) => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 500 },
    interaction: { mode: 'index', intersect: false },
    scales: {
        x: { title: { display: !!satuanX, text: satuanX, color: warna().teks }, ticks: { color: warna().teks, maxTicksLimit: 12 }, grid: { color: warna().garis } },
        y: { beginAtZero: true, title: { display: !!satuanY, text: satuanY, color: warna().teks }, ticks: { color: warna().teks }, grid: { color: warna().garis } },
    },
    plugins: { legend: { display: legenda, position: 'bottom', labels: { color: warna().teks, boxWidth: 12 } } },
});

const garis = (label, data, warnaGaris, putus = false, isi = false) => ({
    label,
    data,
    borderColor: warnaGaris,
    backgroundColor: isi ? warnaGaris + '22' : warnaGaris,
    fill: isi,
    borderDash: putus ? [6, 4] : [],
    borderWidth: 2.5,
    pointRadius: putus ? 0 : 2,
    pointHoverRadius: 5,
    spanGaps: true,
    tension: 0.3,
});

const kanvas = (id) => document.getElementById(id);

// Kurva harian satu siklus: bobot aktual dan standar, deplesi kumulatif.
const sumber = document.getElementById('data-grafik');
if (sumber) {
    const d = JSON.parse(sumber.textContent);
    const w = warna();

    if (kanvas('grafik-bobot')) {
        new Chart(kanvas('grafik-bobot'), {
            type: 'line',
            data: { labels: d.labels, datasets: [garis('Bobot aktual (g)', d.bobot, w.merek, false, true), garis('Bobot standar (g)', d.bobot_standar, w.abu, true)] },
            options: dasar('Umur (hari)', 'Bobot (g/ekor)'),
        });
    }

    if (kanvas('grafik-deplesi')) {
        new Chart(kanvas('grafik-deplesi'), {
            type: 'line',
            data: { labels: d.labels, datasets: [garis('Deplesi kumulatif (%)', d.deplesi, w.emas, false, true), ...(d.deplesi_standar ? [garis('Standar (%)', d.deplesi_standar, w.abu, true)] : [])] },
            options: dasar('Umur (hari)', 'Deplesi (%)'),
        });
    }
}

// Tren antar-siklus (halaman Performa): FCR dan IP.
const tren = document.getElementById('data-tren');
if (tren) {
    const d = JSON.parse(tren.textContent);
    const w = warna();

    if (kanvas('grafik-ip')) {
        new Chart(kanvas('grafik-ip'), { type: 'line', data: { labels: d.labels, datasets: [garis('IP', d.ip, w.merek, false, true)] }, options: dasar(null, 'IP', false) });
    }

    if (kanvas('grafik-fcr')) {
        const opsi = dasar(null, 'FCR', false);
        opsi.scales.y.beginAtZero = false;
        new Chart(kanvas('grafik-fcr'), { type: 'line', data: { labels: d.labels, datasets: [garis('FCR', d.fcr, w.emas, false, true)] }, options: opsi });
    }
}

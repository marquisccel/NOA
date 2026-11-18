import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            // laporan.js memuat Chart.js dan hanya dipanggil dari halaman laporan; masuk.js (kanvas) hanya dari halaman tamu.
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/laporan.js', 'resources/js/masuk.js'],
            refresh: true,
        }),
    ],
});

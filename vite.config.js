import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            // laporan.js memuat Chart.js dan hanya dipanggil dari halaman laporan.
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/laporan.js'],
            refresh: true,
        }),
    ],
});

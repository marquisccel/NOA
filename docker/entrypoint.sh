#!/bin/sh
set -e

cd /var/www/html

# Kunci aplikasi wajib ada; bila belum diatur lewat variabel lingkungan, buat sementara (sesi lama tidak berlaku lagi setelah restart).
if [ -z "$APP_KEY" ]; then
    export APP_KEY="$(php artisan key:generate --show)"
fi

# Tunggu basis data siap lalu migrasi.
n=0
until php artisan migrate --force; do
    n=$((n + 1))
    if [ "$n" -ge 15 ]; then
        echo "Basis data tidak siap, berhenti."
        exit 1
    fi
    echo "Menunggu basis data ($n/15)..."
    sleep 4
done

# Mode demo: isi data simulasi sekali, saat akun demo belum ada.
if [ "$NOA_DEMO" = "true" ]; then
    php artisan noa:siapkan-demo
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"

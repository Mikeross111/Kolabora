#!/bin/sh
# dijalankan setiap kali container start (mis. tiap deploy baru di Render/Railway).
set -e

cd /var/www/html

# APP_KEY dibuat sekali manual lalu disimpan sebagai env var; ini cuma jaga-jaga kalau belum ada
if [ -z "$APP_KEY" ]; then
    echo "PERINGATAN: APP_KEY belum diset. Jalankan 'php artisan key:generate --show' lalu simpan sebagai env var."
fi

# Railway (dan beberapa host lain) menentukan port lewat env PORT; Render selalu pakai 80.
# Apache di image ini default dengar di port 80, jadi diarahkan ulang kalau PORT beda.
if [ -n "$PORT" ] && [ "$PORT" != "80" ]; then
    sed -ri "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
    sed -ri "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf
fi

php artisan config:clear
php artisan migrate --force
php artisan db:seed --class=ReferenceDataSeeder --force || echo "PERINGATAN: seeder data referensi gagal, lanjut start."

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground

#!/bin/sh
set -e

# ── 0. Ensure required directories exist and are writable (bind-mount wipes them) ──
mkdir -p /var/www/bootstrap/cache \
         /var/www/storage/logs \
         /var/www/storage/framework/cache \
         /var/www/storage/framework/sessions \
         /var/www/storage/framework/views
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# ── 1. Install dependencies if vendor is missing (bind-mount scenario) ────────
if [ ! -f /var/www/vendor/autoload.php ]; then
    echo "[entrypoint] vendor/ not found — running composer install..."
    composer install --no-dev --prefer-dist --optimize-autoloader --working-dir=/var/www
fi

# ── 1. Create .env from example if it doesn't exist ───────────────────────────
if [ ! -f /var/www/.env ]; then
    echo "[entrypoint] .env not found — copying from .env.example"
    cp /var/www/.env.example /var/www/.env
fi

# ── 1b. Ensure FRONTEND_URL is set in .env ────────────────────────────────────
if ! grep -q "^FRONTEND_URL=" /var/www/.env; then
    echo "[entrypoint] FRONTEND_URL not found in .env — adding default"
    printf '\nFRONTEND_URL=http://localhost:5173\n' >> /var/www/.env
fi

# ── 2. Generate APP_KEY if empty ───────────────────────────────────────────────
if grep -q "^APP_KEY=$" /var/www/.env; then
    echo "[entrypoint] Generating APP_KEY..."
    php /var/www/artisan key:generate --force
fi

# ── 3. Wait for MySQL (extra safety on top of healthcheck) ────────────────────
echo "[entrypoint] Waiting for database..."
until php -r "new PDO('mysql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT').';dbname='.getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" 2>/dev/null; do
    sleep 2
done
echo "[entrypoint] Database is ready."

# ── 4. Run migrations ─────────────────────────────────────────────────────────
# Remove duplicate Sanctum migration if present (we have our own)
rm -f /var/www/database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php
php /var/www/artisan migrate --force

# ── 4b. Publish Sanctum config only if not already present ─────────────────────
if [ ! -f /var/www/config/sanctum.php ]; then
    echo "[entrypoint] Publishing Sanctum config..."
    php /var/www/artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --force || true
fi

# ── 5. Generate Swagger docs ──────────────────────────────────────────────────
php /var/www/artisan l5-swagger:generate || true

# ── 6. Cache config for performance ───────────────────────────────────────────
php /var/www/artisan config:cache || true

# ── 7. Start PHP-FPM ──────────────────────────────────────────────────────────
echo "[entrypoint] Starting PHP-FPM..."
exec php-fpm

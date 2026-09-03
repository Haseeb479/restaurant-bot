#!/bin/bash
set -e

# Configure port for Render or local
PORT=${PORT:-8080}
echo "🚀 Starting Foodio Restaurant Bot on port ${PORT}..."
sed -i "s/LISTEN_PORT/${PORT}/g" /etc/nginx/sites-available/default

# Prepare environment file if missing
if [ ! -f /var/www/html/.env ]; then
    echo "📋 Copying .env.example to .env..."
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Ensure APP_KEY exists
if ! grep -q "APP_KEY=base64:" /var/www/html/.env; then
    echo "🔑 Generating Application Key..."
    php /var/www/html/artisan key:generate --force
fi

# Prepare SQLite database
mkdir -p /var/www/html/database
touch /var/www/html/database/database.sqlite
chown -R www-data:www-data /var/www/html/database
chmod -R 775 /var/www/html/database

# Prepare storage and session dirs
mkdir -p /var/www/html/storage/framework/{sessions,views,cache} /var/www/html/storage/logs /var/www/html/bootstrap/cache /var/www/html/.wwebjs_auth
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/.wwebjs_auth
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create storage symlink
php /var/www/html/artisan storage:link || true

# Run database migrations and seed default restaurant if empty
echo "📦 Running database migrations..."
php /var/www/html/artisan migrate --force

echo "🌱 Ensuring default restaurant exists..."
php /var/www/html/artisan db:seed --force || true

# Cache config and routes for speed
php /var/www/html/artisan optimize:clear
php /var/www/html/artisan config:cache || true
php /var/www/html/artisan route:cache || true
php /var/www/html/artisan view:cache || true

echo "✅ Initialization complete. Starting Nginx, PHP-FPM, and WhatsApp Bot..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

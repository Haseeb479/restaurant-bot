#!/bin/bash

# Configure port for Render or local
PORT=${PORT:-8080}
echo "🚀 [Foodio] Starting Foodio Restaurant Bot on port ${PORT}..."

# Replace listen port in Nginx config
sed -i "s/LISTEN_PORT/${PORT}/g" /etc/nginx/sites-available/default

# Ensure runtime directories exist
mkdir -p /var/log/supervisor /var/run /run/php /var/log/nginx
touch /var/log/supervisor/supervisord.log

# Prepare environment file if missing
if [ ! -f /var/www/html/.env ]; then
    echo "📋 [Foodio] Creating .env from .env.example..."
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Force SQLite in .env for all-in-one container deployment
echo "🗄️ [Foodio] Configuring SQLite database..."
sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' /var/www/html/.env || echo "DB_CONNECTION=sqlite" >> /var/www/html/.env
sed -i 's|^DB_DATABASE=.*|DB_DATABASE=/var/www/html/database/database.sqlite|' /var/www/html/.env || echo "DB_DATABASE=/var/www/html/database/database.sqlite" >> /var/www/html/.env
sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=file/' /var/www/html/.env || true
sed -i 's/^CACHE_STORE=.*/CACHE_STORE=file/' /var/www/html/.env || true

# Ensure APP_KEY exists
if ! grep -q "APP_KEY=base64:" /var/www/html/.env; then
    echo "🔑 [Foodio] Generating Application Key..."
    php /var/www/html/artisan key:generate --force || true
fi

# Ensure BOT_INTERNAL_TOKEN is set so internal control API starts
if ! grep -q "BOT_INTERNAL_TOKEN=[a-zA-Z0-9]" /var/www/html/.env; then
    DEFAULT_TOKEN="02946be11eb016a014ef2c16a254b0bc8d62c141e52c1525002d96a484cda4d5"
    TOKEN="${BOT_INTERNAL_TOKEN:-$DEFAULT_TOKEN}"
    echo "🤖 [Foodio] Setting BOT_INTERNAL_TOKEN..."
    sed -i "s/^BOT_INTERNAL_TOKEN=.*/BOT_INTERNAL_TOKEN=${TOKEN}/" /var/www/html/.env || echo "BOT_INTERNAL_TOKEN=${TOKEN}" >> /var/www/html/.env
    export BOT_INTERNAL_TOKEN="${TOKEN}"
fi

# Sync GROQ_API_KEY if provided in container environment
if [ -n "$GROQ_API_KEY" ]; then
    echo "🧠 [Foodio] Configuring GROQ_API_KEY..."
    sed -i "s/^GROQ_API_KEY=.*/GROQ_API_KEY=${GROQ_API_KEY}/" /var/www/html/.env || echo "GROQ_API_KEY=${GROQ_API_KEY}" >> /var/www/html/.env
fi

# Ensure ADMIN_PASSWORD is set (default: admin123, or user specified)
ADMIN_PASS="${ADMIN_PASSWORD:-admin123}"
echo "🛡️ [Foodio] Configuring ADMIN_PASSWORD..."
sed -i "s/^ADMIN_PASSWORD=.*/ADMIN_PASSWORD=${ADMIN_PASS}/" /var/www/html/.env || echo "ADMIN_PASSWORD=${ADMIN_PASS}" >> /var/www/html/.env
export ADMIN_PASSWORD="${ADMIN_PASS}"

# Ensure SQLite database file exists and is writable
mkdir -p /var/www/html/database
touch /var/www/html/database/database.sqlite
chmod -R 777 /var/www/html/database

# Prepare storage and session dirs with full permissions
mkdir -p /var/www/html/storage/framework/{sessions,views,cache} /var/www/html/storage/logs /var/www/html/bootstrap/cache /var/www/html/.wwebjs_auth
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/.wwebjs_auth
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/.wwebjs_auth

# Create storage symlink
php /var/www/html/artisan storage:link || true

# Run database migrations
echo "📦 [Foodio] Running database migrations..."
php /var/www/html/artisan migrate --force || echo "⚠️ Migration notice: table may already exist."

echo "🌱 [Foodio] Ensuring default restaurant data exists..."
php /var/www/html/artisan db:seed --force || echo "🌱 Database already seeded."

# Clear cached config so runtime environment variables apply
php /var/www/html/artisan optimize:clear || true

# Test Nginx configuration
nginx -t || echo "⚠️ Nginx config test finished"

echo "✅ [Foodio] Initialization complete. Starting Nginx, PHP-FPM, and WhatsApp Bot..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

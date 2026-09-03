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

# Detect database configuration
if [ -n "$DATABASE_URL" ] || [ -n "$DB_URL" ] || [ "$DB_CONNECTION" = "pgsql" ]; then
    DB_CONN="${DB_CONNECTION:-pgsql}"
    ACTIVE_URL="${DATABASE_URL:-$DB_URL}"
    echo "🗄️ [Foodio] Using configured PostgreSQL database..."
    sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=${DB_CONN}/" /var/www/html/.env || echo "DB_CONNECTION=${DB_CONN}" >> /var/www/html/.env
    sed -i "s/^DB_PORT=.*/DB_PORT=5432/" /var/www/html/.env || echo "DB_PORT=5432" >> /var/www/html/.env
    if [ -n "$ACTIVE_URL" ]; then
        sed -i "s|^DATABASE_URL=.*|DATABASE_URL=${ACTIVE_URL}|" /var/www/html/.env || echo "DATABASE_URL=${ACTIVE_URL}" >> /var/www/html/.env
        sed -i "s|^DB_URL=.*|DB_URL=${ACTIVE_URL}|" /var/www/html/.env || echo "DB_URL=${ACTIVE_URL}" >> /var/www/html/.env
    fi
else
    echo "🗄️ [Foodio] Using default SQLite database..."
    sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' /var/www/html/.env || echo "DB_CONNECTION=sqlite" >> /var/www/html/.env
    sed -i 's|^DB_DATABASE=.*|DB_DATABASE=/var/www/html/database/database.sqlite|' /var/www/html/.env || echo "DB_DATABASE=/var/www/html/database/database.sqlite" >> /var/www/html/.env
    mkdir -p /var/www/html/database
    touch /var/www/html/database/database.sqlite
    chmod -R 777 /var/www/html/database
fi

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

# Prepare storage and session dirs with full permissions
mkdir -p /var/www/html/storage/framework/{sessions,views,cache} /var/www/html/storage/logs /var/www/html/bootstrap/cache /var/www/html/.wwebjs_auth
touch /var/www/html/storage/logs/laravel.log
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/.wwebjs_auth /var/www/html/database 2>/dev/null || true
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/.wwebjs_auth /var/www/html/database 2>/dev/null || true

# Create storage symlink
php /var/www/html/artisan storage:link || true

# Run database migrations
echo "📦 [Foodio] Running database migrations..."
php /var/www/html/artisan migrate --force || echo "⚠️ Migration notice: table may already exist."

echo "🌱 [Foodio] Ensuring default restaurant data exists..."
php /var/www/html/artisan db:seed --force || echo "🌱 Database already seeded."

# Clear cached config so runtime environment variables apply
php /var/www/html/artisan optimize:clear || true

# Re-apply full permissions right before handing over to Nginx and PHP-FPM
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/.wwebjs_auth /var/www/html/database 2>/dev/null || true
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/.wwebjs_auth /var/www/html/database 2>/dev/null || true

# Test Nginx configuration
nginx -t || echo "⚠️ Nginx config test finished"

echo "✅ [Foodio] Initialization complete. Starting Nginx, PHP-FPM, and WhatsApp Bot..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

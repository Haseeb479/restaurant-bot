# Deployment Checklist

This document contains the steps needed to prepare, deploy, and launch the Restaurant Bot project in production.

## 1. Environment setup
- Copy `.env.example` to `.env`
- Set `APP_ENV=production`
- Set `APP_DEBUG=false`
- Set `APP_URL=https://your-domain.com`
- Set a strong `ADMIN_PASSWORD`
- Configure database settings:
  - `DB_CONNECTION`
  - `DB_HOST`
  - `DB_PORT`
  - `DB_DATABASE`
  - `DB_USERNAME`
  - `DB_PASSWORD`
- Configure the bot process:
  - `BOT_INTERNAL_TOKEN` — **required**, shared secret between Laravel and the
    bot's control server. Generate with
    `php -r "echo bin2hex(random_bytes(32));"`.
  - `BOT_INTERNAL_PORT` / `BOT_INTERNAL_API` — must agree with each other.
  - `GROQ_API_KEY`, `OWNER_PHONE`.
  Restaurants need no per-tenant API credentials: owners connect by scanning a
  QR code from their dashboard.
- Configure any mail settings if needed.

## 2. Install dependencies
```bash
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

## 3. Application keys and database
```bash
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force  # optional, only if seed data is needed
```

## 4. Cache and optimize
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

## 5. File permissions
Ensure the web server user can write to:
- `storage`
- `bootstrap/cache`

## 6. Server configuration
- Point the web server document root to `public/`
- Use PHP 8.3
- Enable HTTPS and configure SSL
- Configure a process manager for PHP-FPM if needed

## 7. Production checks
- Confirm `APP_DEBUG=false`
- Make sure `.env` is not publicly accessible
- Confirm the application serves correctly from the production URL
- Test admin login, restaurant login, and dashboard flows

## 8. Monitoring and maintenance
- Monitor `storage/logs/laravel.log`
- Set up backups for the database and `.env`
- Optionally add uptime and alert monitoring

## 9. Local LAN testing
To test on your local network before full launch:
```bash
php artisan serve --host=0.0.0.0 --port=8000
```
Then open `http://<your-local-ip>:8000` from another device on the same LAN.

## 10. Launch steps
- Finalize domain and DNS records
- Deploy code to production server
- Run migrations and cache commands
- Validate all core flows:
  - Admin create restaurant
  - Restaurant login/menu/settings
  - Order status updates
  - WhatsApp message delivery (if configured)

---

### Optional improvements after launch
- Add automated tests for core flows
- Add queue worker support
- Add usage analytics or error reporting
- Harden security headers and firewall rules

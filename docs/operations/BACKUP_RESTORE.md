# Backup and restore

Production uses the Railway PostgreSQL service. Confirm backup retention and
restore procedures in the Railway project before relying on them. Keep a
separate backup before migrations or other destructive changes.

## PostgreSQL

Create a custom-format backup from an environment with `pg_dump` installed:

```bash
pg_dump "$DATABASE_URL" --format=custom --no-owner --no-privileges \
  --file="restaurant_bot_$(date +%Y%m%d_%H%M%S).dump"
```

Restore to a prepared database:

```bash
pg_restore --clean --if-exists --no-owner --no-privileges \
  --dbname="$DATABASE_URL" restaurant_bot_YYYYMMDD_HHMMSS.dump
```

Verify the schema and application data after restore:

```bash
php artisan migrate:status
php artisan tinker --execute="echo 'Restaurants: ' . \App\Models\Restaurant::count() . ', Orders: ' . \App\Models\Order::count();"
```

## Local SQLite development database

Use SQLite's online backup command for a consistent copy:

```bash
sqlite3 database/database.sqlite \
  ".backup 'database/database_backup_$(date +%Y%m%d_%H%M%S).sqlite'"
```

Restore only after stopping the local application, then clear cached
configuration:

```bash
cp database/database_backup_YYYYMMDD_HHMMSS.sqlite database/database.sqlite
php artisan optimize:clear
```

## Evolution API sessions and uploads

Evolution API session storage and uploaded media are separate from PostgreSQL.
Verify that each service uses persistent storage and has its own recovery
procedure. A PostgreSQL backup does not contain WhatsApp sessions or uploaded
files.

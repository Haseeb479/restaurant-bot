# Foodio Database Backup & Restore Guide

This document outlines practical and zero-extra-cost backup and disaster recovery procedures for Foodio based on the current infrastructure (Railway / Render / Local Docker).

---

## 1. Current Architecture Overview

Depending on the deployment environment, Foodio connects to:
- **Production (Railway / PostgreSQL):** Configured via DATABASE_URL or DB_CONNECTION=pgsql.
- **Production / Staging (Render / MySQL or SQLite):** Configured via .env / render.yaml.
- **Local Development:** SQLite (database/database.sqlite) or MySQL (127.0.0.1:3306).

---

## 2. PostgreSQL (Railway Setup)

### Automated Daily Snapshots
Railway provides automatic point-in-time and daily backups for managed PostgreSQL plugins:
- Navigate to your **Railway Dashboard** -> Select your **Postgres Service** -> Click **Backups**.
- Snapshots are retained automatically without extra cost on standard developer tiers.

### Manual Backup (CLI / Script)
To generate an immediate, self-contained SQL dump:
\\\ash
# Using pg_dump with the DATABASE_URL connection string:
pg_dump "$DATABASE_URL" -F c -b -v -f "foodio_backup_$(date +%Y%m%d_%H%M%S).dump"
\\\

### Restore Procedure
\\\ash
# To restore to a clean database:
pg_restore -v --clean --no-owner --no-privileges -d "$DATABASE_URL" foodio_backup_YYYYMMDD_HHMMSS.dump
\\\

### Verification of Restoration
1. Check tables and counts:
\\\ash
php artisan tinker --execute="echo 'Restaurants: ' . \App\Models\Restaurant::count() . ', Orders: ' . \App\Models\Order::count();"
\\\
2. Verify migrations:
\\\ash
php artisan migrate:status
\\\

---

## 3. MySQL Setup

### Manual Backup (mysqldump)
\\\ash
mysqldump -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" \
  --single-transaction \
  --quick \
  --routines \
  --triggers > "foodio_mysql_backup_$(date +%Y%m%d_%H%M%S).sql"
\\\

### Restore Procedure
\\\ash
mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < foodio_mysql_backup_YYYYMMDD_HHMMSS.sql
\\\

### Verification
\\\ash
php artisan tinker --execute="echo 'Total Orders: ' . \App\Models\Order::count();"
\\\

---

## 4. SQLite (Local / Minimal Container)

### Backup
\\\ash
# Safe live copy using SQLite CLI:
sqlite3 database/database.sqlite ".backup 'storage/app/backups/backup_$(date +%Y%m%d_%H%M%S).sqlite'"
\\\

### Restore
\\\ash
cp storage/app/backups/backup_YYYYMMDD_HHMMSS.sqlite database/database.sqlite
php artisan optimize:clear
\\\

---

## 5. Important Status & Testing Notice

> Note:
> - Railway automated backups are managed directly by the platform provider.
> - The manual backup commands above use native database utilities (pg_dump, mysqldump, sqlite3).
> - The Super Admin UI trigger (AdminController::createBackupDump) records an audit log event and can be hooked into custom shell automations if a shared persistent volume is attached.

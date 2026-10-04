# Railway deployment checklist

This project runs on Railway with separate application, PostgreSQL, Redis, and
Evolution API services. Configure service references and secrets in Railway's
project dashboard; never put production credentials in the repository.

## Application service

- Connect the Laravel application service to the intended Git repository and
  production branch.
- Confirm the Railway builder and start command match the service configuration.
  This repository contains both a `Dockerfile` and `nixpacks.toml`; keep the
  selected builder consistent with the service's Railway settings.
- Set `APP_ENV=production`, `APP_DEBUG=false`, the public `APP_URL`, and a
  persistent `APP_KEY`. Do not regenerate `APP_KEY` on each deploy.
- Set a unique, strong `ADMIN_PASSWORD`.
- Set `DB_CONNECTION=pgsql` and connect the app to the Railway PostgreSQL
  service using Railway's private connection variables.
- Set Redis connection variables and select Redis for the cache, session, and
  queue drivers when those services are intended to use Redis.
- Set `EVOLUTION_BASE_URL` to the Evolution API service's private Railway
  address and `EVOLUTION_API_KEY` to the matching service secret.
- Configure any enabled integrations (for example, Groq or Google Maps) with
  Railway-managed secrets.

## Release and data

- Back up PostgreSQL before schema changes and verify Railway backup retention.
- Ensure any uploaded media that must survive deployments is stored on
  persistent storage or an external object store.
- Ensure Evolution API session data is stored persistently by its Railway
  service. A successful application deploy alone does not preserve Evolution
  sessions.
- Run migrations as a controlled release step:

  ```bash
  php artisan migrate --force
  ```

- Do not run seeders against a live database unless the seed data is explicitly
  intended for that environment.

## Health and smoke tests

Use `/health/live` for process liveness and `/health/ready` to check application
readiness, including its database and cache connections. Configure the Railway
health check path to match the check appropriate for the service.

After deploying, verify:

1. The application serves over HTTPS with debug output disabled.
2. Admin and restaurant authentication work across requests (session storage
   should be persistent/shared).
3. The dashboard can create or retrieve an Evolution API instance and display
   the QR code.
4. A paired WhatsApp account can receive a message, process an order, and send
   a reply.
5. An order can be tracked publicly, and order updates persist after an app
   restart.
6. Logs show no failed migrations, database connection errors, Redis errors, or
   repeated Evolution API failures.

For Evolution API networking and instance operations, see
[`EVOLUTION_API.md`](EVOLUTION_API.md). For database recovery, see
[`../operations/BACKUP_RESTORE.md`](../operations/BACKUP_RESTORE.md).

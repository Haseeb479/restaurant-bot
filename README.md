# Restaurant Bot

Multi-tenant restaurant ordering and management platform. The Laravel
application provides the admin and restaurant dashboards, order management,
public tracking, and the Evolution API integration. Production services run on
Railway: the web application, PostgreSQL, Redis, and Evolution API.

## Stack

| Component | Technology |
| --- | --- |
| Application | Laravel 13, PHP 8.3+ |
| WhatsApp | Evolution API v2 |
| Database | PostgreSQL |
| Cache, sessions, and queues | Redis |
| Frontend | Blade, Vite, Tailwind CSS |

The repository also contains an older Node.js/`whatsapp-web.js` bot under
[`bot/`](bot/). It is retained for reference and development; it is not the
Evolution API production service.

## Local development

Requirements: PHP 8.3+, Composer, Node.js 20+, and a local database. Copy
`.env.example` to `.env`, configure the required application and database
settings, then run:

```bash
composer install
npm ci
php artisan key:generate
php artisan migrate
npm run build
php artisan serve
```

Run the Laravel tests with `php artisan test` and the legacy bot tests with
`npm test`.

## Main pages

| Path | Purpose |
| --- | --- |
| `/admin/login` | Platform administration |
| `/dashboard/{id}/login` | Restaurant owner dashboard |
| `/restaurant/register` | Restaurant registration |
| `/track/{code}` | Public order tracking |

To connect a restaurant, create or approve it in the admin panel, sign in to
its dashboard, and use **Connect WhatsApp** to pair its Evolution API instance.

## Production deployment

Railway service configuration is managed in the Railway project dashboard.
Configure the web service to use its linked PostgreSQL, Redis, and Evolution API
services; keep credentials in Railway variables, not in source control. See
[`docs/deployment/DEPLOYMENT.md`](docs/deployment/DEPLOYMENT.md) for the
deployment checklist and
[`docs/deployment/EVOLUTION_API.md`](docs/deployment/EVOLUTION_API.md) for
Evolution API setup.

## Project layout

```text
app/          Laravel application: controllers, models, services, jobs
bot/          Legacy Node.js bot and its tests
bootstrap/    Laravel application bootstrap
config/       Laravel configuration
database/     Migrations, seeders, and factories
docs/         Architecture, deployment, operations, and security documentation
docker/       Web server and process configuration for the Docker build
public/       Web entry point and public assets
resources/    Blade views, frontend JavaScript, and styles
routes/       Web and console routes
tests/        Laravel feature and unit tests
```

## Documentation

- [Deployment checklist](docs/deployment/DEPLOYMENT.md)
- [Evolution API operations](docs/deployment/EVOLUTION_API.md)
- [Backup and restore](docs/operations/BACKUP_RESTORE.md)
- [Architecture notes](docs/architecture/BOT_V2_FEATURES.md)
- [Security review and remediation](docs/security/README.md)
- [Legacy Node bot notes](bot/README.md)

## License

Built on the Laravel framework (MIT). Application code © its authors.

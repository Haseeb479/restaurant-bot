# Restaurant Bot

Multi-tenant WhatsApp restaurant-ordering platform: a **Laravel** web app
(super-admin panel + per-restaurant owner dashboards + public order tracking)
paired with a **Node.js WhatsApp bot** (`whatsapp-web.js` + Puppeteer) that runs
an AI ordering conversation (Groq LLM + vision menu OCR) and writes orders to a
shared **MySQL** database.

> Current mode: the bot connects a real WhatsApp account via QR code. This is the
> foundation for a future hosted SaaS.

## Architecture

| Component | Tech | Role |
|-----------|------|------|
| Web app | Laravel 13 (PHP 8.3+) | Admin panel, owner dashboards, `/track` order lookup |
| Bot | Node.js 20+, `whatsapp-web.js`, Puppeteer | WhatsApp session, AI conversation, order capture |
| AI | Groq (chat + menu OCR) | Replies, parses menus from images/Excel |
| Control server | Node HTTP (`bot/src/server/InternalServer.js`, port 3000) | QR status / send-message / restart, called by Laravel |
| Database | MySQL | restaurants, menu, orders, customers, conversations |

Both processes read the same `.env`.

## Prerequisites

- PHP **8.3+** with Composer
- Node.js **20+** and npm
- MySQL 8+
- A Groq API key — <https://console.groq.com/keys>

## Setup

```bash
composer install
npm install

cp .env.example .env
# Edit .env: DB_* credentials, ADMIN_PASSWORD (required), GROQ_API_KEY, OWNER_PHONE

php artisan key:generate
php artisan migrate --seed
npm run build
```

## Running

Two processes. In development, use two terminals:

```bash
php artisan serve
```

```bash
npm run bot
```

Laravel serves on <http://localhost:8000>; the bot's control server listens on
port 3000. For production, run both under PM2:

```bash
pm2 start ecosystem.config.cjs
```

## Connecting WhatsApp

1. Open the super-admin panel at <http://localhost:8000/admin/login>
   (password = `ADMIN_PASSWORD`) and create a restaurant — or let an owner
   self-register at `/restaurant/register`.
2. Log into the owner dashboard at `http://localhost:8000/dashboard/{id}/login`.
3. Open **Connect WhatsApp** and scan the QR code with the restaurant's phone.
4. Message the bot to place a test order; customers track it at `/track/{code}`.

## Key URLs

| URL | Purpose |
|-----|---------|
| `/admin/login` | Super-admin panel |
| `/dashboard/{id}/login` | Restaurant owner dashboard |
| `/restaurant/register` | Owner self-registration |
| `/track/{code}` | Public order tracking |

## Configuration

All configuration lives in `.env` (see `.env.example` for the full annotated
list). Bot-specific keys: `BOT_INTERNAL_PORT`, `BOT_INTERNAL_TOKEN`,
`GROQ_API_KEY`, `GROQ_MODELS` (optional model fallback order), `REQUEST_DELAY_MS`,
`OWNER_PHONE`.

The bot reads and writes MySQL directly; it does not call the Laravel HTTP API.
Traffic goes the other way — Laravel calls the bot's control server on
`BOT_INTERNAL_API`, authenticated with `BOT_INTERNAL_TOKEN`.

## Security

A source-level security review lives in [`docs/security/`](docs/security/) —
start with [`REMEDIATION.md`](docs/security/REMEDIATION.md). **Set a strong,
unique `ADMIN_PASSWORD`** and work through that checklist before exposing this
platform to the public internet.

## License

Built on the Laravel framework (MIT). Application code © its authors.

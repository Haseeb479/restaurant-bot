# WhatsApp QR — Quick Start

This bot connects to WhatsApp by scanning a QR code with the restaurant's phone
(via `whatsapp-web.js`). There is **no** Meta/Facebook developer account, webhook,
ngrok tunnel, or access token involved — that older integration has been removed.

## 1. Start the two processes

Laravel (web dashboard + tracking pages):

```bash
php artisan serve
```

The bot (in a second terminal):

```bash
npm run bot
```

Laravel serves on <http://localhost:8000>; the bot's control server listens on
`127.0.0.1:3000` and is reached only through Laravel (never exposed to the
browser). Both must share the same `BOT_INTERNAL_TOKEN` — see `.env.example`.

## 2. Create a restaurant

Either:

- **Super admin:** open <http://localhost:8000/admin/login> (password =
  `ADMIN_PASSWORD` from `.env`) → **Create Restaurant**, or
- **Owner self-service:** <http://localhost:8000/restaurant/register>.

## 3. Scan the QR

1. Log into the owner dashboard: `http://localhost:8000/dashboard/{id}/login`.
2. Open **Connect WhatsApp**. A QR code appears (served from the bot via the
   dashboard proxy).
3. On the restaurant's phone: WhatsApp → **Linked devices** → **Link a device** →
   scan.
4. The dashboard status flips to **connected** once pairing completes.

## 4. Place a test order

Message the restaurant's WhatsApp number from another phone:

```
hello
menu
```

Order conversationally (the bot asks for items, your name, address, and payment,
then shows a summary and a confirmation prompt). On confirmation it replies with a
tracking code.

## 5. Track and manage

- **Customer:** the tracking code, or `http://localhost:8000/track/{code}`.
- **Owner:** `http://localhost:8000/dashboard/{id}/orders` — update status; the
  customer is notified on WhatsApp at each stage.

## Logs

```bash
tail -f storage/logs/laravel.log
```

The bot prints its own activity (QR, connection state, orders, errors) to the
terminal running `npm run bot`.

## Troubleshooting

- **QR never appears / status stuck on initializing** — confirm the bot process
  is running and `BOT_INTERNAL_TOKEN` matches between Laravel and the bot.
- **"This WhatsApp number is not yet linked to a restaurant"** — the scanned
  number doesn't match any active restaurant's `whatsapp_number`. Fix it in the
  dashboard/admin panel; the bot's cache clears within a few seconds.
- **Bot replies with small talk instead of taking an order** — check
  `GROQ_API_KEY` is set; without it the bot falls back to canned replies.

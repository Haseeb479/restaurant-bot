# WhatsApp Bot Service

The Foodio WhatsApp bot is a Node.js microservice (`whatsapp-web.js` + Puppeteer) that handles customer conversations, AI-assisted ordering via Groq, menu OCR, and real-time order state updates directly in MySQL.

## Architecture & Responsibilities

- **Entrypoint:** `bot/index.js`
  - Initializes the WhatsApp client via Puppeteer / `whatsapp-web.js`.
  - Starts the loopback HTTP control server (`InternalServer.js`) on `127.0.0.1:3000`.
  - Handles process lifecycle signals (`SIGINT`, `SIGTERM`).
- **Internal Control Server (`bot/src/server/InternalServer.js`):**
  - Bound strictly to `127.0.0.1` (`BOT_INTERNAL_PORT`, default 3000).
  - Protected with `X-Bot-Token` verification (`BOT_INTERNAL_TOKEN`).
  - Endpoints:
    - `GET /qr`: Returns current QR code for pairing if unauthenticated.
    - `POST /send`: Sends WhatsApp message directly from the paired bot.
    - `POST /restart`: Safely restarts client session.
- **Message Routing & Handlers (`bot/src/handlers/`):**
  - `MessageRouter.js`: Central dispatcher for incoming WhatsApp messages.
  - `TrackingHandler.js`: Intercepts tracking inquiries (e.g. `FZ1234`, `ORD5821`, `TRK-*`) with PII-redacted status replies.
  - `ChatHandler.js`: Manages AI conversational state, cart building, and order confirmation.
  - `EscalationHandler.js`: Detects escalation intent and notifies restaurant managers.
- **AI Services (`bot/src/ai/`):**
  - `GroqClient.js`: Multi-model LLM chat completions with automatic fallback.
  - `PromptBuilder.js`: Contextual prompt assembly including restaurant metadata and live menu.
  - `MenuOcrService.js`: Vision-based OCR to ingest menu photos into structured items.
- **Services & Data Layer (`bot/src/services/`):**
  - `Database.js`: MySQL connection pool reading and writing shared tables.
  - `OrderService.js`: Parses structured orders from conversational history and commits to MySQL.
  - `SessionManager.js`: In-memory conversation state per customer with TTL eviction.
  - `RateLimiter.js`: Per-sender message rate limiting.
  - `NotifyService.js`: Sends owner notifications and dispatches outbound webhooks.
- **Utilities (`bot/src/utils/`):**
  - `LogSanitizer.js`: Redacts customer phone numbers and message bodies before logging.
  - `WebhookUrlValidator.js`: SSRF protection ensuring webhooks only target public HTTPS endpoints.

## Running the Bot

### Development Mode
```bash
# Standard start
npm run bot

# Watch mode for file changes
npm run bot:dev
```

### Testing
```bash
# Run bot test suite (Node test runner)
npm test

# Test Groq LLM connectivity
npm run test:groq
```

### Production
Managed alongside Laravel using PM2:
```bash
pm2 start ecosystem.config.cjs
```

# Threat Model

Architecture, trust boundaries, and attacker profiles for the WhatsApp Restaurant
Ordering platform. Read alongside [`SECURITY_ANALYSIS.md`](SECURITY_ANALYSIS.md)
(findings) and [`REMEDIATION.md`](REMEDIATION.md) (fixes).

---

## 1. System overview

A multi-tenant SaaS that lets restaurants take orders over WhatsApp with an
AI agent, plus web dashboards for owners and a super-admin.

**Components**

| Component | Tech | Role |
|-----------|------|------|
| Web app | Laravel 13 (PHP 8.3) | Super-admin panel, per-restaurant owner dashboards, public order tracking |
| WhatsApp bot | Node.js, `whatsapp-web.js`, Puppeteer | Connects a real WhatsApp account, runs the AI ordering conversation, writes orders |
| AI | Groq (LLM chat + vision OCR) | Generates replies, parses menus from images/Excel |
| Datastore | MySQL | Shared DB for restaurants, orders, menu, customers, conversations |
| Internal control server | Node HTTP (`InternalServer.js`, port 3000) | QR status, send-message, restart, cache invalidation |

**Order data flow (live path)**

```
Customer (WhatsApp) → bot MessageRouter → ChatHandler → Groq LLM
      → (reply text) → OrderService.parseOrderFromHistory → MySQL INSERT
Owner dashboard (Laravel) → reads orders → updateStatus
      → POST bot /send-message → WhatsApp customer
      → POST google_sheet_webhook (optional)
```

Orders are written by the **bot directly to MySQL** with parameterized queries;
the Laravel `routes/api.php` order endpoints exist in source but are **not
registered** (`bootstrap/app.php`), so they are inert today.

---

## 2. Trust boundaries

```
                          ┌─────────────────────────── Internet ───────────────────────────┐
                          │                                                                 │
   [Anonymous web user] ──┼─▶  GET /track/{code}            (public, no auth)               │
   [Prospective owner]  ──┼─▶  /restaurant/register         (public, auto-login)            │
   [WhatsApp customer]  ──┼─▶  WhatsApp → bot                (untrusted input + LLM)         │
                          │                                                                 │
                          └───────────────┬─────────────────────────────────┬───────────────┘
                                          │                                 │
                              ┌───────────▼──────────┐        ┌─────────────▼─────────────┐
   [Restaurant owner] ───────▶│  Laravel web app     │        │  Node WhatsApp bot        │
   (session: restaurant_{id}) │  (dashboards/admin)  │◀──────▶│  + InternalServer :3000   │
   [Super admin] ────────────▶│                      │  HTTP  │  (0.0.0.0, NO AUTH ⚠)     │
   (session: admin_logged_in) └──────────┬───────────┘        └─────────────┬─────────────┘
                                         │                                  │
                                         └───────────────┬──────────────────┘
                                                         ▼
                                                 ┌───────────────┐
                                                 │  MySQL (shared)│
                                                 └───────────────┘
                                                         │
                                          ┌──────────────▼───────────────┐
                                          │  Groq API (external LLM)      │
                                          │  Google API (leaked key ⚠)    │
                                          └───────────────────────────────┘
```

**Key boundaries and their current state**

1. **Internet → Laravel dashboards.** Enforced in-controller via `authCheck()`
   (super-admin or `restaurant_{id}` session) with tenant scoping. *Solid*, but
   protected only by weak/plaintext passwords with no throttling (C-01, H-01,
   H-02).
2. **Internet → public tracking / registration.** Intentionally open. Tracking
   leaks PII on weak codes (H-04); registration auto-logs-in and becomes the
   pivot into authenticated sinks (M-01 → C-03/H-03).
3. **Web app ↔ bot control server.** Assumed to be a private `localhost` channel
   but actually bound to `0.0.0.0` with no auth (C-02) — the weakest boundary.
4. **Customer → LLM → order/DB.** The LLM sits *inside* the trust path: its
   free-text output drives order creation and totals (H-06). Untrusted customer
   input can influence server state.
5. **Server → arbitrary webhook.** `google_sheet_webhook` lets an owner make the
   server call any URL (H-03) — an outbound boundary hole (SSRF).
6. **Tenant ↔ tenant.** Isolation depends entirely on the session-flag checks in
   `authCheck()` and per-object `restaurant_id` comparisons. Correct where
   applied; there is no database-level tenant scoping as a backstop.

---

## 3. Attacker profiles

| # | Attacker | Position | What they can attempt today |
|---|----------|----------|------------------------------|
| A1 | **Anonymous internet** | No account | Guess `admin123` (C-01); if port 3000 is reachable, hijack WhatsApp QR and send messages (C-02); enumerate tracking codes for PII (H-04); brute-force logins (H-02). |
| A2 | **Prospective tenant** | Can hit `/restaurant/register` | Self-register + auto-login (M-01), then upload a `.php` webshell → RCE (C-03), and use `google_sheet_webhook` for SSRF into `:3000`/metadata (H-03). |
| A3 | **Malicious WhatsApp customer** | Can DM the bot | Prompt-inject to place orders with attacker-chosen totals/items (H-06); inject CSV formulas via name/address that fire when an owner exports (M-03); DM guessed tracking codes for others' order/rider PII (H-04). |
| A4 | **Malicious / careless owner** | Valid `restaurant_{id}` | SSRF + exfiltrate their own customers' PII to any URL (H-03); upload → RCE (C-03); set arbitrary order status (M-05). |
| A5 | **Network-adjacent** | Same host/LAN/container network | Reach `:3000` directly (C-02); read plaintext logs (L-02); if DB reachable, read plaintext owner passwords (H-01). |
| A6 | **Repo/history reader** | Read source or git history | Reuse the committed Google API key (S-01); learn the `admin123` default (C-01). |

---

## 4. Assets and impact

| Asset | Threat | Worst case |
|-------|--------|-----------|
| Super-admin panel | C-01, H-02 | Full platform takeover — all tenants, all data |
| WhatsApp account (per restaurant) | C-02 | Account hijack; messages sent as the restaurant (fraud/phishing) |
| Server / host | C-03 | Remote code execution |
| Customer PII (phone, address) | H-03, H-04, L-02 | Mass disclosure / exfiltration |
| Owner credentials | H-01 | Plaintext exposure, credential reuse |
| Order & revenue integrity | H-06, M-05 | Fraudulent/zero-value orders, corrupted reporting |
| External API budget | S-01 | Quota/cost abuse on the leaked key |

---

## 5. Trust-boundary hardening priorities

Mapped to the boundaries in §2:

1. **Boundary 3 (web↔bot):** authenticate and localhost-bind `:3000` (C-02) —
   this is the boundary most at odds with its assumption.
2. **Boundary 1 (auth):** hash + throttle credentials (C-01, H-01, H-02).
3. **Boundary 2 (public):** gate registration and strengthen tracking codes
   (M-01, H-04) to shrink A1/A2 reach.
4. **Boundary 5 (outbound):** validate/allow-list the webhook (H-03).
5. **Boundary 4 (LLM):** move price/confirmation authority server-side (H-06).
6. **Boundary 6 (tenant):** keep `authCheck()` mandatory; consider a global query
   scope as a defense-in-depth backstop.

---

## 6. Out of scope / assumptions

- Third-party dependency CVEs (`vendor/`, `node_modules/`) — not scanned.
- Physical WhatsApp device security and Groq/Google platform security.
- Live/dynamic testing — this model is derived from static review; network
  exposure of port 3000 and the DB is deployment-dependent and should be
  confirmed per environment.

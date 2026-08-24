# Remediation Plan

Prioritized checklist derived from [`SECURITY_ANALYSIS.md`](SECURITY_ANALYSIS.md).
Work top-down: **P0** items are exploitable for full compromise and should be
fixed before the platform is exposed to real tenants or the public internet.

Each item links back to its finding ID and carries its current state:

| Mark | Meaning |
| --- | --- |
| `[x]` | **Applied** — fixed in code, with the file and (where one exists) the test that proves it. |
| `[~]` | **Partial** — the exploitable part is closed, but something named in the item is still outstanding. Read the note. |
| `[ ]` | **Not started** — still open. |

Where an item is deferred, the phase that owns it is named. Phases refer to the
completion plan being worked through in order: 1 runnable · 2 correct ·
**3 secure (this document)** · 4 remove the dead Meta stack · 5 finish
half-built features · 6 tests/CI/repo.

> **Two credentials are still live and weak — owner action, not a code change.**
> See [Outstanding owner actions](#outstanding-owner-actions) at the bottom. No
> code fix in this list makes those safe.

---

## P0 — Do first (compromise-level)

- [~] **Rotate the leaked Google/Gemini API key** (S-01). Removed from
      `BOT_V2_FEATURES.md` (which now carries a stale-section banner) and from the
      git index; `.env.llm` no longer exists. **The key itself has not been
      revoked** — that is an owner action in Google Cloud → APIs & Services →
      Credentials. The outer repo has zero commits, so the key was never actually
      committed, but revoke it anyway: it sat in a plaintext file on disk.
- [x] **Kill the `admin123` default** (C-01). The fallback is gone from
      `config/app.php` and `AdminController`. `ADMIN_PASSWORD` is required, and
      login verifies with `Hash::check()` against a hash — plaintext env values are
      accepted once and rehashed rather than compared as strings. Covered by
      `tests/Feature/AuthTest.php` and `tests/Unit/PasswordHelpersTest.php`.
- [x] **Lock down the bot control server** (C-02). `bot/src/server/InternalServer.js`
      binds `127.0.0.1`, demands `X-Bot-Token` on **every** route, refuses to start
      without `BOT_INTERNAL_TOKEN`, no longer hands `qr_raw` to unauthenticated
      callers, and scopes CORS off `*`. Laravel reaches it through
      `app/Support/BotControlClient.php`, which attaches the token. The misleading
      log line is fixed. Covered by `tests/Feature/BotControlTest.php`.
- [x] **Restrict menu uploads** (C-03). `DashboardController` validates against
      `ALLOWED_MENU_MIMES` (content-sniffed) *and* an independent
      `ALLOWED_MENU_EXTENSIONS` allow-list, renames every file to
      `menu_<id>_<16 hex chars>.<derived ext>`, and creates directories `0755`.
      `public/uploads/.htaccess` is the second, independent layer: PHP engine off,
      handlers stripped, executable extensions denied, `nosniff`, no ExecCGI.
      Covered by `tests/Feature/MenuUploadTest.php`.

## P1 — Do next (high impact)

- [x] **Hash owner passwords everywhere** (H-01). `Hash::make` in both
      `AdminController::storeRestaurant` and `RestaurantController::register`;
      `Hash::check` in `DashboardController::login`, behind an `isHashed()` guard
      (`Hash::check()` *throws* on a non-bcrypt value when `hashing.verify` is on).
      A surviving plaintext row is rehashed on its next successful login rather
      than being left comparable as a string. This also fixed the separate bug
      where a self-registered owner could never log in. Covered by
      `tests/Unit/PasswordHelpersTest.php` + `tests/Feature/AuthTest.php`.
      One legacy plaintext row still exists in the live DB — see the owner actions
      below.
- [~] **Add auth throttling** (H-02). `throttle:` is applied to every login and
      registration route in `routes/web.php`, asserted in `AuthTest`.
      **Still open:** per-account lockout (throttling is per-IP + route) and
      alerting on repeated failures. Deferred — needs a notification channel the
      project does not have yet.
- [x] **Validate the webhook URL** (H-03). `app/Support/WebhookUrlValidator.php`
      enforces https/443, a public destination, and no userinfo, before any
      outbound POST; `bot/src/utils/WebhookUrlValidator.js` mirrors it for the Node
      side, which posts the *same* stored value. Both fail closed on an
      unresolvable host, and both callers send with redirects disabled so a public
      URL cannot 302 into the private network. The PII shared is documented in
      `.env.example`. Covered by `bot/tests/webhook-url-validator.test.mjs`
      (55 cases). DNS-rebinding is explicitly out of scope and noted in both files.
- [x] **Strengthen tracking codes** (H-04). Both generators
      (`Order::generateTrackingCode`, `OrderService.generateTrackingCode`) use a
      CSPRNG with 80 bits of suffix entropy (16 chars from a 32-symbol
      Crockford-style alphabet), non-sequential; the PHP path additionally
      re-rolls against the UNIQUE column so a collision can't fail a real
      customer's order. `/track` and the
      status poll are rate-limited (`throttle:20,1` / `throttle:60,1`). The public
      page now shows a masked address, the rider's first name only, and the rider's
      phone solely while in transit; it renders items from `order_items` rather
      than `notes` (which is a copy of the chat and restates the address), sends
      `no-store` + `noindex` + `no-referrer`, and is disallowed in `robots.txt`.
      Covered by `tests/Feature/OrderTrackingTest.php`.
- [x] **Decide the fate of `routes/api.php`** (H-05). Decision: **leave it
      unregistered.** `bootstrap/app.php` routes only `web:`, `commands:` and
      `health:`, so nothing in that file is reachable. The unauthenticated QR
      proxies were deleted, and `public/order-tracking.html` — a UI that looked
      orders up by phone number alone, i.e. a ready-made front end for a PII dump —
      was deleted with it. **If anyone ever registers `api:`, every endpoint in
      that file needs token auth, per-object authorization and validation first.**
- [ ] **Make order totals authoritative** (H-06). Recompute subtotal/total
      server-side from menu prices before insert (`bot/src/services/OrderService.js`);
      gate creation on a deterministic confirmation signal instead of LLM string
      matching (`ChatHandler.isOrderConfirmed`). **Deferred to Phase 5**, where the
      confirmation flow is reworked — the two changes touch the same code path.

## P2 — Hardening (medium)

- [ ] **Gate self-registration** (M-01): admin approval or verification; raise the
      password policy to ≥12 chars; drop auto-login into sensitive state.
      **Open** — a product decision as much as a security one (it changes signup),
      so it is queued with the Phase 5 feature work.
- [ ] **Remove bot auto-bind** (M-02): require an explicit, verified
      number↔restaurant mapping (`bot/src/services/Database.js`,
      `RestaurantController`). **Open** — Phase 5.
- [x] **Neutralize CSV formula injection** (M-03). `app/Support/CsvSanitizer.php`
      prefixes cells opening with `= + - @ \t \r`, used by both
      `exportCustomersCsv` and `exportSalesReportCsv`. Covered by
      `tests/Feature/CsvExportTest.php`.
- [~] **Ship safe env defaults** (M-04). `.env.example` documents every variable
      the app *and* the bot read, ships no default admin password, requires
      `BOT_INTERNAL_TOKEN`, and carries production guidance on `APP_ENV`/
      `APP_DEBUG` and the session cookie. **Still nominally open:** the template's
      own values remain `APP_ENV=local` / `APP_DEBUG=true` (it is the local-dev
      starting point) and `DB_USERNAME=root`. `WHATSAPP_VERIFY_TOKEN` is *not*
      required — it belongs to the Meta stack being removed in Phase 4.
- [x] **Validate order status enum** (M-05). `DashboardController::updateStatus`
      validates against the explicit six-value list; anything else is rejected
      before it reaches `$order->update()`.

## P3 — Defense in depth (low / info)

- [x] **Session hardening** (L-01). `session()->regenerate()` on every login path
      (`AdminController` ×2, `DashboardController`, `RestaurantController`).
      `SESSION_SECURE_COOKIE`, `SESSION_SAME_SITE=lax` and `SESSION_HTTP_ONLY=true`
      are now explicit in `.env` and `.env.example`. `SECURE_COOKIE` is left
      `false` in the live `.env` **because local dev runs on plain http** — it must
      be flipped to `true` when this is served over HTTPS, or the login cookie
      travels in clear text. That flip is deliberately env-driven rather than
      forced in code, so a misconfigured proxy can't lock the owner out of their
      own panel.
- [ ] **Reduce PII in logs** (L-02): redact/truncate message bodies, mask phone
      numbers, set log retention/rotation. **Open** — Phase 6.
- [ ] **Re-enable the browser sandbox** (L-03): run the bot unprivileged with the
      Puppeteer sandbox on, or containerize with seccomp. **Open** — needs a
      deployment target to test against; revisit at SaaS migration.
- [~] **Tighten `$fillable`** (L-04). `owner_password` is out of mass assignment
      and can only be set explicitly. `is_active`, `plan` and `plan_expires_at`
      remain in `Restaurant::$fillable`; a grep found no request-driven mass
      assignment reaching them (the only `update($validated)` is
      `OrderController::updateStatus`, behind the M-05 enum), so this is latent
      rather than exploitable — but it should still be closed.

---

## Outstanding owner actions

These cannot be fixed from the codebase. Nothing above compensates for them.

1. **`ADMIN_PASSWORD` in `.env` is literally `admin123`** — the value C-01 was
   about. The *fallback* is gone, so the app no longer supplies it for you, but
   the configured value is still that string. Replace it with a long random one.
2. **Restaurant #1's `owner_password` is stored as plaintext `admin123`** — a
   pre-H-01 row. It will be rehashed the next time that owner logs in
   successfully, which does nothing about the fact that the password is guessable.
   Change it, then log in once to convert the row.
3. **Revoke the Gemini API key** in Google Cloud (see S-01).
4. **`settings.admin_password_hash` is unset** — the super-admin has not logged in
   since the auth rework, so the hash store is still empty and login is falling
   back to the (plaintext) env value on first use. Logging in once after changing
   `ADMIN_PASSWORD` populates it.

---

## Suggested verification after fixes

- Attempt admin login with `admin123` → must fail; confirm lockout after N tries.
  *(Currently: fails only once the password is actually changed — see owner
  actions. Rate limiting is in place; per-account lockout is not, see H-02.)*
- From another host, `curl http://<host>:3000/qr-status` → refused (server binds
  loopback); on loopback without `X-Bot-Token` → 401.
- Upload `test.php` as a menu file → rejected; nothing executable lands in
  `public/uploads/`.
- Point `google_sheet_webhook` at `http://127.0.0.1:3000/` → rejected, from both
  the dashboard and the bot.
- Request sequential/guessed tracking codes on `/track` → rate-limited; codes are
  high-entropy and non-sequential.
- Inspect the `restaurants` table → `owner_password` values are hashes, not
  plaintext. *(One legacy row is not — see owner actions.)*
- Export customers containing a name like `=1+1` → opens as literal text, not a
  formula.
- Load `/track/<code>` → response has `Cache-Control: no-store`, the address is
  masked, and the rider's phone is absent unless the order is out for delivery.

Automated equivalents live in `tests/Feature/` (PHP) and `bot/tests/` (Node,
`npm test`).

## Notes on ordering

C-03 and H-03 were only "authenticated" because of M-01 (open registration +
auto-login), which is still open — so their fixes were the right priority. With
both now closed in code, M-01's remaining risk is tenant-namespace pollution and
resource abuse rather than a path to the two flaws above.

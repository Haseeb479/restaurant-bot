# Mobile API Audit — Foodio Owner Mobile Application

## 1. Overview & Objective
This audit analyzes the existing Laravel backend (`restaurant-bot`) to establish the API baseline for the **Foodio Owner Mobile Application** (Phase 0).
The goal is to determine what existing backend endpoints and logic can be reused directly, what gaps exist for mobile clients, and how tenant isolation and authentication should be structured without breaking existing web dashboard or WhatsApp bot functionality.

---

## 2. Authentication & Session Architecture

### Current Web State:
* **Route:** `POST /login` and `POST /dashboard/{id}/login`
* **Mechanism:** Laravel Session Cookies (`restaurant_{id} = true`).
* **Lookup:** Supports lookup by restaurant name (exact or fuzzy), email, or owner WhatsApp phone number.
* **Passwords:** Bcrypt hashed passwords (`owner_password` on `restaurants` table) with fallback for legacy unhashed rows.
* **Lockout:** Per-account brute-force protection (`AccountLockoutService`), audit logging on failed attempts, constant-time dummy hash on missing user.

### Mobile Requirements & Strategy:
* Mobile clients cannot reliably depend on cookie-based web sessions across diverse Android network environments.
* **Sanctum / Token Authentication:** Token-based API authentication (or a secure bearer token exchange) is recommended.
* **Tenant Scoping:** The authenticated token MUST bind directly to `Restaurant` (or owner `User`).
* **Authoritative Rule:** Client requests must **never** supply a trusted `restaurant_id` in headers or body to assert identity. The authenticated identity (`$request->user()` or token payload) must dictate the tenant scope.

---

## 3. Endpoints Inventory & Reusability Assessment

| Feature Area | Existing Route | Existing Controller/Method | Auth / Guard | Response Format | Mobile Readiness & Required Changes |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Auth - Login** | `POST /login` | `web.php` closure (`$ownerLoginHandler`) | Rate limited (5/min), Lockout | HTML Redirect | **Needs API Endpoint:** `POST /api/v1/auth/login` returning JSON token + restaurant profile. |
| **Auth - Logout** | `POST /dashboard/{id}/logout` | `DashboardController@logout` | Session | HTML Redirect | **Needs API Endpoint:** `POST /api/v1/auth/logout` revoking current token. |
| **Auth - Me / Session** | None | None | None | None | **Needs API Endpoint:** `GET /api/v1/auth/me` returning authenticated restaurant metadata. |
| **Dashboard KPI** | `GET /dashboard/{id}/orders/live-feed` | `DashboardController@liveOrdersFeed` | Session (`authCheck`) | **JSON** | **Highly Reusable:** Returns sales, orders count, attention counts (waiting riders, bot status, unavailable items), and live orders array. Needs token-scoped API wrapper. |
| **Live Orders** | `GET /dashboard/{id}/live-orders` | `DashboardController@liveOrders` | Session (`authCheck`) | HTML / Blade | Live feed JSON above provides the raw data. |
| **All Orders** | `GET /dashboard/{id}/orders` | `DashboardController@orders` | Session (`authCheck`) | HTML / Blade | **Needs API Endpoint:** `GET /api/v1/orders` supporting status filter (`pending`, `confirmed`, `preparing`, `ready`, `delivered`, `cancelled`) and pagination. |
| **Order Details** | Included in live feed / orders blade | `DashboardController@orders` | Session (`authCheck`) | Blade | **Needs API Endpoint:** `GET /api/v1/orders/{order}` returning items, options, customer GPS, timeline, and totals. |
| **Update Order Status** | `POST /dashboard/{id}/orders/{order}/status` | `DashboardController@updateStatus` | Session (`authCheck`) | Redirect / JSON | **Highly Reusable:** Updates status (`confirmed`, `preparing`, `out_for_delivery`, `delivered`, `cancelled`), assigns rider, and notifies customer via WhatsApp. Needs token-scoped API wrapper. |
| **POS / Create Order** | `POST /orders/create` (in unused `routes/api.php`) | `OrderController@create` | Unprotected (disabled) | JSON | **Needs API Endpoint:** `POST /api/v1/pos/orders` with strict token auth, authoritative item price calculation, customer resolution, and tenant scoping. |
| **Menu Listing** | `GET /dashboard/{id}/menu` | `DashboardController@menu` | Session (`authCheck`) | HTML / Blade | **Needs API Endpoint:** `GET /api/v1/menu` returning categories, menu items, variants, prices, and availability flags. |
| **Toggle Menu Item** | `POST /dashboard/{id}/menu/item/{item}/toggle` | `DashboardController@toggleItem` | Session (`authCheck`) | JSON | **Highly Reusable:** Returns JSON `{success: true, is_available: bool}`. Needs API route wrapper. |
| **Riders List** | `GET /dashboard/{id}/riders` | `DashboardController@riders` | Session (`authCheck`) | HTML / Blade | **Needs API Endpoint:** `GET /api/v1/riders` returning active riders for assignment. |
| **Customers Directory** | `GET /dashboard/{id}/customers` | `DashboardController@customers` | Session (`authCheck`) | HTML / Blade | **Needs API Endpoint:** `GET /api/v1/customers` with search by name/phone, order history, and totals. |
| **Reports / Analytics** | `GET /dashboard/{id}/reports` | `DashboardController@reports` | Session (`authCheck`) | HTML / Blade | **Needs API Endpoint:** `GET /api/v1/reports` returning aggregated revenue, orders, AOV, top items for given date ranges. |
| **Store Toggle** | `POST /dashboard/{id}/toggle-open` | `DashboardController@toggleOpen` | Session (`authCheck`) | JSON | **Highly Reusable:** Toggles `is_active` / restaurant open status and returns JSON. |
| **WhatsApp Bot Status** | `GET /dashboard/{id}/bot/status` | `DashboardController@botStatus` | Session (`authCheck`) | JSON | **Highly Reusable:** Returns `{status: connected|disconnected|qr, qr_code, ...}`. |
| **Push Token Registration**| None | None | None | None | **Needs API Endpoint:** `POST /api/v1/device-tokens` for Expo push notification delivery. |

---

## 4. Tenant Isolation Review
* The existing web dashboard enforces tenant boundary in `DashboardController::authCheck($id)` by checking `session("restaurant_{$id}") === true`.
* In `tests/Feature/SecurityPhase4TenantIsolationTest.php`, 5 strict tenant isolation tests exist and pass (verifying Restaurant A cannot modify Restaurant B's items, orders, riders, or categories).
* For the Mobile REST API, the tenant must be resolved automatically from the authenticated token. Under no circumstances should `{id}` or `restaurant_id` in request payloads override the token owner.

---

## 5. Mobile Readiness & Identified Gaps
1. **API Routing Registration:** Currently, `routes/api.php` is intentionally disabled in `bootstrap/app.php`. A dedicated, clean API route file or registered route group (`routes/api.php` or `routes/api_v1.php`) must be enabled with proper Sanctum or bearer token middleware.
2. **Missing Authoritative POS Endpoint:** Order creation currently relies on WhatsApp bot logic or internal dashboard methods. A dedicated POS endpoint must calculate item totals server-side and guarantee idempotency.
3. **Push Notification Infrastructure:** No existing table or endpoint exists for saving mobile device tokens (`expo_push_token`). A migration for `device_tokens` or column on `restaurants` / `users` is required in Phase 5.
4. **Realtime Updates:** The web dashboard currently polls `/dashboard/{id}/orders/live-feed` every few seconds. TanStack Query in the mobile app can leverage this exact feed with interval polling or WebSocket/SSE.

---

## 6. Phase 0 Audit Summary

```text
PHASE 0 COMPLETE

Files changed:
- docs/mobile/MOBILE_API_AUDIT.md

Backend APIs reusable:
- DashboardController@liveOrdersFeed (KPIs, active orders, alerts)
- DashboardController@updateStatus (Order state transitions & WhatsApp triggers)
- DashboardController@toggleItem (Item availability toggle)
- DashboardController@toggleOpen (Store online/offline toggle)
- DashboardController@botStatus (WhatsApp connectivity status)

Backend changes required:
- Enable API route group in bootstrap/app.php
- Implement token-based mobile auth endpoints (login, logout, me)
- Implement JSON endpoints for paginated orders, POS order creation, menu catalog, customers, riders, and reports
- Add mobile device push token registration

Risks:
- Preserving WhatsApp bot state machine and web dashboard session logic while introducing token API.
- Ensuring all new API endpoints strictly enforce authenticated tenant scope.

Next phase:
PHASE 1 — Mobile Foundation
```

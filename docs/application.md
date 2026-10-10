# Foodio Owner Mobile Application — Build Specification

## 1. Purpose

Build a production-quality **Foodio Owner Mobile Application** for restaurant owners.

This is NOT a simple mobile wrapper around the existing Laravel dashboard.

The mobile app must feel like a dedicated restaurant operating system/POS that allows an owner to run day-to-day operations from a phone.

The existing Foodio backend, database, WhatsApp bot, order logic, menu logic, pricing rules, delivery logic, and tenant architecture remain the source of truth.

## 2. Critical Development Rule

DO NOT build the entire application in one step.

Implement this specification phase-by-phase.

After each phase:
1. Inspect the existing implementation.
2. Implement only the current phase.
3. Run tests/type checks/build checks.
4. Verify existing Foodio functionality is not broken.
5. Report what changed.
6. Stop and wait for the next phase.

Never silently jump ahead.

## 3. Target Architecture

```text
                         FOODIO BACKEND
                    Laravel + MySQL + Redis
                              |
              +---------------+---------------+
              |               |               |
              v               v               v
        WhatsApp Bot     Owner Web       Owner Mobile
        Node/Evolution   Dashboard       React Native
                                             |
                                           Expo
```

The mobile application is another client of the existing backend.

Do NOT create a second backend or duplicate business logic.

## 4. Target Mobile Stack

Use:
- React Native
- Expo
- TypeScript
- Expo Router
- TanStack Query for server state
- Zustand for small client/UI state where required
- React Hook Form + Zod where useful
- Secure token storage using an appropriate Expo-compatible solution
- Expo Notifications
- Expo Location
- Google Maps or an appropriate React Native map solution
- EAS Build

Use stable/current versions compatible with the installed Expo SDK. Check compatibility before installing packages.

## 5. Project Location

Create the mobile application separately:

```text
foodio/
├── app/
├── bot/
├── database/
├── routes/
├── resources/
├── tests/
└── mobile/
    ├── app/
    ├── components/
    ├── features/
    ├── services/
    ├── stores/
    ├── hooks/
    ├── types/
    ├── constants/
    ├── utils/
    └── assets/
```

Do not restructure unrelated existing folders.

## 6. Product Vision

Foodio Mobile should feel like:

> "I can run my restaurant from my phone."

It should NOT feel like:

> "I opened my desktop admin panel on a phone."

Priorities:
1. Orders
2. POS
3. Restaurant status
4. Notifications
5. Delivery
6. Menu
7. Customers
8. Reports
9. Staff/settings

Avoid huge sidebars, desktop tables squeezed onto mobile, excessive cards, generic admin templates, fake statistics/orders, unnecessary animations, giant headers, and navigation clutter.

## 7. Main Navigation

Primary navigation:

```text
Home
Orders
POS
Delivery
More
```

More:

```text
Menu
Customers
Reports
Staff
Restaurant
WhatsApp
Settings
```

## 8. UI Principles

Use:
- modern typography
- strong hierarchy
- efficient spacing
- subtle borders
- small/medium corner radius
- clear status colors
- loading/empty/error states
- light/dark themes
- touch-friendly controls
- accessible contrast
- restrained transitions
- skeleton loading where appropriate
- confirmation dialogs for destructive actions

Optimize for normal Android phones first.

## 9. Backend Authority Rules

The mobile client must NEVER be authoritative for:
- restaurant_id
- user permissions
- item price
- subtotal
- delivery fee
- total
- payment state
- order ownership
- order status permissions
- tenant access
- menu availability
- customer ownership

Laravel must validate and calculate these values.

Never trust client totals.

## 10. Tenant Isolation

Foodio is a multi-restaurant SaaS.

Every authenticated request must be scoped to the authenticated restaurant.

Never trust a browser/mobile supplied `restaurant_id` as proof of ownership.

Restaurant A must never access Restaurant B's:
- orders
- menu
- customers
- reports
- riders
- settings

Add tenant-isolation tests where appropriate.

## 11. Authentication

Support:
- login
- secure token/session handling
- logout
- session restoration
- expired-token handling
- unauthorized handling
- loading state
- invalid credentials state
- network failure state

Never store authentication secrets in plain AsyncStorage when secure storage is appropriate.

Do not hardcode API credentials.

Use environment configuration. Never place server secrets in `EXPO_PUBLIC_*` variables.

## 12. Phase Plan

### PHASE 0 — Repository Audit

Before writing mobile code:
- inspect Laravel API/routes/controllers/models/services
- identify authentication
- identify order endpoints
- identify menu endpoints
- identify customer endpoints
- identify restaurant endpoints
- identify delivery/rider endpoints
- identify dashboard endpoints
- identify realtime/event infrastructure
- identify missing APIs
- inspect tests

Create:

```text
docs/mobile/MOBILE_API_AUDIT.md
```

Document endpoint, method, authentication, request/response shape, tenant behavior, mobile readiness, and required backend changes.

Do not redesign the backend unnecessarily.

### PHASE 1 — Mobile Foundation

Create `mobile/`.

Set up:
- Expo
- TypeScript
- Expo Router
- ESLint
- formatting
- environment configuration
- theme
- light/dark mode
- reusable typography
- spacing
- buttons
- inputs
- loading/error/empty states

Route structure:

```text
mobile/app/
├── _layout.tsx
├── (auth)/
│   └── login.tsx
└── (app)/
    ├── _layout.tsx
    ├── index.tsx
    ├── orders.tsx
    ├── pos.tsx
    ├── delivery.tsx
    └── more/
```

Do not implement full business features yet.

Acceptance:
- app starts
- TypeScript passes
- navigation works
- theme works
- no fake business data
- Android development build runs

### PHASE 2 — API + Authentication

Create a clean API client under:

```text
mobile/services/api/
```

Include:
- base URL
- auth headers
- request handling
- response parsing
- timeout/error handling
- 401 handling
- safe retries where appropriate

Implement:
- login
- restore session
- logout

Use TanStack Query for server data.

Create typed API models.

Acceptance:
- real Laravel login works
- session survives restart
- logout works
- expired session redirects
- API errors are user-friendly
- no hardcoded restaurant ID

### PHASE 3 — Home / Command Center

Build the owner home screen using real backend data:

```text
Restaurant status
Today's sales
Today's orders
Average order value
Completed orders
Needs Attention
Live orders
Recent activity
```

Implement proper loading/empty/error/success states.

Online/offline control must call the backend.

No fake numbers.

### PHASE 4 — Orders

Build:
```text
All
New
Confirmed
Preparing
Ready
Out for Delivery
Delivered
```

Order list:
- order number
- customer
- item summary
- total
- payment method
- time
- status

Order detail:
- customer
- phone
- exact delivery coordinates when available
- address/label
- items
- variants/sizes
- prices
- subtotal
- delivery fee
- total
- payment
- timeline
- authorized status actions

Status changes must be authorized by Laravel.

### PHASE 5 — Push Notifications

Flow:

```text
Backend creates order
        ↓
Backend event
        ↓
Push notification
        ↓
Owner taps notification
        ↓
Mobile opens exact order
```

Notification must NEVER create the order.

Implement:
- permission handling
- device token registration
- notification routing
- foreground behavior
- background behavior
- logout token handling

### PHASE 6 — POS

Build:

```text
Category
  ↓
Menu item
  ↓
Variant/size
  ↓
Quantity
  ↓
Cart
  ↓
Customer
  ↓
Delivery/Pickup
  ↓
Payment
  ↓
Review
  ↓
Create Order
```

Requirements:
- backend menu prices
- variants/sizes
- availability
- quantities
- customer selection
- new customer
- delivery/pickup
- payment method
- review

Backend recalculates everything.

Never trust a client total.

### PHASE 7 — Menu Management

Implement:
- categories
- menu items
- images
- descriptions
- availability
- variants
- sizes
- prices
- deals where supported

Example:

```text
Shahi Pizza
Small    Rs 900
Medium   Rs 1250
Large    Rs 1750
```

Do not break existing WhatsApp menu behavior.

### PHASE 8 — Customers

Implement:
- customer search
- customer profile
- phone
- saved addresses
- order history
- total orders
- total spending

Never expose customers from another restaurant.

### PHASE 9 — Delivery

Implement:
- active deliveries
- riders
- assignment
- delivery status
- customer location
- rider location
- map
- distance
- ETA where reliable
- tracking

Do not present straight-line distance as road ETA.

Respect location permissions and privacy.

### PHASE 10 — Reports

Implement:
- today
- yesterday
- 7 days
- 30 days
- custom range

Metrics:
- sales
- orders
- average order value
- completed
- cancelled
- delivery fees
- payment methods
- top-selling items

Use real backend data.

### PHASE 11 — Restaurant / Settings

Implement:
- restaurant profile
- opening hours
- online/offline
- delivery radius
- delivery fee
- minimum order
- payment methods
- WhatsApp configuration/status
- notification settings

Never display raw WhatsApp secrets.

### PHASE 12 — Staff

Potential roles:

```text
Owner
Manager
Cashier
Kitchen
Rider
```

Backend enforces permissions. UI is not the security boundary.

### PHASE 13 — Offline / Poor Network

Implement:
- cached read-only data
- retry states
- connection status
- safe mutation retry
- idempotency for order creation

Never create duplicate orders from repeated taps/network retries.

Do not blindly queue financially critical mutations offline.

### PHASE 14 — Production Hardening

Audit:
- secure auth storage
- token expiry
- HTTPS
- API validation/rate limits
- tenant isolation
- order idempotency
- authoritative pricing
- transaction safety
- notification token lifecycle
- location permissions/privacy/battery impact
- crash/error handling
- Android back behavior
- no secrets in source
- no PII in logs
- no hardcoded tenant IDs

### PHASE 15 — Release Preparation

Prepare:
- Android application ID
- icon
- splash screen
- production environment
- EAS configuration
- Android internal build
- release build
- versioning
- crash monitoring
- production API URL

Do not publish publicly until production tests pass.

## 13. Design Direction

Foodio should visually feel like a premium restaurant POS.

Use qualities from modern finance/productivity apps and restaurant POS systems without copying another company's exact UI.

Recommended:
- modern sans-serif
- neutral base
- one Foodio accent
- semantic status colors
- subtle separators
- moderate radius
- minimal shadows
- dark mode
- light mode

Animations should improve feedback, not decorate every screen.

## 14. Reusable Components

Create reusable components:

```text
AppButton
AppInput
AppModal
StatusBadge
OrderCard
OrderStatusTimeline
MoneyText
EmptyState
ErrorState
LoadingState
SearchBar
BottomSheet
ConfirmDialog
MenuItemCard
CustomerCard
MetricCard
```

Do not duplicate equivalent components.

## 15. Suggested Data Layer

```text
mobile/
├── services/
│   ├── api/
│   ├── auth/
│   ├── notifications/
│   └── location/
├── features/
│   ├── auth/
│   ├── dashboard/
│   ├── orders/
│   ├── pos/
│   ├── menu/
│   ├── customers/
│   ├── delivery/
│   ├── reports/
│   └── settings/
├── components/
├── stores/
├── hooks/
├── types/
└── utils/
```

Keep feature-specific code together.

## 16. Testing Requirements

At minimum test:

### Authentication
- successful login
- failed login
- session restoration
- logout

### Orders
- order list
- order detail
- status permissions
- tenant isolation

### POS
- variant selection
- quantity
- unavailable item
- backend pricing
- duplicate submission

### Menu
- item
- variant
- availability

### Notifications
- token registration
- notification routing

### Delivery
- location permission
- correct order location
- tenant isolation

### Build
- TypeScript
- lint
- Android build

## 17. Agent Operating Rules

These rules apply to every phase:

1. Inspect before modifying.
2. Do not assume an endpoint exists.
3. Do not invent backend responses.
4. Do not create fake data to hide missing APIs.
5. Reuse existing Foodio business logic.
6. Preserve working WhatsApp behavior.
7. Preserve existing web dashboard behavior.
8. Do not perform unrelated refactoring.
9. Do not rename existing database fields without necessity.
10. Do not introduce microservices.
11. Do not introduce a second database.
12. Do not duplicate pricing/order logic.
13. Do not trust mobile-supplied restaurant IDs.
14. Do not expose secrets.
15. Do not commit `.env`.
16. Keep changes focused on the current phase.
17. Add/update tests for changed behavior.
18. Run relevant checks before declaring completion.
19. If a required backend capability is missing, document it and implement the smallest compatible backend change.
20. Never remove a working feature just to make mobile implementation easier.

## 18. Definition of Done

A phase is complete only when:

```text
Implementation
     +
Real API integration
     +
Correct tenant behavior
     +
Loading/error/empty states
     +
Tests/checks
     +
No regression
     +
Professional mobile UX
```

A rendered screen alone does not count as completion.

## 19. First Agent Instruction

The FIRST coding-agent task is ONLY:

> Execute PHASE 0 — Repository Audit.

Do not build the mobile UI yet.

Inspect the repository and produce:

```text
docs/mobile/MOBILE_API_AUDIT.md
```

The audit must identify:
- authentication
- dashboard data
- orders
- menu
- customers
- restaurant settings
- delivery/riders
- notifications/realtime
- existing API gaps
- tenant isolation concerns
- mobile-specific backend requirements

At the end, report:

```text
PHASE 0 COMPLETE

Files changed:
...

Backend APIs reusable:
...

Backend changes required:
...

Risks:
...

Next phase:
PHASE 1 — Mobile Foundation
```

Stop after Phase 0.

## 20. Success Target

The final product should allow:

```text
Open Foodio
   ↓
See today's business
   ↓
Receive new order
   ↓
Accept order
   ↓
Prepare order
   ↓
Assign delivery
   ↓
Track rider
   ↓
Complete order
   ↓
Review sales
   ↓
Manage menu/customers/settings
```

All from the mobile application.

The goal is not merely to make Foodio mobile.

The goal is to make **Foodio usable as the owner's primary restaurant operating application.**

# Security Documentation

This folder contains the security analysis for the WhatsApp Restaurant Ordering platform
(Laravel backend + Node.js `whatsapp-web.js` bot + MySQL).

## Contents

| File | Purpose |
|------|---------|
| [`SECURITY_ANALYSIS.md`](SECURITY_ANALYSIS.md) | Full findings report — every issue with location, impact, and fix. |
| [`REMEDIATION.md`](REMEDIATION.md) | Prioritized, actionable fix checklist (start here to plan work). |
| [`threat-model.md`](threat-model.md) | System architecture, trust boundaries, and attacker profiles. |

## Scope

- **In scope:** first-party application code — Laravel (`app/`, `routes/`, `config/`),
  the Node.js bot (`bot/src/`), Blade views (`resources/views/`), and deployment/config
  defaults (`.env.example`, `config/*`).
- **Out of scope:** third-party dependencies in `vendor/` and `node_modules/`
  (flagged only where a default/version is directly relevant), and live penetration
  testing against a running host. This is a static (source-level) review.

## Methodology

Manual source review focused on the OWASP Top 10 plus LLM- and WhatsApp-specific risks:

- Authentication & session management (admin + owner logins)
- Authorization & multi-tenant isolation (IDOR, cross-tenant access)
- Injection (SQL, command, CSV/formula, prompt injection)
- Secrets management & configuration hardening
- File upload handling
- Server-side request forgery (SSRF) and the internal push API
- Sensitive data exposure (PII, tracking codes, logs)

## Severity legend

| Severity | Meaning |
|----------|---------|
| **Critical** | Remote/unauthenticated compromise, account takeover, or full data breach. Fix immediately. |
| **High** | Serious impact, usually needing a low bar (e.g. any tenant) or exposing credentials/PII at scale. |
| **Medium** | Real risk requiring specific conditions or limited to semi-trusted actors. |
| **Low** | Hardening gap or defense-in-depth; limited direct impact. |
| **Info** | Best-practice note; no direct exploit. |

## How to use this report

1. Read [`REMEDIATION.md`](REMEDIATION.md) for the prioritized task list **and the
   current state of each item** — it is the living document; the other two are the
   original point-in-time review.
2. Consult [`SECURITY_ANALYSIS.md`](SECURITY_ANALYSIS.md) for the detail behind
   each finding ID.
3. Findings reference source locations as `path:line` relative to the project root.
   **Line numbers in `SECURITY_ANALYSIS.md` and `threat-model.md` are from the
   original review and many have drifted** — the fixes moved code around. Trust the
   file paths and finding IDs; re-grep for the symbol rather than jumping to a line.

> **Status:** the analysis itself was documentation-only, but the fixes have since
> been applied to the code. `REMEDIATION.md` records what is done, what is partial,
> and which credentials still need rotating by hand (no code change can do that).


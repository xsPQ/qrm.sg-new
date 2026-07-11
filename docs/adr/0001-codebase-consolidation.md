# ADR 0001 — Codebase Consolidation (Gate P2)

- Status: Accepted
- Date: 2026-07-10
- Supersedes: the divergent managed skeleton (managed project `_default`, 39 PHP, API-only)
- Refs: DEV-636 (P2 Reconciliation), DEV-632 (Gate failure), DEV-643 (this consolidation)

## Context

The P2 reconciliation (DEV-636) found that the managed Paperclip project folder and an
alternate codebase had diverged. The managed project held only the API skeleton
(QR domain, API routes, T07 gating, T13 password, the DEV-613 security fix). The alternate
codebase held the complete P2 feature set (all UI, Filament admin, Stripe billing, mail
flows, free-tier limit, immutable entitlement snapshot) plus ~70 tests, but was **not**
under version control — a data-loss risk.

Two structures existed side by side (domain layout, service layout, plan representation,
admin panel generation, entitlement model). The reconciliation required one canonical,
git-tracked project with all 13 P2 deliverables present, tested and consistent.

## Decision

Designate the alternate codebase as the canonical base and resolve every divergence in its
favour, because it is strictly the superset (109 PHP + 70 tests, all features, immutable
entitlement model) and is internally consistent. Port the managed-only work into it.

### Chosen architecture (single, consistent structure)

| Aspect | Decision |
|---|---|
| QR domain | `app/Domain/QrTypes/` (type content + handlers) |
| Billing domain | `app/Domain/Billing/Plan.php` |
| Entitlement domain | `app/Domain/Entitlement/` — `EntitlementGate` (checks/display) + immutable `EntitlementSnapshot` value object |
| Services | `app/Services/QrTypes/` + `QrCodeService`, `QrCodeResolver`, `QrCodeRouteService`, `QrPreviewService` |
| Plan representation | immutable `EntitlementSnapshot` (JSON column on `qr_codes`), NOT a separate table/enam |
| Admin panel | Laravel **Filament v4** (fully implemented, `/admin`) |
| Billing | Laravel Cashier (Stripe Checkout, Customer Portal, idempotent webhook) |
| Roles/permissions | `spatie/laravel-permission` |

### Managed-only work ported into the canonical base

1. **DEV-613 security fix (F1/F2/F3)** — the single critical port.
   - F1/F2: `QrCodeResolver::isPasswordVerified()` no longer accepts a query-string
     `?password=`. The GET resolver honours only the signed grant cookie issued by the
     dedicated `POST /r/{code}/password` endpoint, closing the unthrottled GET brute-force
     bypass and the URL/log password leak.
   - F3: `QrCode::$hidden = ['password_hash']` so the hash never leaks via model
     serialization (API resources, JSON, logs).
   - Regression coverage added/updated in `QrCodeResolverTest`.
2. **T08 immutability** — the alternate's immutable `EntitlementSnapshot` (final value
   object + `QrCode::booted()` saving guard + non-fillable column) is strictly stronger than
   the managed's dangling `9d198ef` (separate table + `Enums/Plan`). No cherry-pick needed;
   grandfathering is covered by `EntitlementSnapshotGrandfatheringTest`.
3. **T07 feature gating** and **T13 password protection** — verified present and covered.

### Divergences intentionally dropped (superseded by the canonical base)

- Managed `app/Domain/Qr/` flat layout → replaced by `Domain/QrTypes`.
- Managed `app/Services/Qr/` → replaced by `Services/QrTypes` + `QrCodeService`.
- Managed `app/Enums/Plan.php` simple enum → replaced by immutable snapshot.
- Managed Filament v3 (composer dep, no code) → replaced by Filament v4 (implemented).
- Managed "Cashier absent" → Cashier installed and configured.
- Managed `EntitlementService` + simple `EntitlementSnapshot` → `EntitlementGate` + immutable
  domain model.

## Consequences

- One canonical, git-tracked Laravel 13 / PHP 8.3 project holds every P2 deliverable.
- The public resolver is reachable at `GET /{codeOrAlias}` (catch-all, registered last) and
  `GET /r/{code}`; passwords are verified only through `POST /r/{code}/password`.
- Test suite: **535 passing, 0 failing** (sqlite in-memory, sync queue, array mailer).
- The consolidated codebase is the managed project going forward.

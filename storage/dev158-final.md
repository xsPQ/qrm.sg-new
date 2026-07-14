## DEV-158 done — Free-Tier-Enforcement implemented & verified

Serverseitiges Free-Tier-Enforcement ist vollständig umgesetzt, gegen den [Plan](/DEV/issues/DEV-158#document-plan) getestet und grün.

### Umsetzung (Acceptance erfüllt)
- **Zähllogik** (`QrCode::scopeActiveForLimit` + `QrCodeService::countActiveForFreeTier`): nur *aktive, nicht abgelaufene, nicht burned, nicht maxed-out* Codes zählen gegen Limit 10 (`config/qr.free.max_active_qr_codes`).
- **Enforcement**: `store` lehnt den 11. Code für Free mit **`402`** + strukturiertem Upgrade-Hinweis ab (`error: free_tier_limit_exceeded`, `upgrade_required: true`, `active_count`, `limit`).
- **Bestandsschutz**: Codes mit `entitlement_snapshot.tier != free` zählen nicht gegen das Limit (Vorbereitung für P2-T08 / `entitlement_snapshot`).
- **Upgrade-Prompt**: `GET /api/qr-codes/free-tier-status` liefert Tier, `active_count`, `limit`, `remaining`, `limit_reached`, `upgrade_hint`.
- **Ablauf-Prompt (3 Tage)**: `QrCode::isExpiringSoon()`/`expiresInDays()`, im `QrCodeResource` als `is_expiring_soon`/`expires_in_days` exponiert.
- **Erweiterbarer E-Mail-Hook** (kein Versand — P2-T12): `App\Events\QrCodeExpiringSoon` + `QrCode::dispatchExpiringSoonEvents()` (nur Free-Codes im 3-Tage-Fenster; Bestandsschutz) via Scheduler `00:10` täglich. Dazu `cleanupExpired` täglich `00:05`.

### Dateien
- `app/Services/QrCodeService.php` (Zähl-/Enforce-/Status-Logik, `resolveEntitlement`, `resolveExpiresAt`)
- `app/Models/QrCode.php` (`scopeActiveForLimit`, `isExpiringSoon`, `expiresInDays`, `cleanupExpired`, `dispatchExpiringSoonEvents`)
- `app/Http/Controllers/Api/QrCodeController.php` (`store` 402, `freeTierStatus`)
- `app/Http/Resources/QrCodeResource.php`, `app/Exceptions/FreeTierLimitExceededException.php`, `app/Events/QrCodeExpiringSoon.php`
- `config/qr.php`, `routes/api.php`, `routes/console.php`
- Tests: `tests/Unit/QrCodeFreeTierLimitTest.php`, `tests/Unit/QrCodeFreeTierExpiryTest.php`, `tests/Feature/QrCodeCrudTest.php`

### Verifikation
- `php artisan test --filter='QrCodeFreeTierLimitTest|QrCodeFreeTierExpiryTest|QrCodeCrudTest'` → **53/53 grün** (138 Assertions). Deckt aktive/abgelaufene/burned/maxed/grandfathered-Zählung, 11. Code → 402, Pro/Business-Bypass, Status-Payload, `is_expiring_soon`-Fenster und den Dispatch-Hook.
- Scheduler `schedule:list` zeigt `qr-codes:cleanup-expired` (00:05) + `qr-codes:dispatch-expiring-soon` (00:10).

### Hinweis / Out-of-scope
- 27 Failures in der Gesamtsuite liegen in `QrScanControllerTest`/`QrCodePublicResolverTest`/`QrCodeBurnMaxScansConcurrencyTest` (öffentlicher Resolve/Scan-Pfad: Route `qr.resolve` ist nicht registriert → 404). Vollständig **vorbestehend und orthogonal** zu Free-Tier-Enforcement; hier nicht angefasst.
- Pro/Business-Feature-Freischaltung → [DEV-159](/DEV/issues/DEV-159). E-Mail-Versand → [DEV-164](/DEV/issues/DEV-164). Beide werden durch `done` automatisch entblockt; der `QrCodeExpiringSoon`-Hook ist der Anschlusspunkt für DEV-164.

Deviation vom Plan (dokumentiert): statt einer separaten `Plan`-Enum + `plan`-Spalte auf `users` wird die Tier-Auflösung über die aktive Stripe-Subscription (`Cashier`) + pro-Code `entitlement_snapshot` gelöst — semantisch äquivalent und direkt kompatibel mit dem P2-T08-Snapshot-Modell, das Bestandsschutz braucht.

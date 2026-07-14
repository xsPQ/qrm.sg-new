# FEAT-07: QR-Code-Versionshistorie

## Status
❌ Nicht umgesetzt

## Priorität
Mittel

## Abhängigkeiten
Keine

## Beschreibung
Bei jeder Änderung an einem QR-Code (content, title, settings, alias) wird eine automatische Version in einer `qr_code_revisions`-Tabelle gespeichert. Der Nutzer kann frühere Versionen in der Editor-Historie einsehen und wiederherstellen.

## Datenmodell

### Neue Tabelle: `qr_code_revisions`

| Feld | Typ | Beschreibung |
|---|---|---|
| `id` | BIGINT (PK) | Revision-ID |
| `qr_code_id` | BIGINT (FK) | Zugehöriger QR-Code |
| `user_id` | BIGINT (FK) | Wer hat geändert |
| `version` | INT | Versionsnummer (inkrementell pro QR-Code) |
| `snapshot` | JSONB | Vollständiger Snapshot von content, title, settings, alias, status |
| `change_description` | TEXT | Auto-generierte Beschreibung der Änderung |
| `created_at` | TIMESTAMPTZ | Änderungszeitpunkt |

## Anforderungen

1. **Automatische Speicherung:** Bei jedem `save()` auf QrCode, das Felder ändert, wird ein Revision-Eintrag erstellt
2. **Snapshot:** Speichert den VORHERIGEN Zustand (nicht den neuen)
3. **UI:** Editor erhält einen "History"-Tab/Bereich mit:
   - Timeline der Änderungen (Zeit, Nutzer, Beschreibung)
   - Diff-Anzeige (was hat sich geändert)
   - "Restore"-Button pro Version
4. **Wiederherstellung:** Stellt content, title, settings wieder her (nicht type, nicht route)
5. **Begrenzung:** Maximal 50 Revisionen pro QR-Code (älteste werden gelöscht)
6. **Cache-Invalidierung:** Resolver-Cache wird bei Restore gelöscht

## Verifikation
- `php artisan test --filter=QrCodeRevisionTest`
- Editor History-Tab im Browser prüfen
- Restore → Resolver zeigt alten Inhalt

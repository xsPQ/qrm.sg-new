# Phase P2: Statusänderung end-to-end
Ziel: Nutzer ändern sicher eigenen Status und sehen ihn im Board. Scope FR-002..004/AC-002..004; nicht: Redesign, Historie, Kalender. Vorgänger P1: Schema, Shell, Identity Adapter, Tests.

| Task | Ziel | Depends | parallel |
|---|---|---|---|
| P2-T01 | Domainvalidator | P1 | ja |
| P2-T02 | Repository-Upsert | P1 | ja |
| P2-T03 | PATCH /api/me/availability | T01,T02 | nein |
| P2-T04 | UI-Formular | T03 | nein |
| P2-T05 | Security/E2E | T03,T04 | nein |

Gate: AC grün, Security ohne High/Critical, Docker-Smoke. Rollen/Budget aus Analyse.

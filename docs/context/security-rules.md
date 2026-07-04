# Project Security Rules

Schutzdaten/Trust Boundaries: `[Angaben]`. Auth/Authz/Secret Store/Retention: `[Angaben]`.

Keine Secrets, Tokens, private Schlüssel, Produktionsdaten oder unredigierte Dumps in Repo, Prompts, Logs oder Artefakten. `.env.example` enthält Namen und sichere Beispiele, nie Werte. Eingaben validieren, Ausgaben kontextgerecht encoden, Rechte minimal halten, Timeouts/Rate Limits setzen, sensitive Logs redigieren.

Security Review ist zwingend bei Auth, Mandanten, PII, Zahlung/Lizenz, Upload/Pfad, Migration, externen APIs, Secrets und Deployment. Critical/High blockieren Release, sofern keine dokumentierte autorisierte Ausnahme existiert.

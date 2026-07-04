# Project Testing Rules

Frameworks/Befehle: `[unit]`, `[integration]`, `[e2e]`, `[coverage]`.

Neue/geänderte Funktion: Happy Path plus relevante Edge-/Errorpfade. Bugfix: reproduzierender Regressionstest. API: Vertrag/Status/Fehler. Security: negativer Test. Migration: Vorwärts-, Daten- und möglichst Rollbacknachweis. Manuell nur mit Grund, exakten Schritten und Erwartung.

Tests sind isoliert, deterministisch und verändern keine Produktion. Gate: taskbezogene Tests und erforderlicher Gesamtcheck grün.

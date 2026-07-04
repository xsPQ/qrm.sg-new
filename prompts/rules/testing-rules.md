# Testing Rules

Test Verhalten statt Implementierung. Pro Funktion Happy Path und relevante Edge-/Errorpfade; Bugfix mit Regressionstest; API mit Vertrag/Status; Security negativ; Migration mit Datennachweis.

Tests sind isoliert, deterministisch, parallelverträglich und ohne Produktion. Mock nur externe Grenzen. Manuell nur mit Begründung, Schritten, Erwartung, Prüfer/Datum.

Nicht „grün“ behaupten ohne Befehl, Exitcode und Ergebnis. Coverage ersetzt keine Risikofälle.

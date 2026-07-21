# Seshat

Timesheet-productiviteitsoverzicht op basis van goedgekeurde urenstaten uit Business Central.

## Configuratie

Pas de werksoortlijsten handmatig aan:

- `web/data/seshat_productive_work_types.json` — productieve werksoorten (blauw in pie-chart)
- `web/data/seshat_leave_work_types.json` — verlof (groen in pie-chart)
- `web/data/seshat_ignored_work_types.json` — volledig genegeerd (niet zichtbaar)

## Cache

Goedgekeurde timesheetregels worden permanent per week opgeslagen in `web/cache/seshat/`.
Verhoog `SESHAT_CACHE_VERSION` in `web/seshat_config.php` om oudere cachebestanden automatisch te negeren.

## Starten

De applicatie draait vanuit `web/` via `index.php`.

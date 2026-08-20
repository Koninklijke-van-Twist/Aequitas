# Aequitas

Inkoopprijsvergelijking op basis van `AppItemCard` en `Prijslijstregels` uit Business Central.

## Data

`nightly.php` haalt 's nachts via GET beide BC-entities **pagina voor pagina** op (`$top` + `odata.maxpagesize`) en schrijft elke chunk naar JSONL in `web/cache/aequitas/` tot de volgende nightly-run. `index.php` leest alleen die cache.

## Starten

De applicatie draait vanuit `web/` via `index.php`. Roep `nightly.php` aan om de cache te vullen of te verversen.

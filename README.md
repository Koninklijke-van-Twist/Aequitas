# Aequitas

Inkoopprijsvergelijking op basis van `AppItemCard` en `Prijslijstregels` uit Business Central.

## Data

`nightly.php` haalt per bedrijf de volledige actuele Prijslijstregels en AppItemCard (Artikelen) op en schrijft de cache. Alleen afwijkende bedragen en dubbele regels blijven in de item-cache. `index.php` leest alleen die cache. `hourly.php` haalt geen AppItemCard op.

## Starten

De applicatie draait vanuit `web/` via `index.php`. Roep `nightly.php` aan om de cache te vullen of te verversen.

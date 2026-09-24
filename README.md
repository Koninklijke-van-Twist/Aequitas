# Aequitas

Inkoopprijsvergelijking op basis van `AppItemCard` en `Prijslijstregels` uit Business Central.

## Data

`nightly.php` haalt per bedrijf de actuele Prijslijstregels en AppItemCard (Artikelen) op. De Artikelen-sync is incrementeel (`Last_Date_Modified` plus gewijzigde prijsregels) alleen als `items_backfill_done` true is en er een watermark plus item-cache is. Zonder voltooide backfill volgt een volledige sync; de marker wordt pas gezet nadat die slaagt. Alleen afwijkende bedragen en dubbele regels blijven in de item-cache. `hourly.php` haalt geen AppItemCard op.

## Starten

De applicatie draait vanuit `web/` via `index.php`. Roep `nightly.php` aan om de cache te vullen of te verversen.

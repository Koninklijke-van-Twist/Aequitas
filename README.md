# Aequitas

Inkoopprijsvergelijking op basis van `AppItemCard` en `Prijslijstregels` uit Business Central.

## Data

`nightly.php` haalt per bedrijf de actuele Prijslijstregels en AppItemCard (Artikelen) op. Bestaat er al een item-cache met watermark, dan is de Artikelen-sync incrementeel (`Last_Date_Modified` plus gewijzigde prijsregels); anders een volledige sync van de prijsindex. Alleen afwijkende bedragen en dubbele regels blijven in de item-cache. `hourly.php` haalt geen AppItemCard op.

## Starten

De applicatie draait vanuit `web/` via `index.php`. Roep `nightly.php` aan om de cache te vullen of te verversen.

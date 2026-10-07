# Aequitas

Inkoopprijsvergelijking op basis van `AppItemCard` en `Prijslijstregels` uit Business Central.

## Data

`nightly.php` haalt per bedrijf de volledige actuele Prijslijstregels en AppItemCard (Artikelen) op en schrijft de cache. Alleen afwijkende bedragen en dubbele regels blijven in de item-cache. `index.php` leest alleen die cache. `hourly.php` haalt geen AppItemCard op.

## Starten

De applicatie draait vanuit `web/` via `index.php`. Roep `nightly.php` aan om de cache te vullen of te verversen (webverzoek of CLI/cron: `php web/nightly.php`). `hourly.php` doet geen Business Central-call.

## Toegang

`$allowedUsers` in `web/auth.php` is optioneel en werkt zoals bij Kothar/Ktesios: weglaten of `[]` laat elke geldige Entra-login toe; een lijst met e-mailadressen beperkt Aequitas tot die accounts. Zet de variabele op topniveau in `auth.php`, niet in een functie.

## Mímir en Business Central

Zet in `web/auth.php` (niet in git) `$mimirApi` én de directe BC-gegevens naast elkaar: `$baseUrl`, `$environment`, `$auth_list` en `$auth`. Zie `web/auth_TEMPLATE.php`.

Met `$mimirApi` gezet gaan OData-fetches (live pagina's, `nightly.php` via web én via CLI) eerst naar Mímir. Faalt die aanroep, dan gebruikt hetzelfde verzoek de oude directe BC-route en slaat Mímir voor de rest van dat PHP-proces over. Zonder `$mimirApi` blijft alleen die directe route actief. Ontbreken de BC-gegevens, dan komt de oorspronkelijke Mímir-fout terug.

De fallback staat in `web/odata.php`. Dat bestand verder niet wijzigen; deze fallback is een goedgekeurde uitzondering (Tim Falken, 2026-09-28).

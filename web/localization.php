<?php

/**
 * Constants
 */

const FLAG_SVGS = [
    'nl' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 600"><rect width="900" height="600" fill="#AE1C28"/><rect width="900" height="400" fill="#fff"/><rect width="900" height="200" fill="#fff"/><rect width="900" height="200" y="0" fill="#AE1C28"/><rect width="900" height="200" y="200" fill="#fff"/><rect width="900" height="200" y="400" fill="#21468B"/></svg>',
    'en' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 40"><clipPath id="a"><path d="M0 0v40h60V0z"/></clipPath><clipPath id="b"><path d="M30 20h30v20zv20H0zH0V0zV0h30z"/></clipPath><g clip-path="url(#a)"><path d="M0 0v40h60V0z" fill="#012169"/><path d="M0 0l60 40m0-40L0 40" stroke="#fff" stroke-width="8"/><path d="M0 0l60 40m0-40L0 40" clip-path="url(#b)" stroke="#C8102E" stroke-width="5"/><path d="M30 0v40M0 20h60" stroke="#fff" stroke-width="13"/><path d="M30 0v40M0 20h60" stroke="#C8102E" stroke-width="8"/></g></svg>',
    'de' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 5 3"><rect width="5" height="3" y="0" fill="#000"/><rect width="5" height="2" y="1" fill="#D00"/><rect width="5" height="1" y="2" fill="#FFCE00"/></svg>',
    'fr' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 600"><rect width="900" height="600" fill="#ED2939"/><rect width="600" height="600" fill="#fff"/><rect width="300" height="600" fill="#002395"/></svg>',
];

const SUPPORTED_LANGUAGES = [
    'nl' => ['flag' => '🇳🇱', 'label' => 'Nederlands'],
    'en' => ['flag' => '🇬🇧', 'label' => 'English'],
    'de' => ['flag' => '🇩🇪', 'label' => 'Deutsch'],
    'fr' => ['flag' => '🇫🇷', 'label' => 'Français'],
];

const LOCALE_BY_LANG = [
    'nl' => 'nl-NL',
    'en' => 'en-GB',
    'de' => 'de-DE',
    'fr' => 'fr-FR',
];

const TRANSLATIONS = [
    'nl' => [
        'lang.menu_aria' => 'Taal kiezen',
        'lang.switch_to' => 'Schakel naar %s',
        'app.title' => 'Aequitas',
        'aequitas.hero.title' => 'Inkoopprijzen',
        'aequitas.hero.subtitle' => 'Alleen afwijkende inkoopprijzen en dubbele prijslijstregels.',
        'aequitas.label.company' => 'Bedrijf',
        'aequitas.label.item_no' => 'Artikelnummer',
        'aequitas.label.vendor' => 'Leverancier',
        'aequitas.label.search' => 'Zoeken',
        'aequitas.label.page_size' => 'Regels per pagina',
        'aequitas.placeholder.item_no' => 'Filter op artikelnummer',
        'aequitas.placeholder.vendor' => 'Alle leveranciers',
        'aequitas.placeholder.search' => 'Zoek in alle kolommen',
        'aequitas.page_size.unlimited' => 'Onbeperkt',
        'aequitas.pager.prev' => 'Vorige',
        'aequitas.pager.next' => 'Volgende',
        'aequitas.pager.status' => 'Pagina %1$d van %2$d · %3$d regels',
        'aequitas.col.item_no' => 'Artikelnummer',
        'aequitas.col.description' => 'Artikelomschrijving',
        'aequitas.col.vendor_no' => 'LeverancierNr',
        'aequitas.col.purchase_price' => 'Inkoopprijs',
        'aequitas.col.last_direct_cost' => 'Laatste directe kosten',
        'aequitas.col.delta' => 'Δ€',
        'aequitas.col.minimum_quantity' => 'MinimumAantal',
        'aequitas.col.base_unit' => 'Eenheid Basis',
        'aequitas.col.unit' => 'Eenheid',
        'aequitas.col.starting_date' => 'Begindatum',
        'aequitas.col.ending_date' => 'Einddatum',
        'aequitas.col.settlement_price' => 'Vaste Verekeningsprijs',
        'aequitas.col.price_list' => 'Prijslijst',
        'aequitas.col.line_no' => 'Regel',
        'aequitas.cached_at' => 'Laatst bijgewerkt: %s',
        'aequitas.row_count' => '%d regels',
        'aequitas.empty.cache' => 'Nog geen nachtelijke data. Start nightly.php om AppItemCard en Prijslijstregels op te halen.',
        'aequitas.empty.rows' => 'Geen afwijkende of dubbele prijslijstregels gevonden.',
        'aequitas.stale.cache' => 'De nachtelijke update is verouderd. Start nightly.php opnieuw.',
        'aequitas.modal.title' => 'Dubbele regels gedetecteerd',
        'aequitas.modal.close' => 'Sluiten',
    ],

    'en' => [
        'lang.menu_aria' => 'Choose language',
        'lang.switch_to' => 'Switch to %s',
        'app.title' => 'Aequitas',
        'aequitas.hero.title' => 'Purchase prices',
        'aequitas.hero.subtitle' => 'Only mismatched purchase prices and duplicate price list lines.',
        'aequitas.label.company' => 'Company',
        'aequitas.label.item_no' => 'Item no.',
        'aequitas.label.vendor' => 'Vendor',
        'aequitas.label.search' => 'Search',
        'aequitas.label.page_size' => 'Rows per page',
        'aequitas.placeholder.item_no' => 'Filter by item number',
        'aequitas.placeholder.vendor' => 'All vendors',
        'aequitas.placeholder.search' => 'Search all columns',
        'aequitas.page_size.unlimited' => 'Unlimited',
        'aequitas.pager.prev' => 'Previous',
        'aequitas.pager.next' => 'Next',
        'aequitas.pager.status' => 'Page %1$d of %2$d · %3$d rows',
        'aequitas.col.item_no' => 'Item no.',
        'aequitas.col.description' => 'Item description',
        'aequitas.col.vendor_no' => 'Vendor no.',
        'aequitas.col.purchase_price' => 'Purchase price',
        'aequitas.col.last_direct_cost' => 'Last direct cost',
        'aequitas.col.delta' => 'Δ€',
        'aequitas.col.minimum_quantity' => 'Minimum qty',
        'aequitas.col.base_unit' => 'Base unit',
        'aequitas.col.unit' => 'Unit',
        'aequitas.col.starting_date' => 'Starting date',
        'aequitas.col.ending_date' => 'Ending date',
        'aequitas.col.settlement_price' => 'Fixed settlement price',
        'aequitas.col.price_list' => 'Price list',
        'aequitas.col.line_no' => 'Line',
        'aequitas.cached_at' => 'Last updated: %s',
        'aequitas.row_count' => '%d rows',
        'aequitas.empty.cache' => 'No nightly data yet. Run nightly.php to fetch AppItemCard and Prijslijstregels.',
        'aequitas.empty.rows' => 'No mismatched or duplicate price list lines found.',
        'aequitas.stale.cache' => 'The nightly update is stale. Run nightly.php again.',
        'aequitas.modal.title' => 'Duplicate lines detected',
        'aequitas.modal.close' => 'Close',
    ],

    'de' => [
        'lang.menu_aria' => 'Sprache wählen',
        'lang.switch_to' => 'Wechseln zu %s',
        'app.title' => 'Aequitas',
        'aequitas.hero.title' => 'Einkaufspreise',
        'aequitas.hero.subtitle' => 'Nur abweichende Einkaufspreise und doppelte Preislistenzeilen.',
        'aequitas.label.company' => 'Unternehmen',
        'aequitas.label.item_no' => 'Artikelnummer',
        'aequitas.label.vendor' => 'Kreditor',
        'aequitas.label.search' => 'Suchen',
        'aequitas.label.page_size' => 'Zeilen pro Seite',
        'aequitas.placeholder.item_no' => 'Nach Artikelnummer filtern',
        'aequitas.placeholder.vendor' => 'Alle Kreditoren',
        'aequitas.placeholder.search' => 'In allen Spalten suchen',
        'aequitas.page_size.unlimited' => 'Unbegrenzt',
        'aequitas.pager.prev' => 'Zurück',
        'aequitas.pager.next' => 'Weiter',
        'aequitas.pager.status' => 'Seite %1$d von %2$d · %3$d Zeilen',
        'aequitas.col.item_no' => 'Artikelnummer',
        'aequitas.col.description' => 'Artikelbeschreibung',
        'aequitas.col.vendor_no' => 'Kreditornr.',
        'aequitas.col.purchase_price' => 'Einkaufspreis',
        'aequitas.col.last_direct_cost' => 'Letzte direkte Kosten',
        'aequitas.col.delta' => 'Δ€',
        'aequitas.col.minimum_quantity' => 'Mindestmenge',
        'aequitas.col.base_unit' => 'Basiseinheit',
        'aequitas.col.unit' => 'Einheit',
        'aequitas.col.starting_date' => 'Startdatum',
        'aequitas.col.ending_date' => 'Enddatum',
        'aequitas.col.settlement_price' => 'Fester Verrechnungspreis',
        'aequitas.col.price_list' => 'Preisliste',
        'aequitas.col.line_no' => 'Zeile',
        'aequitas.cached_at' => 'Zuletzt aktualisiert: %s',
        'aequitas.row_count' => '%d Zeilen',
        'aequitas.empty.cache' => 'Noch keine Nachtdaten. Starten Sie nightly.php, um AppItemCard und Prijslijstregels abzurufen.',
        'aequitas.empty.rows' => 'Keine abweichenden oder doppelten Preislistenzeilen gefunden.',
        'aequitas.stale.cache' => 'Die Nachtaktualisierung ist veraltet. Starten Sie nightly.php erneut.',
        'aequitas.modal.title' => 'Doppelte Zeilen erkannt',
        'aequitas.modal.close' => 'Schließen',
    ],

    'fr' => [
        'lang.menu_aria' => 'Choisir la langue',
        'lang.switch_to' => 'Passer en %s',
        'app.title' => 'Aequitas',
        'aequitas.hero.title' => 'Prix d’achat',
        'aequitas.hero.subtitle' => 'Uniquement les prix d’achat divergents et les doublons de tarif.',
        'aequitas.label.company' => 'Société',
        'aequitas.label.item_no' => 'N° article',
        'aequitas.label.vendor' => 'Fournisseur',
        'aequitas.label.search' => 'Rechercher',
        'aequitas.label.page_size' => 'Lignes par page',
        'aequitas.placeholder.item_no' => 'Filtrer par n° article',
        'aequitas.placeholder.vendor' => 'Tous les fournisseurs',
        'aequitas.placeholder.search' => 'Rechercher dans toutes les colonnes',
        'aequitas.page_size.unlimited' => 'Illimité',
        'aequitas.pager.prev' => 'Précédent',
        'aequitas.pager.next' => 'Suivant',
        'aequitas.pager.status' => 'Page %1$d sur %2$d · %3$d lignes',
        'aequitas.col.item_no' => 'N° article',
        'aequitas.col.description' => 'Description article',
        'aequitas.col.vendor_no' => 'N° fournisseur',
        'aequitas.col.purchase_price' => 'Prix d’achat',
        'aequitas.col.last_direct_cost' => 'Dernier coût direct',
        'aequitas.col.delta' => 'Δ€',
        'aequitas.col.minimum_quantity' => 'Qté minimum',
        'aequitas.col.base_unit' => 'Unité de base',
        'aequitas.col.unit' => 'Unité',
        'aequitas.col.starting_date' => 'Date de début',
        'aequitas.col.ending_date' => 'Date de fin',
        'aequitas.col.settlement_price' => 'Prix de règlement fixe',
        'aequitas.col.price_list' => 'Tarif',
        'aequitas.col.line_no' => 'Ligne',
        'aequitas.cached_at' => 'Dernière mise à jour : %s',
        'aequitas.row_count' => '%d lignes',
        'aequitas.empty.cache' => 'Pas encore de données nocturnes. Lancez nightly.php pour récupérer AppItemCard et Prijslijstregels.',
        'aequitas.empty.rows' => 'Aucun tarif divergent ou en double trouvé.',
        'aequitas.stale.cache' => 'La mise à jour nocturne est obsolète. Relancez nightly.php.',
        'aequitas.modal.title' => 'Doublons détectés',
        'aequitas.modal.close' => 'Fermer',
    ],
];
/**
 * Functies
 */

function getUserPrefsPath(string $email): ?string
{
    $email = strtolower(trim($email));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }
    $dir = __DIR__ . '/data/user_prefs';
    $filename = preg_replace('/[^a-z0-9._\-]/', '_', $email) . '.json';
    return $dir . '/' . $filename;
}

function loadUserPrefs(string $email): array
{
    $path = getUserPrefsPath($email);
    if ($path === null || !is_file($path)) {
        return [];
    }
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function saveUserPref(string $email, string $key, mixed $value): void
{
    $path = getUserPrefsPath($email);
    if ($path === null) {
        return;
    }
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
    $prefs = loadUserPrefs($email);
    $prefs[$key] = $value;
    file_put_contents($path, json_encode($prefs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function getCurrentLanguage(): string
{
    $lang = (string) ($_SESSION['lang'] ?? 'nl');
    return array_key_exists($lang, SUPPORTED_LANGUAGES) ? $lang : 'nl';
}

function getHtmlLang(): string
{
    return getCurrentLanguage();
}

function getDateLocale(): string
{
    $lang = getCurrentLanguage();
    return LOCALE_BY_LANG[$lang] ?? 'nl-NL';
}

/**
 * Geeft de vertaling voor $key in de actieve taal.
 * Extra $args worden via sprintf ingevoegd (voor %d, %s, etc.).
 */
function LOC(string $key, mixed ...$args): string
{
    $lang = getCurrentLanguage();
    $translations = TRANSLATIONS[$lang] ?? TRANSLATIONS['nl'];
    $string = $translations[$key] ?? (TRANSLATIONS['nl'][$key] ?? $key);

    return $args !== [] ? sprintf($string, ...$args) : $string;
}

function localizationFlagSvg(string $lang): string
{
    $svg = FLAG_SVGS[$lang] ?? '';
    if ($svg === '') {
        return '';
    }

    $safeLang = preg_replace('/[^a-z0-9]/', '', $lang) ?? $lang;
    return str_replace(
        ['id="a"', 'url(#a)', 'id="b"', 'url(#b)'],
        ['id="flag-' . $safeLang . '-a"', 'url(#flag-' . $safeLang . '-a)', 'id="flag-' . $safeLang . '-b"', 'url(#flag-' . $safeLang . '-b)'],
        $svg
    );
}

function localizationUrlWithLang(string $lang): string
{
    $params = $_GET;
    unset($params['lang']);
    $params['lang'] = $lang;
    $path = strtok((string) ($_SERVER['REQUEST_URI'] ?? ''), '?') ?: '';
    $query = http_build_query($params);
    return $path . ($query !== '' ? '?' . $query : '');
}

function localizationJsTranslations(array $keys): string
{
    $payload = [];
    foreach ($keys as $key) {
        $payload[$key] = LOC($key);
    }

    return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function renderLanguageSwitcherStyles(): void
{
    echo <<<'CSS'
<style>
.lang-switcher {
    position: fixed;
    top: 12px;
    right: 12px;
    z-index: 5000;
    font-family: inherit;
}
.lang-switcher-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 30px;
    padding: 0;
    border: 1px solid rgba(0, 82, 155, 0.25);
    border-radius: 6px;
    background: #ffffff;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.12);
    cursor: pointer;
}
.lang-switcher-toggle:hover {
    background: #f2f9ff;
}
.lang-switcher-toggle svg {
    width: 28px;
    height: auto;
    display: block;
    border-radius: 2px;
    overflow: hidden;
}
.lang-switcher-menu {
    position: absolute;
    top: calc(100% + 6px);
    right: 0;
    min-width: 160px;
    margin: 0;
    padding: 6px;
    list-style: none;
    background: #ffffff;
    border: 1px solid #c9d7eb;
    border-radius: 10px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.18);
    display: none;
}
.lang-switcher.is-open .lang-switcher-menu {
    display: block;
}
.lang-switcher-item a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: 8px;
    color: var(--kvt-text, #1f2937);
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
}
.lang-switcher-item a:hover {
    background: #edf7ff;
}
.lang-switcher-item.is-active a {
    background: #e6f4ff;
}
.lang-switcher-item svg {
    width: 24px;
    height: auto;
    flex-shrink: 0;
    border-radius: 2px;
    overflow: hidden;
}
@media print {
    .lang-switcher {
        display: none !important;
    }
}
</style>
CSS;
}

function renderLanguageSwitcher(): void
{
    $current = getCurrentLanguage();
    $menuAria = htmlspecialchars(LOC('lang.menu_aria'), ENT_QUOTES);

    echo '<div class="lang-switcher" data-lang-switcher>';
    echo '<button type="button" class="lang-switcher-toggle" aria-haspopup="true" aria-expanded="false" aria-label="' . $menuAria . '">';
    echo localizationFlagSvg($current);
    echo '</button>';
    echo '<ul class="lang-switcher-menu" role="menu">';

    foreach (SUPPORTED_LANGUAGES as $code => $meta) {
        if ($code === $current) {
            continue;
        }

        $label = (string) ($meta['label'] ?? $code);
        $href = htmlspecialchars(localizationUrlWithLang($code), ENT_QUOTES);
        $title = htmlspecialchars(LOC('lang.switch_to', $label), ENT_QUOTES);

        echo '<li class="lang-switcher-item" role="none">';
        echo '<a role="menuitem" href="' . $href . '" title="' . $title . '">';
        echo localizationFlagSvg($code);
        echo '<span>' . htmlspecialchars($label) . '</span>';
        echo '</a>';
        echo '</li>';
    }

    echo '</ul>';
    echo '</div>';
}

function renderLanguageSwitcherScript(): void
{
    echo <<<'JS'
<script>
(function () {
    document.querySelectorAll('[data-lang-switcher]').forEach(function (root) {
        var toggle = root.querySelector('.lang-switcher-toggle');
        if (!toggle) {
            return;
        }

        toggle.addEventListener('click', function (event) {
            event.stopPropagation();
            var isOpen = root.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('click', function () {
            root.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        });

        root.addEventListener('click', function (event) {
            event.stopPropagation();
        });
    });
})();
</script>
JS;
}

/**
 * Page load
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

if (!isset($_SESSION['lang'])) {
    $prefEmail = strtolower(trim((string) ($_SESSION['user']['email'] ?? '')));
    if ($prefEmail !== '') {
        $savedPrefs = loadUserPrefs($prefEmail);
        if (isset($savedPrefs['lang']) && array_key_exists($savedPrefs['lang'], SUPPORTED_LANGUAGES)) {
            $_SESSION['lang'] = $savedPrefs['lang'];
        }
    }
}

if (!isset($_SESSION['lang']) || !array_key_exists((string) $_SESSION['lang'], SUPPORTED_LANGUAGES)) {
    $_SESSION['lang'] = 'nl';
}

if (isset($_GET['lang']) && array_key_exists($_GET['lang'], SUPPORTED_LANGUAGES)) {
    $requestedLang = (string) $_GET['lang'];
    $langChanged = $requestedLang !== getCurrentLanguage();
    $_SESSION['lang'] = $requestedLang;
    $prefEmail = strtolower(trim((string) ($_SESSION['user']['email'] ?? '')));
    if ($prefEmail !== '' && $langChanged) {
        saveUserPref($prefEmail, 'lang', $requestedLang);
    }

    $isApiAction = isset($_GET['action']) && trim((string) $_GET['action']) !== '';
    if (!$isApiAction && strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'GET') {
        $params = $_GET;
        unset($params['lang']);
        $path = strtok((string) ($_SERVER['REQUEST_URI'] ?? ''), '?') ?: '';
        $query = http_build_query($params);
        header('Location: ' . $path . ($query !== '' ? '?' . $query : ''));
        exit;
    }
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

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
        'app.title' => 'Seshat',
        'seshat.hero.title' => 'Timesheet productiviteit',
        'seshat.hero.subtitle' => 'Overzicht van goedgekeurde urenstaten per medewerker.',
        'seshat.label.company' => 'Bedrijf',
        'seshat.label.date_from' => 'Van',
        'seshat.label.date_to' => 'Tot',
        'seshat.btn.load' => 'Laden',
        'seshat.btn.excel' => 'Excel',
        'seshat.config.note' => 'Werksoorten instellen via',
        'seshat.section.cards' => 'Medewerkers',
        'seshat.section.person_summary' => 'Totaaloverzicht per medewerker',
        'seshat.section.week_blocks' => 'Per weeknummer',
        'seshat.legend.productive' => 'Productief',
        'seshat.legend.unproductive' => 'Improductief',
        'seshat.legend.leave' => 'Verlof',
        'seshat.card.last_week_orders' => 'Werkorders afgelopen week',
        'seshat.card.total_hours' => 'Totaal uren',
        'seshat.col.employee' => 'Medewerker',
        'seshat.col.cost_center' => 'Kostenplaats',
        'seshat.col.total_hours' => 'Totaal uren',
        'seshat.col.avg_productivity' => 'Gem. productiviteit per week',
        'seshat.col.productivity' => 'Productiviteit',
        'seshat.day.mon' => 'Ma',
        'seshat.day.tue' => 'Di',
        'seshat.day.wed' => 'Wo',
        'seshat.day.thu' => 'Do',
        'seshat.day.fri' => 'Vr',
        'seshat.day.sat' => 'Za',
        'seshat.day.sun' => 'Zo',
        'seshat.empty.cards' => 'Geen goedgekeurde timesheetregels gevonden in deze periode.',
        'seshat.error.load_failed' => 'Timesheets ophalen mislukt. Probeer het later opnieuw.',
        'seshat.loader.loading' => 'Timesheets ophalen uit Business Central',
        'seshat.modal.close' => 'Sluiten',
        'seshat.modal.hours_total' => 'uur totaal',
        'seshat.modal.hours_productive' => 'productief',
    ],

    'en' => [
        'lang.menu_aria' => 'Choose language',
        'lang.switch_to' => 'Switch to %s',
        'app.title' => 'Seshat',
        'seshat.hero.title' => 'Timesheet productivity',
        'seshat.hero.subtitle' => 'Overview of approved timesheets per employee.',
        'seshat.label.company' => 'Company',
        'seshat.label.date_from' => 'From',
        'seshat.label.date_to' => 'To',
        'seshat.btn.load' => 'Load',
        'seshat.btn.excel' => 'Excel',
        'seshat.config.note' => 'Configure work types via',
        'seshat.section.cards' => 'Employees',
        'seshat.section.person_summary' => 'Summary per employee',
        'seshat.section.week_blocks' => 'By week number',
        'seshat.legend.productive' => 'Productive',
        'seshat.legend.unproductive' => 'Unproductive',
        'seshat.legend.leave' => 'Leave',
        'seshat.card.last_week_orders' => 'Work orders last week',
        'seshat.card.total_hours' => 'Total hours',
        'seshat.col.employee' => 'Employee',
        'seshat.col.cost_center' => 'Cost center',
        'seshat.col.total_hours' => 'Total hours',
        'seshat.col.avg_productivity' => 'Avg. productivity per week',
        'seshat.col.productivity' => 'Productivity',
        'seshat.day.mon' => 'Mon',
        'seshat.day.tue' => 'Tue',
        'seshat.day.wed' => 'Wed',
        'seshat.day.thu' => 'Thu',
        'seshat.day.fri' => 'Fri',
        'seshat.day.sat' => 'Sat',
        'seshat.day.sun' => 'Sun',
        'seshat.empty.cards' => 'No approved timesheet lines found in this period.',
        'seshat.error.load_failed' => 'Failed to load timesheets. Please try again later.',
        'seshat.loader.loading' => 'Loading timesheets from Business Central',
        'seshat.modal.close' => 'Close',
        'seshat.modal.hours_total' => 'hours total',
        'seshat.modal.hours_productive' => 'productive',
    ],

    'de' => [
        'lang.menu_aria' => 'Sprache wählen',
        'lang.switch_to' => 'Wechseln zu %s',
        'app.title' => 'Seshat',
        'seshat.hero.title' => 'Timesheet-Produktivität',
        'seshat.hero.subtitle' => 'Übersicht genehmigter Zeiterfassungen pro Mitarbeiter.',
        'seshat.label.company' => 'Unternehmen',
        'seshat.label.date_from' => 'Von',
        'seshat.label.date_to' => 'Bis',
        'seshat.btn.load' => 'Laden',
        'seshat.btn.excel' => 'Excel',
        'seshat.config.note' => 'Arbeitsarten konfigurieren über',
        'seshat.section.cards' => 'Mitarbeiter',
        'seshat.section.person_summary' => 'Gesamtübersicht pro Mitarbeiter',
        'seshat.section.week_blocks' => 'Pro Kalenderwoche',
        'seshat.legend.productive' => 'Produktiv',
        'seshat.legend.unproductive' => 'Unproduktiv',
        'seshat.legend.leave' => 'Urlaub',
        'seshat.card.last_week_orders' => 'Arbeitsaufträge letzte Woche',
        'seshat.card.total_hours' => 'Gesamtstunden',
        'seshat.col.employee' => 'Mitarbeiter',
        'seshat.col.cost_center' => 'Kostenstelle',
        'seshat.col.total_hours' => 'Gesamtstunden',
        'seshat.col.avg_productivity' => 'Ø Produktivität pro Woche',
        'seshat.col.productivity' => 'Produktivität',
        'seshat.day.mon' => 'Mo',
        'seshat.day.tue' => 'Di',
        'seshat.day.wed' => 'Mi',
        'seshat.day.thu' => 'Do',
        'seshat.day.fri' => 'Fr',
        'seshat.day.sat' => 'Sa',
        'seshat.day.sun' => 'So',
        'seshat.empty.cards' => 'Keine genehmigten Timesheet-Zeilen in diesem Zeitraum gefunden.',
        'seshat.error.load_failed' => 'Timesheets konnten nicht geladen werden. Bitte später erneut versuchen.',
        'seshat.loader.loading' => 'Timesheets werden aus Business Central geladen',
        'seshat.modal.close' => 'Schließen',
        'seshat.modal.hours_total' => 'Stunden gesamt',
        'seshat.modal.hours_productive' => 'produktiv',
    ],

    'fr' => [
        'lang.menu_aria' => 'Choisir la langue',
        'lang.switch_to' => 'Passer en %s',
        'app.title' => 'Seshat',
        'seshat.hero.title' => 'Productivité des feuilles de temps',
        'seshat.hero.subtitle' => 'Aperçu des feuilles de temps approuvées par employé.',
        'seshat.label.company' => 'Société',
        'seshat.label.date_from' => 'Du',
        'seshat.label.date_to' => 'Au',
        'seshat.btn.load' => 'Charger',
        'seshat.btn.excel' => 'Excel',
        'seshat.config.note' => 'Configurer les types de travail via',
        'seshat.section.cards' => 'Employés',
        'seshat.section.person_summary' => 'Résumé par employé',
        'seshat.section.week_blocks' => 'Par numéro de semaine',
        'seshat.legend.productive' => 'Productif',
        'seshat.legend.unproductive' => 'Improductif',
        'seshat.legend.leave' => 'Congé',
        'seshat.card.last_week_orders' => 'Ordres de travail semaine dernière',
        'seshat.card.total_hours' => 'Heures totales',
        'seshat.col.employee' => 'Employé',
        'seshat.col.cost_center' => 'Centre de coûts',
        'seshat.col.total_hours' => 'Heures totales',
        'seshat.col.avg_productivity' => 'Productivité moy. par semaine',
        'seshat.col.productivity' => 'Productivité',
        'seshat.day.mon' => 'Lu',
        'seshat.day.tue' => 'Ma',
        'seshat.day.wed' => 'Me',
        'seshat.day.thu' => 'Je',
        'seshat.day.fri' => 'Ve',
        'seshat.day.sat' => 'Sa',
        'seshat.day.sun' => 'Di',
        'seshat.empty.cards' => 'Aucune ligne de feuille de temps approuvée trouvée pour cette période.',
        'seshat.error.load_failed' => 'Impossible de charger les feuilles de temps. Réessayez plus tard.',
        'seshat.loader.loading' => 'Chargement des feuilles de temps depuis Business Central',
        'seshat.modal.close' => 'Fermer',
        'seshat.modal.hours_total' => 'heures au total',
        'seshat.modal.hours_productive' => 'productif',
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

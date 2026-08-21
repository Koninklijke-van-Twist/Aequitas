<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Includes/requires
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/logincheck.php';
require_once __DIR__ . '/localization.php';
require_once __DIR__ . '/aequitas_data.php';

/**
 * Functies
 */

function aequitas_h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function aequitas_format_number(float $value): string
{
    if (abs($value - round($value)) < 0.00001) {
        return number_format($value, 0, ',', '.');
    }

    return number_format($value, 2, ',', '.');
}

function aequitas_format_money(float $value): string
{
    return '€ ' . number_format($value, 2, ',', '.');
}

function aequitas_format_date(string $value): string
{
    if ($value === '') {
        return '';
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    if ($date instanceof DateTimeImmutable) {
        return $date->format('d-m-Y');
    }

    return $value;
}

/**
 * Δ tussen laatste directe kosten en inkoopprijs (basis = inkoopprijs).
 *
 * @return array{dir:string,percent:float,label:string}
 */
function aequitas_price_delta(float $lastDirectCost, float $purchasePrice): array
{
    $ldcCents = (int) round($lastDirectCost * 100);
    $ppCents = (int) round($purchasePrice * 100);

    if ($ldcCents === $ppCents) {
        return ['dir' => 'eq', 'percent' => 0.0, 'label' => '~'];
    }

    if ($ppCents === 0) {
        $percent = 100.0;
    } else {
        $percent = abs(($lastDirectCost - $purchasePrice) / $purchasePrice) * 100;
    }

    $label = number_format($percent, 1, ',', '.') . '%';
    if ($ldcCents > $ppCents) {
        return ['dir' => 'up', 'percent' => $percent, 'label' => $label];
    }

    return ['dir' => 'down', 'percent' => $percent, 'label' => $label];
}

function aequitas_format_delta_html(float $lastDirectCost, float $purchasePrice): string
{
    $delta = aequitas_price_delta($lastDirectCost, $purchasePrice);
    if ($delta['dir'] === 'eq') {
        return '<span class="aequitas-delta aequitas-delta-eq" title="0%">~</span>';
    }

    if ($delta['dir'] === 'up') {
        return '<span class="aequitas-delta aequitas-delta-up" title="'
            . aequitas_h($delta['label']) . '">↑ ' . aequitas_h($delta['label']) . '</span>';
    }

    return '<span class="aequitas-delta aequitas-delta-down" title="'
        . aequitas_h($delta['label']) . '">↓ ' . aequitas_h($delta['label']) . '</span>';
}

function aequitas_row_search_text(array $row): string
{
    $parts = [
        $row['item_no'] ?? '',
        $row['description'] ?? '',
        $row['vendor_no'] ?? '',
        $row['vendor_name'] ?? '',
        aequitas_format_money((float) ($row['purchase_price'] ?? 0)),
        aequitas_format_money((float) ($row['last_direct_cost'] ?? 0)),
        aequitas_price_delta((float) ($row['last_direct_cost'] ?? 0), (float) ($row['purchase_price'] ?? 0))['label'],
        aequitas_format_number((float) ($row['minimum_quantity'] ?? 0)),
        $row['base_unit'] ?? '',
        $row['unit'] ?? '',
        aequitas_format_date((string) ($row['starting_date'] ?? '')),
        aequitas_format_date((string) ($row['ending_date'] ?? '')),
        aequitas_format_money((float) ($row['settlement_price'] ?? 0)),
    ];

    return strtolower(implode(' ', array_map('strval', $parts)));
}

/**
 * Page load
 */

$aequitasPageSizeOptions = [25, 50, 100, 150, 200, 300, 500, 1000, 0];
$aequitasDefaultPageSize = 100;

if (isset($_GET['action']) && trim((string) $_GET['action']) === 'save_page_size') {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $prefEmail = strtolower(trim((string) ($_SESSION['user']['email'] ?? '')));
    $requestedSize = (int) ($_GET['page_size'] ?? $aequitasDefaultPageSize);
    if (!in_array($requestedSize, $aequitasPageSizeOptions, true)) {
        $requestedSize = $aequitasDefaultPageSize;
    }
    if ($prefEmail !== '') {
        saveUserPref($prefEmail, 'page_size', $requestedSize);
    }
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['ok' => true, 'page_size' => $requestedSize], JSON_UNESCAPED_UNICODE);
    exit;
}

$companies = aequitas_cached_companies();
$prefEmail = strtolower(trim((string) ($_SESSION['user']['email'] ?? '')));
$userPrefs = $prefEmail !== '' ? loadUserPrefs($prefEmail) : [];
$savedCompany = trim((string) ($userPrefs['company'] ?? ''));
$savedPageSize = (int) ($userPrefs['page_size'] ?? $aequitasDefaultPageSize);
if (!in_array($savedPageSize, $aequitasPageSizeOptions, true)) {
    $savedPageSize = $aequitasDefaultPageSize;
}

$requestedCompany = trim((string) ($_GET['company'] ?? ''));
if ($requestedCompany !== '' && in_array($requestedCompany, $companies, true)) {
    $company = $requestedCompany;
    if ($prefEmail !== '' && $requestedCompany !== $savedCompany) {
        saveUserPref($prefEmail, 'company', $requestedCompany);
    }
} elseif ($savedCompany !== '' && in_array($savedCompany, $companies, true)) {
    $company = $savedCompany;
} else {
    $company = (string) ($companies[0] ?? '');
}

$cache = $company !== '' ? aequitas_read_company_cache($company) : null;
$cachedAt = (int) ($cache['_meta']['cached_at'] ?? 0);
$cacheStale = $cache !== null && $cachedAt > 0 && (time() - $cachedAt) > 129600;
$rows = [];
$vendors = [];

if (is_array($cache)) {
    $rows = aequitas_build_table_rows_from_cache($company);
    $vendors = aequitas_vendor_options($rows);
}

$cachedAtLabel = '';
if ($cachedAt > 0) {
    $cachedAtLabel = (new DateTimeImmutable('@' . $cachedAt))->setTimezone(new DateTimeZone(date_default_timezone_get()))->format('d-m-Y H:i');
}

?><!DOCTYPE html>
<html lang="<?= aequitas_h(getHtmlLang()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= aequitas_h(LOC('app.title')) ?></title>
    <link rel="stylesheet" href="brand.css">
    <link rel="manifest" href="site.webmanifest">
    <link rel="icon" href="doc.svg" type="image/svg+xml">
    <?php renderLanguageSwitcherStyles(); ?>
    <style>
        .aequitas-page { max-width: 1700px; margin: 0 auto; padding: 16px; }
        .aequitas-header { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; margin-bottom: 16px; }
        .aequitas-header img { max-height: 42px; width: auto; }
        .aequitas-header-actions { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-left: auto; }
        .aequitas-card { background: var(--kvt-panel-bg); border: 1px solid var(--kvt-line); border-radius: 12px; padding: 16px; margin-bottom: 16px; }
        .aequitas-card h1, .aequitas-card h2 { margin: 0 0 12px; color: var(--kvt-text); }
        .aequitas-subtitle, .aequitas-meta { color: var(--kvt-muted); margin: 6px 0 0; }
        .aequitas-meta { font-size: 0.92rem; }
        .aequitas-form { display: grid; gap: 12px; margin-top: 16px; }
        .aequitas-form-grid { display: grid; gap: 12px; }
        .aequitas-form label { display: grid; gap: 6px; font-weight: 700; color: var(--kvt-muted); }
        .aequitas-form input, .aequitas-form select { font: inherit; border-radius: 10px; border: 1px solid var(--kvt-line); padding: 12px 14px; width: 100%; box-sizing: border-box; }
        .aequitas-search { width: 100%; }
        .aequitas-alert { border: 1px solid #fecaca; background: #fef2f2; color: var(--kvt-danger); border-radius: 10px; padding: 12px 14px; margin-bottom: 16px; }
        .aequitas-alert-warn { border-color: #fdba74; background: #fff7ed; color: #9a3412; }
        .aequitas-muted { color: var(--kvt-muted); }
        .aequitas-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table.aequitas-table { width: 100%; border-collapse: collapse; font-size: 0.92rem; min-width: 980px; }
        table.aequitas-table th, table.aequitas-table td { border-bottom: 1px solid var(--kvt-line); padding: 10px 8px; text-align: left; vertical-align: top; background: #fff; }
        table.aequitas-table th { color: var(--kvt-muted); font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.03em; white-space: nowrap; position: sticky; top: 0; z-index: 2; }
        table.aequitas-table td.num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        table.aequitas-table th:first-child, table.aequitas-table td:first-child { position: sticky; left: 0; z-index: 1; min-width: 110px; }
        table.aequitas-table th:first-child { z-index: 3; }
        .aequitas-delta { font-weight: 700; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .aequitas-delta-up { color: #b91c1c; }
        .aequitas-delta-down { color: #15803d; }
        .aequitas-delta-eq { color: #94a3b8; font-size: 1.05rem; }
        .aequitas-pager { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; margin-top: 14px; }
        .aequitas-pager-controls { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .aequitas-pager button { font: inherit; border: 1px solid var(--kvt-line); background: #fff; border-radius: 10px; padding: 10px 14px; cursor: pointer; color: var(--kvt-text); }
        .aequitas-pager button:disabled { opacity: 0.45; cursor: not-allowed; }
        .aequitas-pager-status { color: var(--kvt-muted); font-size: 0.92rem; }
        .aequitas-row-mismatch td { background: #ffedd5; }
        .aequitas-row-conflict { cursor: pointer; }
        .aequitas-row-conflict td { background: #fecaca; animation: aequitas-blink 1.1s ease-in-out infinite; }
        .aequitas-row-hidden { display: none; }
        .aequitas-modal-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45); display: none; align-items: flex-end; justify-content: center; z-index: 13000; padding: 0; }
        .aequitas-modal-backdrop.is-open { display: flex; }
        .aequitas-modal { width: min(960px, 100%); max-height: 92vh; overflow: auto; background: #fff; border-radius: 16px 16px 0 0; padding: 16px; box-shadow: 0 20px 60px rgba(15, 23, 42, 0.25); }
        .aequitas-modal-header { display: flex; justify-content: space-between; gap: 12px; align-items: start; margin-bottom: 12px; position: sticky; top: 0; background: #fff; padding-bottom: 8px; border-bottom: 1px solid var(--kvt-line); }
        .aequitas-modal-close { border: 0; background: transparent; font-size: 1.4rem; line-height: 1; cursor: pointer; color: var(--kvt-muted); padding: 4px 8px; }
        .aequitas-modal-table { width: 100%; border-collapse: collapse; font-size: 0.86rem; }
        .aequitas-modal-table th, .aequitas-modal-table td { border-bottom: 1px solid var(--kvt-line); padding: 8px 6px; text-align: left; }
        .aequitas-modal-table td.num { text-align: right; font-variant-numeric: tabular-nums; }
        @keyframes aequitas-blink {
            0%, 100% { background: #fecaca; }
            50% { background: #ef4444; color: #fff; }
        }
        @media (min-width: 720px) {
            .aequitas-form-grid { grid-template-columns: 1fr 1fr; }
            .aequitas-modal-backdrop { align-items: center; padding: 24px; }
            .aequitas-modal { border-radius: 16px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .aequitas-row-conflict td { animation: none; background: #ef4444; color: #fff; }
        }
    </style>
</head>
<body>
<div class="aequitas-page">
    <header class="aequitas-header">
        <img src="logo-website.png" alt="KVT">
        <div class="aequitas-header-actions">
            <?php if ($companies !== []): ?>
                <form method="get" action="index.php">
                    <label class="aequitas-muted" style="display:grid;gap:6px;font-weight:700;">
                        <?= aequitas_h(LOC('aequitas.label.company')) ?>
                        <select name="company" onchange="this.form.submit()">
                            <?php foreach ($companies as $companyOption): ?>
                                <option value="<?= aequitas_h($companyOption) ?>"<?= $companyOption === $company ? ' selected' : '' ?>><?= aequitas_h($companyOption) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </form>
            <?php endif; ?>
            <?php renderLanguageSwitcher(); ?>
        </div>
    </header>

    <section class="aequitas-card">
        <h1 class="brand-display"><?= aequitas_h(LOC('aequitas.hero.title')) ?></h1>
        <p class="aequitas-subtitle"><?= aequitas_h(LOC('aequitas.hero.subtitle')) ?></p>
        <?php if ($cachedAtLabel !== ''): ?>
            <p class="aequitas-meta"><?= aequitas_h(LOC('aequitas.cached_at', $cachedAtLabel)) ?> · <span id="aequitas-row-count"><?= aequitas_h(LOC('aequitas.row_count', count($rows))) ?></span></p>
        <?php endif; ?>

        <div class="aequitas-form">
            <div class="aequitas-form-grid">
                <label>
                    <?= aequitas_h(LOC('aequitas.label.item_no')) ?>
                    <input type="search" id="aequitas-filter-item" placeholder="<?= aequitas_h(LOC('aequitas.placeholder.item_no')) ?>" autocomplete="off">
                </label>
                <label>
                    <?= aequitas_h(LOC('aequitas.label.vendor')) ?>
                    <select id="aequitas-filter-vendor">
                        <option value=""><?= aequitas_h(LOC('aequitas.placeholder.vendor')) ?></option>
                        <?php foreach ($vendors as $vendorNo => $vendorLabel): ?>
                            <option value="<?= aequitas_h((string) $vendorNo) ?>"><?= aequitas_h($vendorLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="aequitas-form-grid">
                <label>
                    <?= aequitas_h(LOC('aequitas.label.search')) ?>
                    <input class="aequitas-search" type="search" id="aequitas-filter-search" placeholder="<?= aequitas_h(LOC('aequitas.placeholder.search')) ?>" autocomplete="off">
                </label>
                <label>
                    <?= aequitas_h(LOC('aequitas.label.page_size')) ?>
                    <select id="aequitas-page-size">
                        <?php foreach ($aequitasPageSizeOptions as $sizeOption): ?>
                            <option value="<?= (int) $sizeOption ?>"<?= (int) $sizeOption === $savedPageSize ? ' selected' : '' ?>>
                                <?= $sizeOption === 0 ? aequitas_h(LOC('aequitas.page_size.unlimited')) : (int) $sizeOption ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
        </div>
    </section>

    <?php if ($cache === null): ?>
        <div class="aequitas-alert"><?= aequitas_h(LOC('aequitas.empty.cache')) ?></div>
    <?php elseif ($cacheStale): ?>
        <div class="aequitas-alert aequitas-alert-warn"><?= aequitas_h(LOC('aequitas.stale.cache')) ?></div>
    <?php endif; ?>

    <?php if ($cache !== null && $rows === []): ?>
        <section class="aequitas-card">
            <p class="aequitas-muted"><?= aequitas_h(LOC('aequitas.empty.rows')) ?></p>
        </section>
    <?php endif; ?>

    <?php if ($rows !== []): ?>
        <section class="aequitas-card">
            <div class="aequitas-table-wrap">
                <table class="aequitas-table" id="aequitas-table">
                    <thead>
                        <tr>
                            <th><?= aequitas_h(LOC('aequitas.col.item_no')) ?></th>
                            <th><?= aequitas_h(LOC('aequitas.col.description')) ?></th>
                            <th><?= aequitas_h(LOC('aequitas.col.vendor_no')) ?></th>
                            <th><?= aequitas_h(LOC('aequitas.col.purchase_price')) ?></th>
                            <th><?= aequitas_h(LOC('aequitas.col.last_direct_cost')) ?></th>
                            <th><?= aequitas_h(LOC('aequitas.col.delta')) ?></th>
                            <th><?= aequitas_h(LOC('aequitas.col.minimum_quantity')) ?></th>
                            <th><?= aequitas_h(LOC('aequitas.col.base_unit')) ?></th>
                            <th><?= aequitas_h(LOC('aequitas.col.unit')) ?></th>
                            <th><?= aequitas_h(LOC('aequitas.col.starting_date')) ?></th>
                            <th><?= aequitas_h(LOC('aequitas.col.ending_date')) ?></th>
                            <th><?= aequitas_h(LOC('aequitas.col.settlement_price')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $classes = [];
                            if (!empty($row['conflict'])) {
                                $classes[] = 'aequitas-row-conflict';
                            } elseif (!empty($row['price_mismatch'])) {
                                $classes[] = 'aequitas-row-mismatch';
                            }
                            $conflictJson = '';
                            if (!empty($row['conflict'])) {
                                $encoded = json_encode($row['conflicts'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
                                $conflictJson = is_string($encoded) ? $encoded : '[]';
                            }
                            ?>
                            <tr
                                class="<?= aequitas_h(implode(' ', $classes)) ?>"
                                data-item="<?= aequitas_h((string) $row['item_no']) ?>"
                                data-vendor="<?= aequitas_h((string) $row['vendor_no']) ?>"
                                data-search="<?= aequitas_h(aequitas_row_search_text($row)) ?>"
                                <?php if ($conflictJson !== ''): ?>
                                    data-conflicts="<?= aequitas_h($conflictJson) ?>"
                                    tabindex="0"
                                <?php endif; ?>
                            >
                                <td><?= aequitas_h((string) $row['item_no']) ?></td>
                                <td><?= aequitas_h((string) $row['description']) ?></td>
                                <td><?= aequitas_h((string) $row['vendor_no']) ?></td>
                                <td class="num"><?= aequitas_h(aequitas_format_money((float) $row['purchase_price'])) ?></td>
                                <td class="num"><?= aequitas_h(aequitas_format_money((float) $row['last_direct_cost'])) ?></td>
                                <td class="num"><?= aequitas_format_delta_html((float) $row['last_direct_cost'], (float) $row['purchase_price']) ?></td>
                                <td class="num"><?= aequitas_h(aequitas_format_number((float) $row['minimum_quantity'])) ?></td>
                                <td><?= aequitas_h((string) $row['base_unit']) ?></td>
                                <td><?= aequitas_h((string) $row['unit']) ?></td>
                                <td><?= aequitas_h(aequitas_format_date((string) $row['starting_date'])) ?></td>
                                <td><?= aequitas_h(aequitas_format_date((string) $row['ending_date'])) ?></td>
                                <td class="num"><?= aequitas_h(aequitas_format_money((float) $row['settlement_price'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="aequitas-pager" id="aequitas-pager" hidden>
                <div class="aequitas-pager-status" id="aequitas-pager-status"></div>
                <div class="aequitas-pager-controls">
                    <button type="button" id="aequitas-page-prev"><?= aequitas_h(LOC('aequitas.pager.prev')) ?></button>
                    <button type="button" id="aequitas-page-next"><?= aequitas_h(LOC('aequitas.pager.next')) ?></button>
                </div>
            </div>
        </section>
    <?php endif; ?>
</div>

<div id="aequitas-modal-backdrop" class="aequitas-modal-backdrop" aria-hidden="true">
    <div class="aequitas-modal" role="dialog" aria-modal="true" aria-labelledby="aequitas-modal-title">
        <div class="aequitas-modal-header">
            <h2 id="aequitas-modal-title"><?= aequitas_h(LOC('aequitas.modal.title')) ?></h2>
            <button type="button" class="aequitas-modal-close" id="aequitas-modal-close" aria-label="<?= aequitas_h(LOC('aequitas.modal.close')) ?>">&times;</button>
        </div>
        <div class="aequitas-table-wrap" id="aequitas-modal-body"></div>
    </div>
</div>

<?php renderLanguageSwitcherScript(); ?>
<script>
(function () {
    var itemFilter = document.getElementById('aequitas-filter-item');
    var vendorFilter = document.getElementById('aequitas-filter-vendor');
    var searchFilter = document.getElementById('aequitas-filter-search');
    var pageSizeSelect = document.getElementById('aequitas-page-size');
    var pagePrev = document.getElementById('aequitas-page-prev');
    var pageNext = document.getElementById('aequitas-page-next');
    var pager = document.getElementById('aequitas-pager');
    var pagerStatus = document.getElementById('aequitas-pager-status');
    var table = document.getElementById('aequitas-table');
    var rowCount = document.getElementById('aequitas-row-count');
    var backdrop = document.getElementById('aequitas-modal-backdrop');
    var modalBody = document.getElementById('aequitas-modal-body');
    var modalClose = document.getElementById('aequitas-modal-close');
    var pageSize = <?= (int) $savedPageSize ?>;
    var currentPage = 1;
    var labels = <?= json_encode([
        'row_count' => LOC('aequitas.row_count'),
        'page_status' => LOC('aequitas.pager.status'),
        'price_list' => LOC('aequitas.col.price_list'),
        'line_no' => LOC('aequitas.col.line_no'),
        'vendor_no' => LOC('aequitas.col.vendor_no'),
        'description' => LOC('aequitas.col.description'),
        'minimum_quantity' => LOC('aequitas.col.minimum_quantity'),
        'unit' => LOC('aequitas.col.unit'),
        'purchase_price' => LOC('aequitas.col.purchase_price'),
        'starting_date' => LOC('aequitas.col.starting_date'),
        'ending_date' => LOC('aequitas.col.ending_date'),
    ], JSON_UNESCAPED_UNICODE) ?>;

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatNumber(value) {
        var num = Number(value || 0);
        return new Intl.NumberFormat('nl-NL', {
            minimumFractionDigits: Math.abs(num - Math.round(num)) < 0.00001 ? 0 : 2,
            maximumFractionDigits: 2
        }).format(num);
    }

    function formatMoney(value) {
        var num = Number(value || 0);
        return new Intl.NumberFormat('nl-NL', {
            style: 'currency',
            currency: 'EUR'
        }).format(num);
    }

    function formatDate(value) {
        var text = String(value || '');
        var match = text.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (!match) {
            return text;
        }
        return match[3] + '-' + match[2] + '-' + match[1];
    }

    function getAllRows() {
        if (!table || !table.tBodies[0]) {
            return [];
        }
        return Array.prototype.slice.call(table.tBodies[0].rows);
    }

    function rowMatchesFilters(row) {
        var itemValue = (itemFilter && itemFilter.value ? itemFilter.value : '').trim().toLowerCase();
        var vendorValue = (vendorFilter && vendorFilter.value ? vendorFilter.value : '').trim().toLowerCase();
        var searchValue = (searchFilter && searchFilter.value ? searchFilter.value : '').trim().toLowerCase();
        var itemNo = (row.getAttribute('data-item') || '').toLowerCase();
        var vendorNo = (row.getAttribute('data-vendor') || '').toLowerCase();
        var searchText = (row.getAttribute('data-search') || '').toLowerCase();

        if (itemValue !== '' && itemNo.indexOf(itemValue) === -1) {
            return false;
        }
        if (vendorValue !== '' && vendorNo !== vendorValue) {
            return false;
        }
        if (searchValue !== '' && searchText.indexOf(searchValue) === -1) {
            return false;
        }
        return true;
    }

    function applyFilters(resetPage) {
        if (!table) {
            return;
        }
        if (resetPage) {
            currentPage = 1;
        }

        var rows = getAllRows();
        var matching = rows.filter(rowMatchesFilters);
        var total = matching.length;
        var size = pageSize > 0 ? pageSize : total;
        var pageCount = size > 0 ? Math.max(1, Math.ceil(total / size)) : 1;
        if (currentPage > pageCount) {
            currentPage = pageCount;
        }
        if (currentPage < 1) {
            currentPage = 1;
        }

        var start = pageSize > 0 ? (currentPage - 1) * pageSize : 0;
        var end = pageSize > 0 ? start + pageSize : total;

        rows.forEach(function (row) {
            row.classList.add('aequitas-row-hidden');
        });
        matching.forEach(function (row, index) {
            if (index >= start && index < end) {
                row.classList.remove('aequitas-row-hidden');
            }
        });

        if (rowCount && labels.row_count) {
            rowCount.textContent = String(labels.row_count).replace('%d', String(total));
        }

        if (pager) {
            pager.hidden = total === 0;
        }
        if (pagerStatus && labels.page_status) {
            pagerStatus.textContent = String(labels.page_status)
                .replace('%1$d', String(currentPage))
                .replace('%2$d', String(pageCount))
                .replace('%3$d', String(total));
        }
        if (pagePrev) {
            pagePrev.disabled = currentPage <= 1 || pageSize === 0;
        }
        if (pageNext) {
            pageNext.disabled = currentPage >= pageCount || pageSize === 0 || total === 0;
        }
    }

    function savePageSize(size) {
        var url = 'index.php?action=save_page_size&page_size=' + encodeURIComponent(String(size));
        if (window.fetch) {
            fetch(url, { credentials: 'same-origin', cache: 'no-store' }).catch(function () {});
        }
    }

    function closeModal() {
        if (!backdrop) {
            return;
        }
        backdrop.classList.remove('is-open');
        backdrop.setAttribute('aria-hidden', 'true');
    }

    function openConflicts(raw) {
        var lines = [];
        try {
            lines = JSON.parse(raw || '[]');
        } catch (error) {
            lines = [];
        }

        if (!Array.isArray(lines) || !modalBody || !backdrop) {
            return;
        }

        var html = '<table class="aequitas-modal-table"><thead><tr>';
        html += '<th>' + escapeHtml(labels.price_list) + '</th>';
        html += '<th>' + escapeHtml(labels.line_no) + '</th>';
        html += '<th>' + escapeHtml(labels.vendor_no) + '</th>';
        html += '<th>' + escapeHtml(labels.description) + '</th>';
        html += '<th>' + escapeHtml(labels.minimum_quantity) + '</th>';
        html += '<th>' + escapeHtml(labels.unit) + '</th>';
        html += '<th>' + escapeHtml(labels.purchase_price) + '</th>';
        html += '<th>' + escapeHtml(labels.starting_date) + '</th>';
        html += '<th>' + escapeHtml(labels.ending_date) + '</th>';
        html += '</tr></thead><tbody>';

        lines.forEach(function (line) {
            html += '<tr>';
            html += '<td>' + escapeHtml(line.Price_List_Code || '') + '</td>';
            html += '<td class="num">' + escapeHtml(line.Line_No || '') + '</td>';
            html += '<td>' + escapeHtml(line.Source_No || '') + '</td>';
            html += '<td>' + escapeHtml(line.Description || line.PriceListDescription || '') + '</td>';
            html += '<td class="num">' + escapeHtml(formatNumber(line.Minimum_Quantity)) + '</td>';
            html += '<td>' + escapeHtml(line.Unit_of_Measure_Code || '') + '</td>';
            html += '<td class="num">' + escapeHtml(formatMoney(line.DirectUnitCost)) + '</td>';
            html += '<td>' + escapeHtml(formatDate(line.Starting_Date)) + '</td>';
            html += '<td>' + escapeHtml(formatDate(line.Ending_Date)) + '</td>';
            html += '</tr>';
        });

        html += '</tbody></table>';
        modalBody.innerHTML = html;
        backdrop.classList.add('is-open');
        backdrop.setAttribute('aria-hidden', 'false');
    }

    [itemFilter, vendorFilter, searchFilter].forEach(function (input) {
        if (!input) {
            return;
        }
        input.addEventListener('input', function () { applyFilters(true); });
        input.addEventListener('change', function () { applyFilters(true); });
    });

    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', function () {
            pageSize = parseInt(pageSizeSelect.value, 10);
            if (isNaN(pageSize)) {
                pageSize = 100;
            }
            savePageSize(pageSize);
            applyFilters(true);
        });
    }

    if (pagePrev) {
        pagePrev.addEventListener('click', function () {
            currentPage -= 1;
            applyFilters(false);
        });
    }
    if (pageNext) {
        pageNext.addEventListener('click', function () {
            currentPage += 1;
            applyFilters(false);
        });
    }

    if (table) {
        table.addEventListener('click', function (event) {
            var row = event.target.closest('tr.aequitas-row-conflict');
            if (!row) {
                return;
            }
            openConflicts(row.getAttribute('data-conflicts'));
        });

        table.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }
            var row = event.target.closest('tr.aequitas-row-conflict');
            if (!row) {
                return;
            }
            event.preventDefault();
            openConflicts(row.getAttribute('data-conflicts'));
        });
    }

    if (modalClose) {
        modalClose.addEventListener('click', closeModal);
    }
    if (backdrop) {
        backdrop.addEventListener('click', function (event) {
            if (event.target === backdrop) {
                closeModal();
            }
        });
    }
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });

    applyFilters(true);
})();
</script>
</body>
</html>

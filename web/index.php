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
require_once __DIR__ . '/odata.php';
require_once __DIR__ . '/auth_helper.php';
require_once __DIR__ . '/timesheet_data.php';

/**
 * Functies
 */

function seshat_h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function seshat_url(array $params = []): string
{
    $query = $_GET;
    foreach ($params as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
            continue;
        }
        $query[$key] = $value;
    }
    unset($query['lang'], $query['_loaded'], $query['export']);

    $path = strtok((string) ($_SERVER['REQUEST_URI'] ?? 'index.php'), '?') ?: 'index.php';
    $query['lang'] = getCurrentLanguage();

    return $path . '?' . http_build_query($query);
}

function seshat_parse_date_param(string $value): string
{
    $value = trim($value);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
        return '';
    }

    $parts = explode('-', $value);
    if (!checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
        return '';
    }

    return $value;
}

/**
 * Page load
 */

$companies = bc_companies_for_page();
$prefEmail = strtolower(trim((string) ($_SESSION['user']['email'] ?? '')));
$savedCompany = '';
if ($prefEmail !== '') {
    $savedCompany = trim((string) (loadUserPrefs($prefEmail)['company'] ?? ''));
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

$dateFrom = seshat_parse_date_param((string) ($_GET['date_from'] ?? ''));
$dateTo = seshat_parse_date_param((string) ($_GET['date_to'] ?? ''));
if ($dateFrom === '') {
    $dateFrom = seshat_default_date_from();
}
if ($dateTo === '') {
    $dateTo = seshat_default_date_to();
}
if ($dateFrom > $dateTo) {
    [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
}

auth_set_current_company_context($company);

$errorKey = '';
$dashboard = [
    'cards' => [],
    'person_summary' => [],
    'weekly_summary' => [],
    'week_groups' => [],
    'productive_types' => seshat_productive_work_types(),
    'leave_types' => seshat_leave_work_types(),
    'ignored_types' => seshat_ignored_work_types(),
    'last_week' => seshat_last_week_range(),
];

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    try {
        $hiddenCategories = seshat_parse_hidden_categories((string) ($_GET['hide'] ?? ''));
        $exportData = seshat_filter_dashboard(
            seshat_build_dashboard($company, $dateFrom, $dateTo),
            $hiddenCategories
        );
        $filename = 'seshat_' . seshat_company_slug($company) . '_' . $dateFrom . '_' . $dateTo . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo seshat_build_excel_xml($exportData['person_summary'], $exportData['week_groups']);
        exit;
    } catch (Throwable $exportError) {
        $errorKey = 'seshat.error.load_failed';
    }
}

try {
    $dashboard = seshat_build_dashboard($company, $dateFrom, $dateTo);
} catch (Throwable $loadError) {
    $errorKey = 'seshat.error.load_failed';
}

$dashboardJson = json_encode($dashboard, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
if ($dashboardJson === false) {
    $dashboardJson = '{}';
}

$legendLabelsJson = json_encode([
    'productive' => LOC('seshat.legend.productive'),
    'unproductive' => LOC('seshat.legend.unproductive'),
    'leave' => LOC('seshat.legend.leave'),
], JSON_UNESCAPED_UNICODE);
if ($legendLabelsJson === false) {
    $legendLabelsJson = '{}';
}

$uiLabelsJson = json_encode([
    'last_week_orders' => LOC('seshat.card.last_week_orders'),
    'total_hours' => LOC('seshat.card.total_hours'),
    'employee' => LOC('seshat.col.employee'),
    'cost_center' => LOC('seshat.col.cost_center'),
    'avg_productivity' => LOC('seshat.col.avg_productivity'),
    'productivity' => LOC('seshat.col.productivity'),
    'hours_total' => LOC('seshat.modal.hours_total'),
    'hours_productive' => LOC('seshat.modal.hours_productive'),
    'section_person_summary' => LOC('seshat.section.person_summary'),
    'section_week_blocks' => LOC('seshat.section.week_blocks'),
], JSON_UNESCAPED_UNICODE);
if ($uiLabelsJson === false) {
    $uiLabelsJson = '{}';
}

?><!DOCTYPE html>
<html lang="<?= seshat_h(getHtmlLang()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= seshat_h(LOC('app.title')) ?></title>
    <link rel="stylesheet" href="brand.css">
    <link rel="manifest" href="site.webmanifest">
    <link rel="icon" href="doc.svg" type="image/svg+xml">
    <?php renderLanguageSwitcherStyles(); ?>
    <style>
        .seshat-page { max-width: 1700px; margin: 0 auto; padding: 16px; }
        .seshat-header { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; margin-bottom: 20px; }
        .seshat-header img { max-height: 42px; width: auto; }
        .seshat-header-actions { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-left: auto; }
        .seshat-card { background: var(--kvt-panel-bg); border: 1px solid var(--kvt-line); border-radius: 12px; padding: 16px; margin-bottom: 16px; }
        .seshat-card h1, .seshat-card h2, .seshat-card h3 { margin: 0 0 12px; color: var(--kvt-text); }
        .seshat-subtitle { color: var(--kvt-muted); margin: 6px 0 0; }
        .seshat-form { display: grid; gap: 12px; }
        .seshat-form-grid, .seshat-form-dates { display: grid; gap: 12px; }
        .seshat-form label { display: grid; gap: 6px; font-weight: 700; color: var(--kvt-muted); }
        .seshat-form input, .seshat-form select, .seshat-btn { font: inherit; border-radius: 10px; border: 1px solid var(--kvt-line); padding: 12px 14px; }
        .seshat-form input, .seshat-form select { width: 100%; box-sizing: border-box; }
        .seshat-btn { background: var(--kvt-main-blue); color: #fff; border-color: var(--kvt-main-blue); cursor: pointer; text-decoration: none; display: inline-block; text-align: center; }
        .seshat-btn-secondary { background: #fff; color: var(--kvt-main-blue); }
        .seshat-toolbar { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .seshat-alert { border: 1px solid #fecaca; background: #fef2f2; color: var(--kvt-danger); border-radius: 10px; padding: 12px 14px; margin-bottom: 16px; }
        .seshat-muted { color: var(--kvt-muted); font-size: 0.92rem; }
        .seshat-config-note { font-size: 0.88rem; color: var(--kvt-muted); margin-top: 8px; }
        .seshat-config-note code { font-size: 0.85em; }
        .seshat-cards { display: grid; gap: 14px; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); }
        .seshat-person-card { border: 1px solid var(--kvt-line); border-radius: 12px; padding: 14px; background: #fff; cursor: pointer; transition: box-shadow 0.15s ease, transform 0.15s ease; }
        .seshat-person-card:hover { box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08); transform: translateY(-1px); }
        .seshat-person-name { font-family: var(--kvt-font-display); font-size: 1.05rem; margin: 0 0 12px; }
        .seshat-pie { width: 96px; height: 96px; border-radius: 50%; margin: 0 auto 12px; position: relative; }
        .seshat-pie-center { position: absolute; inset: 22px; background: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.82rem; font-weight: 700; color: var(--kvt-text); }
        .seshat-stat { display: flex; justify-content: space-between; gap: 8px; font-size: 0.92rem; margin-top: 6px; }
        .seshat-stat-label { color: var(--kvt-muted); }
        .seshat-stat-value { font-weight: 700; font-variant-numeric: tabular-nums; }
        .seshat-legend { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; font-size: 0.82rem; color: var(--kvt-muted); margin-bottom: 12px; }
        .seshat-legend-toggle { border: 1px solid var(--kvt-line); background: #fff; border-radius: 999px; padding: 8px 12px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font: inherit; font-size: 0.82rem; color: var(--kvt-text); }
        .seshat-legend-toggle.is-hidden { opacity: 0.45; text-decoration: line-through; }
        .seshat-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; flex: 0 0 auto; }
        .seshat-dot-productive { background: var(--kvt-main-blue); }
        .seshat-dot-unproductive { background: var(--kvt-danger); }
        .seshat-dot-leave { background: #15803d; }
        .seshat-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table.seshat-table { width: 100%; border-collapse: collapse; font-size: 0.92rem; min-width: 520px; }
        table.seshat-table th, table.seshat-table td { border-bottom: 1px solid var(--kvt-line); padding: 10px 8px; text-align: left; vertical-align: top; }
        table.seshat-table th { color: var(--kvt-muted); font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.03em; cursor: pointer; user-select: none; white-space: nowrap; }
        table.seshat-table th.is-sorted-asc::after { content: ' ▲'; font-size: 0.72rem; }
        table.seshat-table th.is-sorted-desc::after { content: ' ▼'; font-size: 0.72rem; }
        table.seshat-table td.num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .seshat-week-blocks { display: grid; gap: 14px; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); }
        .seshat-week-block { border: 1px solid var(--kvt-line); border-radius: 12px; padding: 12px; background: #fff; }
        .seshat-week-block h3 { font-size: 1rem; margin-bottom: 8px; }
        .seshat-modal-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45); display: none; align-items: flex-end; justify-content: center; z-index: 13000; padding: 0; }
        .seshat-modal-backdrop.is-open { display: flex; }
        .seshat-modal { width: min(960px, 100%); max-height: 92vh; overflow: auto; background: #fff; border-radius: 16px 16px 0 0; padding: 16px; box-shadow: 0 20px 60px rgba(15, 23, 42, 0.25); }
        .seshat-modal-header { display: flex; justify-content: space-between; gap: 12px; align-items: start; margin-bottom: 12px; position: sticky; top: 0; background: #fff; padding-bottom: 8px; border-bottom: 1px solid var(--kvt-line); }
        .seshat-modal-close { border: 0; background: transparent; font-size: 1.4rem; line-height: 1; cursor: pointer; color: var(--kvt-muted); padding: 4px 8px; }
        .seshat-accordion { display: grid; gap: 10px; }
        .seshat-accordion-item { border: 1px solid var(--kvt-line); border-radius: 10px; overflow: hidden; }
        .seshat-accordion-trigger { width: 100%; text-align: left; border: 0; background: #f8fafc; padding: 12px 14px; font: inherit; font-weight: 700; cursor: pointer; display: flex; justify-content: space-between; gap: 12px; }
        .seshat-accordion-panel { display: none; padding: 0 8px 8px; }
        .seshat-accordion-item.is-open .seshat-accordion-panel { display: block; }
        .seshat-loader { position: fixed; inset: 0; z-index: 12000; display: flex; align-items: center; justify-content: center; padding: 24px; background: rgba(255, 255, 255, 0.92); opacity: 0; visibility: hidden; pointer-events: none; transition: opacity 0.2s ease, visibility 0.2s ease; }
        .seshat-loader.is-visible { opacity: 1; visibility: visible; pointer-events: auto; }
        .seshat-loader-panel { display: grid; gap: 12px; justify-items: center; max-width: 280px; text-align: center; color: var(--kvt-text); }
        .seshat-loader-spinner { width: 42px; height: 42px; border: 3px solid rgba(0, 153, 204, 0.2); border-top-color: var(--kvt-main-blue); border-radius: 50%; animation: seshat-spin 0.8s linear infinite; }
        @keyframes seshat-spin { to { transform: rotate(360deg); } }
        @media (min-width: 640px) {
            .seshat-form-grid { grid-template-columns: 1fr 2fr auto auto; align-items: end; }
            .seshat-form-dates { grid-template-columns: 1fr 1fr; }
            .seshat-modal-backdrop { align-items: center; padding: 24px; }
            .seshat-modal { border-radius: 16px; }
        }
    </style>
</head>
<body>
<div class="seshat-page">
    <header class="seshat-header">
        <img src="logo-website.png" alt="KVT">
        <div class="seshat-header-actions">
            <?php renderLanguageSwitcher(); ?>
        </div>
    </header>

    <section class="seshat-card">
        <h1 class="brand-display"><?= seshat_h(LOC('seshat.hero.title')) ?></h1>
        <p class="seshat-subtitle"><?= seshat_h(LOC('seshat.hero.subtitle')) ?></p>

        <form class="seshat-form seshat-nav" method="get" action="index.php" style="margin-top: 16px;">
            <input type="hidden" name="lang" value="<?= seshat_h(getCurrentLanguage()) ?>">
            <div class="seshat-form-grid">
                <label>
                    <?= seshat_h(LOC('seshat.label.company')) ?>
                    <select name="company">
                        <?php foreach ($companies as $companyOption): ?>
                            <option value="<?= seshat_h($companyOption) ?>"<?= $companyOption === $company ? ' selected' : '' ?>><?= seshat_h($companyOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="seshat-form-dates">
                    <label>
                        <?= seshat_h(LOC('seshat.label.date_from')) ?>
                        <input type="date" name="date_from" value="<?= seshat_h($dateFrom) ?>">
                    </label>
                    <label>
                        <?= seshat_h(LOC('seshat.label.date_to')) ?>
                        <input type="date" name="date_to" value="<?= seshat_h($dateTo) ?>">
                    </label>
                </div>
                <button class="seshat-btn" type="submit"><?= seshat_h(LOC('seshat.btn.load')) ?></button>
                <button class="seshat-btn seshat-btn-secondary" type="button" id="seshat-export-excel"><?= seshat_h(LOC('seshat.btn.excel')) ?></button>
            </div>
        </form>
    </section>

    <?php if ($errorKey !== ''): ?>
        <div class="seshat-alert"><?= seshat_h(LOC($errorKey)) ?></div>
    <?php endif; ?>

    <?php if ($dashboard['cards'] === [] && $errorKey === ''): ?>
        <section class="seshat-card">
            <p class="seshat-muted"><?= seshat_h(LOC('seshat.empty.cards')) ?></p>
        </section>
    <?php endif; ?>

    <?php if ($dashboard['cards'] !== []): ?>
        <section class="seshat-card" id="seshat-cards-section">
            <h2><?= seshat_h(LOC('seshat.section.cards')) ?></h2>
            <div class="seshat-legend" id="seshat-category-legend"></div>
            <div class="seshat-cards" id="seshat-cards"></div>
        </section>

        <section class="seshat-card" id="seshat-person-summary-section">
            <h2><?= seshat_h(LOC('seshat.section.person_summary')) ?></h2>
            <div class="seshat-table-wrap" id="seshat-person-summary"></div>
        </section>

        <section class="seshat-card" id="seshat-week-blocks-section">
            <h2><?= seshat_h(LOC('seshat.section.week_blocks')) ?></h2>
            <div class="seshat-week-blocks" id="seshat-week-blocks"></div>
        </section>
    <?php endif; ?>

    <?= injectTimerHtml([
        'endpoint' => 'odata.php',
        'position' => 'bottom-right',
    ]) ?>
</div>

<div id="seshat-modal-backdrop" class="seshat-modal-backdrop" aria-hidden="true">
    <div class="seshat-modal" role="dialog" aria-modal="true" aria-labelledby="seshat-modal-title">
        <div class="seshat-modal-header">
            <div>
                <h2 id="seshat-modal-title"></h2>
                <p class="seshat-muted" id="seshat-modal-subtitle"></p>
            </div>
            <button type="button" class="seshat-modal-close" id="seshat-modal-close" aria-label="<?= seshat_h(LOC('seshat.modal.close')) ?>">&times;</button>
        </div>
        <div class="seshat-accordion" id="seshat-modal-accordion"></div>
    </div>
</div>

<div id="seshat-loader" class="seshat-loader" aria-hidden="true" aria-busy="false">
    <div class="seshat-loader-panel">
        <div class="seshat-loader-spinner" aria-hidden="true"></div>
        <p><?= seshat_h(LOC('seshat.loader.loading')) ?></p>
    </div>
</div>

<script>
(function () {
    var dashboardData = <?= $dashboardJson ?>;
    var legendLabels = <?= $legendLabelsJson ?>;
    var uiLabels = <?= $uiLabelsJson ?>;
    var dayLabels = <?= json_encode([
        LOC('seshat.day.mon'),
        LOC('seshat.day.tue'),
        LOC('seshat.day.wed'),
        LOC('seshat.day.thu'),
        LOC('seshat.day.fri'),
        LOC('seshat.day.sat'),
        LOC('seshat.day.sun'),
    ], JSON_UNESCAPED_UNICODE) ?>;

    var categories = ['productive', 'unproductive', 'leave'];
    var hiddenCategories = new Set();
    var filteredDashboard = dashboardData;
    var openModalKey = null;

    var legendRoot = document.getElementById('seshat-category-legend');
    var cardsRoot = document.getElementById('seshat-cards');
    var personSummaryRoot = document.getElementById('seshat-person-summary');
    var weekBlocksRoot = document.getElementById('seshat-week-blocks');
    var exportButton = document.getElementById('seshat-export-excel');
    var modalBackdrop = document.getElementById('seshat-modal-backdrop');
    var modalTitle = document.getElementById('seshat-modal-title');
    var modalSubtitle = document.getElementById('seshat-modal-subtitle');
    var modalAccordion = document.getElementById('seshat-modal-accordion');
    var modalClose = document.getElementById('seshat-modal-close');

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatHours(value) {
        var num = Number(value || 0);
        if (Math.abs(num - Math.round(num)) < 0.00001) {
            return String(Math.round(num));
        }
        return num.toFixed(2).replace('.', ',');
    }

    function formatPercent(value) {
        var num = Number(value || 0);
        if (Math.abs(num - Math.round(num)) < 0.00001) {
            return String(Math.round(num));
        }
        return num.toFixed(1).replace('.', ',');
    }

    function calcProductivity(productive, unproductive) {
        var workTotal = Number(productive || 0) + Number(unproductive || 0);
        if (workTotal <= 0) {
            return 0;
        }
        return Math.round((Number(productive || 0) / workTotal) * 1000) / 10;
    }

    function categoryHours(hoursByCategory) {
        var productive = hiddenCategories.has('productive') ? 0 : Number(hoursByCategory.productive || 0);
        var unproductive = hiddenCategories.has('unproductive') ? 0 : Number(hoursByCategory.unproductive || 0);
        var leave = hiddenCategories.has('leave') ? 0 : Number(hoursByCategory.leave || 0);
        return {
            productive: productive,
            unproductive: unproductive,
            leave: leave,
            total: productive + unproductive + leave,
            productivity: calcProductivity(productive, unproductive)
        };
    }

    function filterModalWorkOrders(workOrders) {
        var filtered = [];
        (workOrders || []).forEach(function (workOrder) {
            var visibleLines = [];
            var hoursByCategory = { productive: 0, unproductive: 0, leave: 0 };
            (workOrder.lines || []).forEach(function (line) {
                var classification = line.classification || 'unproductive';
                if (hiddenCategories.has(classification)) {
                    return;
                }
                visibleLines.push(line);
                hoursByCategory[classification] = (hoursByCategory[classification] || 0) + Number(line.total_hours || 0);
            });
            if (visibleLines.length === 0) {
                return;
            }
            var summary = categoryHours(hoursByCategory);
            filtered.push({
                work_order_no: workOrder.work_order_no || '',
                total_hours: Math.round(summary.total * 100) / 100,
                productivity: summary.productivity,
                lines: visibleLines
            });
        });
        filtered.sort(function (a, b) {
            return String(a.work_order_no).localeCompare(String(b.work_order_no));
        });
        return filtered;
    }

    function countLastWeekOrders(card) {
        var lastWeek = dashboardData.last_week || {};
        var refs = {};
        filterModalWorkOrders(card.modal_work_orders || []).forEach(function (workOrder) {
            (workOrder.lines || []).forEach(function (line) {
                var weekStart = line.week_start || '';
                if (weekStart >= (lastWeek.start || '') && weekStart <= (lastWeek.end || '')) {
                    refs[line.work_order_no || workOrder.work_order_no || ''] = true;
                }
            });
        });
        return Object.keys(refs).filter(function (key) { return key !== ''; }).length;
    }

    function filterDashboard(data) {
        var cards = [];
        (data.cards || []).forEach(function (card) {
            var summary = categoryHours({
                productive: card.productive_hours,
                unproductive: card.unproductive_hours,
                leave: card.leave_hours
            });
            if (summary.total <= 0) {
                return;
            }
            cards.push(Object.assign({}, card, {
                productive_hours: Math.round(summary.productive * 100) / 100,
                unproductive_hours: Math.round(summary.unproductive * 100) / 100,
                leave_hours: Math.round(summary.leave * 100) / 100,
                total_hours: Math.round(summary.total * 100) / 100,
                productivity: summary.productivity,
                modal_work_orders: filterModalWorkOrders(card.modal_work_orders || []),
                last_week_count: countLastWeekOrders(card)
            }));
        });
        cards.sort(function (a, b) {
            var productivityCompare = Number(b.productivity || 0) - Number(a.productivity || 0);
            if (productivityCompare !== 0) {
                return productivityCompare;
            }
            return String(a.name || '').localeCompare(String(b.name || ''), undefined, { sensitivity: 'base' });
        });

        var weeklySummary = [];
        (data.weekly_summary || []).forEach(function (row) {
            var rowSummary = categoryHours({
                productive: row.productive_hours,
                unproductive: row.unproductive_hours,
                leave: row.leave_hours
            });
            if (rowSummary.total <= 0) {
                return;
            }
            weeklySummary.push(Object.assign({}, row, {
                total_hours: Math.round(rowSummary.total * 100) / 100,
                productivity: rowSummary.productivity
            }));
        });
        weeklySummary.sort(function (a, b) {
            if (Number(b.week_year) !== Number(a.week_year)) {
                return Number(b.week_year) - Number(a.week_year);
            }
            if (Number(b.week_number) !== Number(a.week_number)) {
                return Number(b.week_number) - Number(a.week_number);
            }
            return String(a.name || '').localeCompare(String(b.name || ''), undefined, { sensitivity: 'base' });
        });

        var productivityByPerson = {};
        weeklySummary.forEach(function (row) {
            if (!productivityByPerson[row.name]) {
                productivityByPerson[row.name] = [];
            }
            productivityByPerson[row.name].push(Number(row.productivity || 0));
        });

        var personSummary = cards.map(function (card) {
            var weeklyValues = productivityByPerson[card.name] || [];
            var avgProductivity = weeklyValues.length
                ? Math.round((weeklyValues.reduce(function (sum, value) { return sum + value; }, 0) / weeklyValues.length) * 10) / 10
                : Number(card.productivity || 0);
            return {
                name: card.name,
                cost_center: card.cost_center,
                total_hours: card.total_hours,
                avg_productivity: avgProductivity
            };
        });

        var weekGroups = {};
        weeklySummary.forEach(function (row) {
            if (!weekGroups[row.week_label]) {
                weekGroups[row.week_label] = [];
            }
            weekGroups[row.week_label].push(row);
        });

        return {
            cards: cards,
            person_summary: personSummary,
            weekly_summary: weeklySummary,
            week_groups: weekGroups
        };
    }

    function buildPieStyle(productive, unproductive, leave) {
        var total = Number(productive || 0) + Number(unproductive || 0) + Number(leave || 0);
        if (total <= 0) {
            return 'background: #e5e7eb;';
        }
        var productivePct = (Number(productive || 0) / total) * 100;
        var unproductivePct = productivePct + ((Number(unproductive || 0) / total) * 100);
        return 'background: conic-gradient(var(--kvt-main-blue) 0 ' + productivePct + '%, var(--kvt-danger) ' + productivePct + '% ' + unproductivePct + '%, #15803d ' + unproductivePct + '% 100%);';
    }

    function formatWorkOrderSummary(workOrder) {
        return formatHours(workOrder.total_hours) + ' uur, ' + formatPercent(workOrder.productivity) + '% ' + uiLabels.hours_productive;
    }

    function bindSortableTables(root) {
        root.querySelectorAll('table[data-sortable="1"]').forEach(function (table) {
            var headers = table.querySelectorAll('thead th[data-sort]');
            headers.forEach(function (header, columnIndex) {
                header.addEventListener('click', function () {
                    var tbody = table.querySelector('tbody');
                    if (!tbody) {
                        return;
                    }
                    var current = header.classList.contains('is-sorted-asc') ? 'asc'
                        : header.classList.contains('is-sorted-desc') ? 'desc'
                        : '';
                    var direction = current === 'asc' ? 'desc' : 'asc';
                    var sortType = header.getAttribute('data-sort') || 'string';
                    headers.forEach(function (otherHeader) {
                        otherHeader.classList.remove('is-sorted-asc', 'is-sorted-desc');
                    });
                    header.classList.add(direction === 'asc' ? 'is-sorted-asc' : 'is-sorted-desc');
                    var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
                    rows.sort(function (a, b) {
                        var aCell = a.children[columnIndex];
                        var bCell = b.children[columnIndex];
                        var aValue = aCell ? (aCell.getAttribute('data-value') || aCell.textContent || '') : '';
                        var bValue = bCell ? (bCell.getAttribute('data-value') || bCell.textContent || '') : '';
                        if (sortType === 'number') {
                            return direction === 'asc' ? Number(aValue) - Number(bValue) : Number(bValue) - Number(aValue);
                        }
                        aValue = String(aValue).toLowerCase();
                        bValue = String(bValue).toLowerCase();
                        if (aValue < bValue) {
                            return direction === 'asc' ? -1 : 1;
                        }
                        if (aValue > bValue) {
                            return direction === 'asc' ? 1 : -1;
                        }
                        return 0;
                    });
                    rows.forEach(function (row) {
                        tbody.appendChild(row);
                    });
                });
            });
        });
    }

    function renderLegend() {
        if (!legendRoot) {
            return;
        }
        legendRoot.innerHTML = categories.map(function (category) {
            var hiddenClass = hiddenCategories.has(category) ? ' is-hidden' : '';
            return '<button type="button" class="seshat-legend-toggle' + hiddenClass + '" data-category="' + category + '">'
                + '<i class="seshat-dot seshat-dot-' + category + '"></i>'
                + escapeHtml(legendLabels[category] || category)
                + '</button>';
        }).join('');
        legendRoot.querySelectorAll('.seshat-legend-toggle').forEach(function (button) {
            button.addEventListener('click', function () {
                var category = button.getAttribute('data-category');
                if (!category) {
                    return;
                }
                if (hiddenCategories.has(category)) {
                    hiddenCategories.delete(category);
                    button.classList.remove('is-hidden');
                } else {
                    hiddenCategories.add(category);
                    button.classList.add('is-hidden');
                }
                filteredDashboard = filterDashboard(dashboardData);
                renderDashboard();
                if (openModalKey !== null) {
                    openModal(openModalKey);
                }
            });
        });
    }

    function renderCards() {
        if (!cardsRoot) {
            return;
        }
        cardsRoot.innerHTML = (filteredDashboard.cards || []).map(function (card, index) {
            return ''
                + '<article class="seshat-person-card" data-person-key="' + escapeHtml(card.key || String(index)) + '" tabindex="0" role="button" aria-label="' + escapeHtml(card.name || '') + '">'
                + '<h3 class="seshat-person-name">' + escapeHtml(card.name || '') + '</h3>'
                + '<div class="seshat-pie" style="' + buildPieStyle(card.productive_hours, card.unproductive_hours, card.leave_hours) + '">'
                + '<div class="seshat-pie-center">' + escapeHtml(formatPercent(card.productivity) + '%') + '</div>'
                + '</div>'
                + '<div class="seshat-stat"><span class="seshat-stat-label">' + escapeHtml(uiLabels.last_week_orders) + '</span><span class="seshat-stat-value">' + escapeHtml(String(card.last_week_count || 0)) + '</span></div>'
                + '<div class="seshat-stat"><span class="seshat-stat-label">' + escapeHtml(uiLabels.total_hours) + '</span><span class="seshat-stat-value">' + escapeHtml(formatHours(card.total_hours)) + '</span></div>'
                + '</article>';
        }).join('');
        cardsRoot.querySelectorAll('.seshat-person-card').forEach(function (cardEl) {
            cardEl.addEventListener('click', function () {
                openModal(cardEl.getAttribute('data-person-key') || '');
            });
            cardEl.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openModal(cardEl.getAttribute('data-person-key') || '');
                }
            });
        });
    }

    function renderPersonSummary() {
        if (!personSummaryRoot) {
            return;
        }
        var rows = filteredDashboard.person_summary || [];
        personSummaryRoot.innerHTML = ''
            + '<table class="seshat-table" data-sortable="1"><thead><tr>'
            + '<th data-sort="string">' + escapeHtml(uiLabels.employee) + '</th>'
            + '<th class="num" data-sort="number">' + escapeHtml(uiLabels.total_hours) + '</th>'
            + '<th class="num" data-sort="number">' + escapeHtml(uiLabels.avg_productivity) + '</th>'
            + '</tr></thead><tbody>'
            + rows.map(function (row) {
                return '<tr>'
                    + '<td>' + escapeHtml(row.name || '') + '</td>'
                    + '<td class="num" data-value="' + escapeHtml(String(row.total_hours || 0)) + '">' + escapeHtml(formatHours(row.total_hours)) + '</td>'
                    + '<td class="num" data-value="' + escapeHtml(String(row.avg_productivity || 0)) + '">' + escapeHtml(formatPercent(row.avg_productivity) + '%') + '</td>'
                    + '</tr>';
            }).join('')
            + '</tbody></table>';
        bindSortableTables(personSummaryRoot);
    }

    function renderWeekBlocks() {
        if (!weekBlocksRoot) {
            return;
        }
        var weekGroups = filteredDashboard.week_groups || {};
        weekBlocksRoot.innerHTML = Object.keys(weekGroups).map(function (weekLabel) {
            var rows = weekGroups[weekLabel] || [];
            return ''
                + '<div class="seshat-week-block">'
                + '<h3>' + escapeHtml(weekLabel) + '</h3>'
                + '<div class="seshat-table-wrap">'
                + '<table class="seshat-table" data-sortable="1"><thead><tr>'
                + '<th data-sort="string">' + escapeHtml(uiLabels.cost_center) + '</th>'
                + '<th data-sort="string">' + escapeHtml(uiLabels.employee) + '</th>'
                + '<th class="num" data-sort="number">' + escapeHtml(uiLabels.total_hours) + '</th>'
                + '<th class="num" data-sort="number">' + escapeHtml(uiLabels.productivity) + '</th>'
                + '</tr></thead><tbody>'
                + rows.map(function (row) {
                    return '<tr>'
                        + '<td>' + escapeHtml(row.cost_center || '') + '</td>'
                        + '<td>' + escapeHtml(row.name || '') + '</td>'
                        + '<td class="num" data-value="' + escapeHtml(String(row.total_hours || 0)) + '">' + escapeHtml(formatHours(row.total_hours)) + '</td>'
                        + '<td class="num" data-value="' + escapeHtml(String(row.productivity || 0)) + '">' + escapeHtml(formatPercent(row.productivity) + '%') + '</td>'
                        + '</tr>';
                }).join('')
                + '</tbody></table></div></div>';
        }).join('');
        bindSortableTables(weekBlocksRoot);
    }

    function renderDashboard() {
        renderCards();
        renderPersonSummary();
        renderWeekBlocks();
    }

    function openModal(personKey) {
        var card = (filteredDashboard.cards || []).find(function (item) {
            return String(item.key || '') === String(personKey || '');
        });
        if (!card) {
            return;
        }
        openModalKey = personKey;
        modalTitle.textContent = card.name || '';
        modalSubtitle.textContent = (card.cost_center ? card.cost_center + ' · ' : '') + formatHours(card.total_hours) + ' ' + uiLabels.hours_total;
        modalAccordion.innerHTML = '';
        (card.modal_work_orders || []).forEach(function (workOrder, woIndex) {
            var item = document.createElement('div');
            item.className = 'seshat-accordion-item' + (woIndex === 0 ? ' is-open' : '');
            var trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = 'seshat-accordion-trigger';
            trigger.innerHTML = '<span>' + escapeHtml(workOrder.work_order_no || '') + '</span><span>' + escapeHtml(formatWorkOrderSummary(workOrder)) + '</span>';
            var panel = document.createElement('div');
            panel.className = 'seshat-accordion-panel';
            var tableWrap = document.createElement('div');
            tableWrap.className = 'seshat-table-wrap';
            var table = document.createElement('table');
            table.className = 'seshat-table';
            table.innerHTML = '<thead><tr><th>Werkorder</th><th>Werksoort</th>'
                + dayLabels.map(function (label) { return '<th class="num">' + label + '</th>'; }).join('')
                + '<th class="num">Totaal</th></tr></thead>';
            var tbody = document.createElement('tbody');
            (workOrder.lines || []).forEach(function (line) {
                var tr = document.createElement('tr');
                var cells = [line.work_order_no || '', line.work_type_code || ''];
                for (var day = 1; day <= 7; day++) {
                    cells.push(formatHours((line.hours && line.hours[day]) || 0));
                }
                cells.push(formatHours(line.total_hours || 0));
                tr.innerHTML = cells.map(function (value, cellIndex) {
                    var cls = cellIndex >= 2 ? ' class="num"' : '';
                    return '<td' + cls + '>' + escapeHtml(value) + '</td>';
                }).join('');
                tbody.appendChild(tr);
            });
            table.appendChild(tbody);
            tableWrap.appendChild(table);
            panel.appendChild(tableWrap);
            trigger.addEventListener('click', function () {
                item.classList.toggle('is-open');
            });
            item.appendChild(trigger);
            item.appendChild(panel);
            modalAccordion.appendChild(item);
        });
        modalBackdrop.classList.add('is-open');
        modalBackdrop.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        openModalKey = null;
        modalBackdrop.classList.remove('is-open');
        modalBackdrop.setAttribute('aria-hidden', 'true');
    }

    if (cardsRoot) {
        renderLegend();
        filteredDashboard = filterDashboard(dashboardData);
        renderDashboard();
    }

    modalClose.addEventListener('click', closeModal);
    modalBackdrop.addEventListener('click', function (event) {
        if (event.target === modalBackdrop) {
            closeModal();
        }
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });

    var DELAY_MS = 500;
    var loader = document.getElementById('seshat-loader');
    var timer = null;

    function showLoader() {
        if (!loader) {
            return;
        }
        loader.classList.add('is-visible');
        loader.setAttribute('aria-hidden', 'false');
        loader.setAttribute('aria-busy', 'true');
    }

    function clearLoaderTimer() {
        if (timer !== null) {
            window.clearTimeout(timer);
            timer = null;
        }
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.classList.contains('seshat-nav')) {
            return;
        }
        clearLoaderTimer();
        timer = window.setTimeout(showLoader, DELAY_MS);
    }, true);

    if (exportButton) {
        exportButton.addEventListener('click', function () {
            clearLoaderTimer();
            timer = window.setTimeout(showLoader, DELAY_MS);

            var params = new URLSearchParams(window.location.search);
            params.set('export', 'excel');
            if (hiddenCategories.size > 0) {
                params.set('hide', Array.from(hiddenCategories).join(','));
            } else {
                params.delete('hide');
            }
            window.location.href = 'index.php?' + params.toString();
        });
    }
})();
</script>
</body>
</html>

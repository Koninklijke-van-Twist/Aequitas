<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);
ignore_user_abort(true);
ini_set('memory_limit', '512M');

/**
 * Includes/requires
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/logincheck.php';
require_once __DIR__ . '/aequitas_data.php';

/**
 * Functies
 */

function aequitas_hourly_companies(string $requestedCompany): array
{
    $requestedCompany = trim($requestedCompany);
    if ($requestedCompany !== '') {
        return [$requestedCompany];
    }

    return AEQUITAS_COMPANIES;
}

function aequitas_hourly_send_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Page load
 */

$startedAt = time();
$requestedCompany = trim((string) ($_GET['company'] ?? ''));
$limit = (int) ($_GET['limit'] ?? AEQUITAS_HOURLY_ITEM_LIMIT);
if ($limit < 1) {
    $limit = AEQUITAS_HOURLY_ITEM_LIMIT;
}

$companies = aequitas_hourly_companies($requestedCompany);
$needsNightly = false;
foreach ($companies as $company) {
    $companyName = trim((string) $company);
    if ($companyName !== '' && !aequitas_company_has_nightly_cache($companyName)) {
        $needsNightly = true;
        break;
    }
}

$results = [];
$ok = true;
$ranAs = $needsNightly ? 'nightly' : 'hourly';

foreach ($companies as $company) {
    $companyName = trim((string) $company);
    if ($companyName === '') {
        continue;
    }

    try {
        if ($needsNightly) {
            $meta = aequitas_refresh_company($companyName);
            $results[] = [
                'ok' => true,
                'ran_as' => 'nightly',
                'company' => $companyName,
                'cached_at' => (int) ($meta['cached_at'] ?? time()),
                'items_mode' => (string) ($meta['items_mode'] ?? 'skipped'),
                'price_line_read' => (int) ($meta['price_line_read'] ?? 0),
                'price_line_count' => (int) ($meta['price_line_count'] ?? 0),
                'unique_items' => (int) ($meta['unique_items'] ?? 0),
                'item_count' => (int) ($meta['item_count'] ?? 0),
                'price_line_pages' => (int) ($meta['price_line_pages'] ?? 0),
            ];
            continue;
        }

        $meta = aequitas_hourly_refresh_company($companyName, $limit);
        $results[] = [
            'ok' => true,
            'ran_as' => 'hourly',
            'company' => $companyName,
            'cached_at' => (int) ($meta['cached_at'] ?? time()),
            'items_mode' => (string) ($meta['items_mode'] ?? ''),
            'items_watermark' => (string) ($meta['items_watermark'] ?? ''),
            'items_backfill_offset' => (int) ($meta['items_backfill_offset'] ?? 0),
            'items_backfill_done' => !empty($meta['items_backfill_done']),
            'hourly_batch' => (int) ($meta['hourly_batch'] ?? 0),
            'hourly_gaps' => (int) ($meta['hourly_gaps'] ?? 0),
            'hourly_catchup' => (int) ($meta['hourly_catchup'] ?? 0),
            'hourly_incomplete' => !empty($meta['hourly_incomplete']),
            'items_checked_count' => (int) ($meta['items_checked_count'] ?? 0),
            'unique_items' => (int) ($meta['unique_items'] ?? 0),
            'item_read' => (int) ($meta['item_read'] ?? 0),
            'item_count' => (int) ($meta['item_count'] ?? 0),
            'item_pages' => (int) ($meta['item_pages'] ?? 0),
        ];
    } catch (Throwable $error) {
        $ok = false;
        $results[] = [
            'ok' => false,
            'ran_as' => $ranAs,
            'company' => $companyName,
            'error' => $error->getMessage(),
        ];
    }
}

aequitas_hourly_send_json([
    'ok' => $ok && $results !== [],
    'ran_as' => $ranAs,
    'ran_at' => $startedAt,
    'duration_seconds' => time() - $startedAt,
    'limit' => $limit,
    'companies' => $results,
], $ok && $results !== [] ? 200 : 500);

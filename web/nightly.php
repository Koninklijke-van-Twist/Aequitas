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
require_once __DIR__ . '/bc_data.php';
require_once __DIR__ . '/aequitas_data.php';

/**
 * Functies
 */

function aequitas_nightly_companies(string $requestedCompany): array
{
    $known = bc_companies_for_page(1);
    $requestedCompany = trim($requestedCompany);
    if ($requestedCompany === '') {
        return $known;
    }

    foreach ($known as $company) {
        if (strcasecmp((string) $company, $requestedCompany) === 0) {
            return [(string) $company];
        }
    }

    return [$requestedCompany];
}

function aequitas_nightly_send_json(array $payload, int $status = 200): never
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

$requestedCompany = trim((string) ($_GET['company'] ?? ''));
$companies = aequitas_nightly_companies($requestedCompany);
$startedAt = time();
$results = [];
$ok = true;

foreach ($companies as $company) {
    $companyName = trim((string) $company);
    if ($companyName === '') {
        continue;
    }

    try {
        auth_set_current_company_context($companyName, 1);
        $meta = aequitas_refresh_company($companyName);
        $results[] = [
            'ok' => true,
            'company' => $companyName,
            'cached_at' => (int) ($meta['cached_at'] ?? time()),
            'item_count' => (int) ($meta['item_count'] ?? 0),
            'price_line_count' => (int) ($meta['price_line_count'] ?? 0),
            'item_pages' => (int) ($meta['item_pages'] ?? 0),
            'price_line_pages' => (int) ($meta['price_line_pages'] ?? 0),
        ];
    } catch (Throwable $error) {
        $ok = false;
        $results[] = [
            'ok' => false,
            'company' => $companyName,
            'error' => $error->getMessage(),
        ];
    }
}

aequitas_nightly_send_json([
    'ok' => $ok && $results !== [],
    'ran_at' => $startedAt,
    'duration_seconds' => time() - $startedAt,
    'companies' => $results,
], $ok && $results !== [] ? 200 : 500);

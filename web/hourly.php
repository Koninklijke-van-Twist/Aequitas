<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);
ignore_user_abort(true);

/**
 * Includes/requires
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/logincheck.php';

/**
 * Functies
 */

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
 *
 * Geen Business Central-call. Artikelen en prijslijst komen uit de nightly-cache.
 */

$startedAt = time();

aequitas_hourly_send_json([
    'ok' => true,
    'ran_as' => 'hourly',
    'ran_at' => $startedAt,
    'duration_seconds' => time() - $startedAt,
    'message' => 'Geen Business Central-call. Artikelen en prijslijst komen uit de nightly-cache.',
]);

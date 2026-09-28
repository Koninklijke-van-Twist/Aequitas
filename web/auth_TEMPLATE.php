<?php
/**
 * Auth-template voor Aequitas. Kopieer naar web/auth.php (niet in git).
 *
 * Mímir heeft voorrang. De BC-gegevens blijven verplicht naast $mimirApi:
 * faalt Mímir, dan valt de app (live web, nightly.php en CLI/cron) terug op dit directe pad.
 *
 *   $mimirApi  = 'mimir_…';
 *   $mimirBase = 'https://sleutels.kvt.nl/mimir/api'; // optioneel
 *
 * Zonder $mimirApi wordt alleen het BC-blok gebruikt.
 */

// --- Mímir (aanbevolen) ---
// $mimirApi  = 'mimir_…';
// $mimirBase = 'https://sleutels.kvt.nl/mimir/api';

// --- Business Central (direct pad, én fallback als Mímir faalt) ---
$auth_list = [
    'Production' => ['mode' => 'basic', 'user' => 'USERNAME', 'pass' => 'PASSWORD'],
];
$environment = 'Production';
$auth = $auth_list[$environment];
$baseUrl = 'https://my-bc-domain.com:7148/';

$allowedUsers = [
    'user@domain.nl',
];

<?php

/**
 * OData-routing: Mímir als $mimirApi gezet is, BC als de key ontbreekt.
 * Run: php web/tests/test_mimir_odata_routing.php
 */

/**
 * Includes/requires
 */
require_once dirname(__DIR__) . '/aequitas_data.php';

/**
 * Variabelen
 */
$failures = 0;
$mockPort = 18941;
$mockLog = sys_get_temp_dir() . '/aequitas-mimir-mock.log';
$mockScript = sys_get_temp_dir() . '/aequitas-mimir-mock.php';
$authPath = dirname(__DIR__) . '/auth.php';
$authBackup = null;
$cacheDir = dirname(__DIR__) . '/cache/odata';
$cacheBefore = glob($cacheDir . '/*.json') ?: [];

/**
 * Functies
 */
function test_assert(string $name, bool $condition, string $detail = ''): void
{
    global $failures;
    if ($condition) {
        echo "OK  {$name}\n";
        return;
    }

    $failures++;
    echo "FAIL {$name}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
}

function test_reset_discovery_cache(): void
{
    unset(
        $GLOBALS['demeter_company_environment_map'],
        $GLOBALS['demeter_companies_by_environment'],
        $GLOBALS['demeter_active_environments']
    );
}

function test_write_mock(): void
{
    global $mockScript, $mockLog;
    $log = var_export($mockLog, true);
    $php = <<<'PHP'
<?php
$log = LOG_PATH;
$uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
$method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
$authorization = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
file_put_contents($log, json_encode([
    'uri' => $uri,
    'method' => $method,
    'ua' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
    'authorization' => $authorization,
    'api_key' => (string) ($_SERVER['HTTP_X_API_KEY'] ?? ''),
], JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
header('Content-Type: application/json');

if (str_contains($uri, '/mimir-dup/api/companies.php')) {
    echo json_encode(['value' => [
        ['name' => 'Overlap BV', 'environment' => 'Production'],
        ['name' => 'Overlap BV', 'environment' => 'Sandbox'],
    ]]);
    exit;
}

if (str_contains($uri, '/mimir/api/companies.php')) {
    echo json_encode(['value' => [
        ['name' => 'Koninklijke van Twist', 'environment' => 'Production'],
        ['name' => "Van Twist's", 'environment' => 'Production'],
        ['name' => 'Hunter van Twist', 'environment' => 'Sandbox'],
    ]]);
    exit;
}

if (str_contains($uri, '/mimir/api/query.php')) {
    $body = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($body)) {
        $body = [];
    }
    echo json_encode(['value' => [[
        'No' => 'ART1',
        'company' => (string) ($body['company'] ?? ''),
        'table' => (string) ($body['table'] ?? ''),
        'select' => $body['select'] ?? [],
        'filter' => (string) ($body['filter'] ?? ''),
        'max_age' => $body['max_age'] ?? null,
    ]]]);
    exit;
}

$user = '';
if (str_starts_with($authorization, 'Basic ')) {
    $decoded = base64_decode(substr($authorization, 6), true);
    if (is_string($decoded) && str_contains($decoded, ':')) {
        $user = explode(':', $decoded, 2)[0];
    }
}
echo json_encode(['value' => [[
    'Name' => 'BC Company',
    'via' => 'bc',
    'user' => $user,
]]]);
PHP;
    file_put_contents($mockScript, str_replace('LOG_PATH', $log, $php));
}

function test_mock_requests(): array
{
    global $mockLog;
    if (!is_file($mockLog)) {
        return [];
    }
    $rows = [];
    foreach (file($mockLog, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $decoded = json_decode($line, true);
        if (is_array($decoded)) {
            $rows[] = $decoded;
        }
    }
    return $rows;
}

function test_wait_for_port(string $host, int $port): bool
{
    for ($attempt = 0; $attempt < 50; $attempt++) {
        $socket = @fsockopen($host, $port, $errno, $errstr, 0.2);
        if (is_resource($socket)) {
            fclose($socket);
            return true;
        }
        usleep(100000);
    }
    return false;
}

/**
 * Page load
 */
test_assert('mimir uit zonder key', odata_mimir_enabled() === false);
test_assert('standaard Mímir-base', odata_mimir_base_url() === 'https://sleutels.kvt.nl/mimir/api');

$spaceUrl = bc_company_entity_url(
    'https://bc.example',
    'Production',
    'Koninklijke van Twist',
    'AppItemCard',
    [
        '$select' => 'No,Description',
        '$filter' => "No eq 'ART1'",
    ]
);
$parsedSpace = odata_mimir_parse_entity_url($spaceUrl);
test_assert(
    'entity-URL met spatie in bedrijfsnaam',
    is_array($parsedSpace)
        && ($parsedSpace['company'] ?? '') === 'Koninklijke van Twist'
        && ($parsedSpace['entity'] ?? '') === 'AppItemCard'
        && ($parsedSpace['query']['$select'] ?? '') === 'No,Description'
        && ($parsedSpace['query']['$filter'] ?? '') === "No eq 'ART1'",
    json_encode($parsedSpace, JSON_UNESCAPED_UNICODE)
);
test_assert(
    'entity-URL is geen company-discovery',
    odata_mimir_parse_companies_url($spaceUrl) === null
);

$apostropheUrl = bc_company_entity_url(
    '',
    'Production',
    "Van Twist's",
    'Prijslijstregels',
    ['$select' => 'Asset_No,DirectUnitCost']
);
$parsedApostrophe = odata_mimir_parse_entity_url($apostropheUrl);
test_assert(
    'lege baseUrl en apostrof in bedrijfsnaam',
    is_array($parsedApostrophe)
        && ($parsedApostrophe['company'] ?? '') === "Van Twist's"
        && ($parsedApostrophe['entity'] ?? '') === 'Prijslijstregels'
        && ($parsedApostrophe['query']['$select'] ?? '') === 'Asset_No,DirectUnitCost',
    json_encode($parsedApostrophe, JSON_UNESCAPED_UNICODE)
);

$companiesUrl = 'https://bc.example/Production/ODataV4/Companies?$select=Name';
$parsedCompanies = odata_mimir_parse_companies_url($companiesUrl);
test_assert(
    'companies-URL levert environment',
    is_array($parsedCompanies) && ($parsedCompanies['environment'] ?? '') === 'Production',
    json_encode($parsedCompanies)
);
test_assert('companies-URL is geen entity', odata_mimir_parse_entity_url($companiesUrl) === null);

$threw = false;
try {
    auth_get_auth_for_environment('Production');
} catch (RuntimeException $error) {
    $threw = str_contains($error->getMessage(), 'Geen auth-configuratie');
}
test_assert('BC-auth ontbreekt blijft exception zonder Mímir', $threw);

$threw = false;
try {
    auth_discover_companies_across_active_environments(30);
} catch (RuntimeException $error) {
    $threw = str_contains($error->getMessage(), 'Geen actieve environments');
}
test_assert('company-discovery faalt snel zonder BC-config en zonder Mímir', $threw);

$threw = false;
try {
    aequitas_bc_auth('');
} catch (RuntimeException $error) {
    $threw = str_contains($error->getMessage(), 'Environment ontbreekt');
}
test_assert('aequitas_bc_auth eist environment zonder Mímir', $threw);

$environment = 'Production';
$auth_list = [
    'Production' => [
        'mode' => 'basic',
        'user' => 'bcuser',
        'pass' => 'bcpass',
    ],
];
$baseUrl = '';
$threw = false;
try {
    aequitas_paginate_entity('', 'AppItemCard', [], static function (): bool {
        return false;
    });
} catch (RuntimeException $error) {
    $threw = str_contains($error->getMessage(), 'baseUrl ontbreekt');
}
test_assert('paginate eist baseUrl zonder Mímir', $threw);
unset($environment, $auth_list, $baseUrl, $auth);
unset($GLOBALS['environment'], $GLOBALS['auth_list'], $GLOBALS['baseUrl'], $GLOBALS['auth']);

test_write_mock();
@unlink($mockLog);
$server = proc_open(
    [PHP_BINARY, '-S', '127.0.0.1:' . $mockPort, $mockScript],
    [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ],
    $pipes,
    sys_get_temp_dir()
);
test_assert('mock-server start', is_resource($server) && test_wait_for_port('127.0.0.1', $mockPort));

$mimirApi = 'mimir_test_key';
$mimirBase = 'http://127.0.0.1:' . $mockPort . '/mimir/api';
$baseUrl = '';
unset($GLOBALS['auth_list'], $GLOBALS['environment'], $GLOBALS['auth']);
test_reset_discovery_cache();

try {
    $discovered = auth_discover_companies_across_active_environments(30);
    test_assert(
        'Mímir company-discovery zonder BC-creds',
        ($discovered['companies'] ?? []) === ['Hunter van Twist', 'Koninklijke van Twist', "Van Twist's"]
            && ($discovered['map']['Koninklijke van Twist'] ?? '') === 'Production'
            && ($discovered['map']['Hunter van Twist'] ?? '') === 'Sandbox'
            && ($discovered['primary_environment'] ?? '') === 'Production',
        json_encode($discovered, JSON_UNESCAPED_UNICODE)
    );

    $auth = auth_get_auth_for_environment('Production');
    test_assert('lege auth-sentinel in Mímir-modus', $auth === []);

    $context = auth_set_current_company_context('Hunter van Twist', 30);
    test_assert(
        'company-context zonder BC-auth',
        ($context['environment'] ?? '') === 'Sandbox' && ($context['auth'] ?? null) === []
    );

    $active = auth_get_active_environments();
    test_assert(
        'environments uit Mímir als auth_list ontbreekt',
        $active === ['Production', 'Sandbox'],
        json_encode($active)
    );

    unset($GLOBALS['baseUrl']);
    $bcAuth = aequitas_bc_auth('Koninklijke van Twist');
    test_assert(
        'aequitas_bc_auth zonder baseUrl in Mímir-modus',
        ($bcAuth['environment'] ?? '') === 'Production'
            && ($bcAuth['auth'] ?? null) === []
            && ($bcAuth['baseUrl'] ?? null) === '',
        json_encode($bcAuth)
    );

    $beforeCache = glob($cacheDir . '/*.json') ?: [];
    $seen = null;
    $stats = aequitas_paginate_entity(
        'Koninklijke van Twist',
        'AppItemCard',
        [
            '$select' => 'No,Description',
            '$filter' => "No eq 'ART1'",
        ],
        static function (array $row) use (&$seen): bool {
            $seen = $row;
            return true;
        }
    );
    test_assert(
        'nightly-paginatie via Mímir odata_get_json',
        ($stats['pages'] ?? 0) === 1
            && ($stats['read'] ?? 0) === 1
            && is_array($seen)
            && ($seen['company'] ?? '') === 'Koninklijke van Twist'
            && ($seen['table'] ?? '') === 'AppItemCard'
            && ($seen['filter'] ?? '') === "No eq 'ART1'"
            && ($seen['max_age'] ?? null) === 300
            && in_array('No', $seen['select'] ?? [], true),
        json_encode(['stats' => $stats, 'row' => $seen], JSON_UNESCAPED_UNICODE)
    );
    $afterCache = glob($cacheDir . '/*.json') ?: [];
    test_assert('Mímir slaat Aequitas-filecache over', count($afterCache) === count($beforeCache));

    $fetched = bc_fetch_rows(
        'Koninklijke van Twist',
        'Prijslijstregels',
        [
            '$select' => 'Asset_No',
            '$filter' => "Asset_No eq 'X'",
        ],
        60
    );
    test_assert(
        'bc_fetch_rows via Mímir met lege baseUrl',
        is_array($fetched[0] ?? null)
            && ($fetched[0]['table'] ?? '') === 'Prijslijstregels'
            && ($fetched[0]['max_age'] ?? null) === 60
            && ($fetched[0]['filter'] ?? '') === "Asset_No eq 'X'",
        json_encode($fetched, JSON_UNESCAPED_UNICODE)
    );

    $companyRows = odata_get_all('https://bc.example/Sandbox/ODataV4/Company?$select=Name', [], 30);
    $companyNames = array_map(static function (array $row): string {
        return (string) ($row['Name'] ?? '');
    }, $companyRows);
    test_assert(
        'company-discovery-URL gaat naar Mímir, niet naar BC-host',
        $companyNames === ['Hunter van Twist'],
        json_encode($companyNames, JSON_UNESCAPED_UNICODE)
    );

    $requests = test_mock_requests();
    $hitBcHost = false;
    $sawMimirUa = false;
    foreach ($requests as $request) {
        if (str_contains((string) ($request['uri'] ?? ''), 'bc.example')) {
            $hitBcHost = true;
        }
        if (($request['ua'] ?? '') === 'Aequitas-MimirClient/1.0' && str_contains((string) ($request['uri'] ?? ''), '/mimir/api/')) {
            $sawMimirUa = true;
        }
    }
    test_assert('geen request naar de BC-host', $hitBcHost === false);
    test_assert('Mímir-client user-agent', $sawMimirUa);

    test_reset_discovery_cache();
    $mimirBase = 'http://127.0.0.1:' . $mockPort . '/mimir-dup/api';
    $overlapThrew = false;
    try {
        auth_discover_companies_across_active_environments(30);
    } catch (RuntimeException $error) {
        $overlapThrew = str_contains($error->getMessage(), 'Bedrijfsnaam-overlap');
    }
    test_assert('overlap tussen Mímir-environments blijft een fout', $overlapThrew);

    $mimirApi = '';
    $baseUrl = 'http://127.0.0.1:' . $mockPort;
    $environment = 'Production';
    $auth_list = [
        'Production' => [
            'mode' => 'basic',
            'user' => 'bcuser',
            'pass' => 'bcpass',
        ],
    ];
    $auth = $auth_list['Production'];
    if (is_file($authPath)) {
        $authBackup = file_get_contents($authPath);
    }
    file_put_contents($authPath, "<?php\n\$baseUrl = " . var_export($baseUrl, true) . ";\n\$environment = 'Production';\n\$auth_list = " . var_export($auth_list, true) . ";\n\$mimirApi = '';\n");
    test_reset_discovery_cache();
    @unlink($mockLog);

    test_assert('Mímir uit na lege key', odata_mimir_enabled() === false);
    $bcRows = odata_get_all($baseUrl . '/Production/ODataV4/Companies?$select=Name', $auth, 30);
    test_assert(
        'zonder Mímir blijft BC-fetch werken',
        is_array($bcRows[0] ?? null) && ($bcRows[0]['via'] ?? '') === 'bc' && ($bcRows[0]['user'] ?? '') === 'bcuser',
        json_encode($bcRows)
    );
    $bcRequests = test_mock_requests();
    $bcHitMimir = false;
    foreach ($bcRequests as $request) {
        if (str_contains((string) ($request['uri'] ?? ''), '/mimir/')) {
            $bcHitMimir = true;
        }
    }
    test_assert('BC-fetch raakt Mímir niet', $bcHitMimir === false);
} finally {
    if (is_resource($server)) {
        proc_terminate($server);
        proc_close($server);
    }
    if ($authBackup === null) {
        @unlink($authPath);
    } else {
        file_put_contents($authPath, $authBackup);
    }
    @unlink($mockScript);
    @unlink($mockLog);
    $cacheAfter = glob($cacheDir . '/*.json') ?: [];
    foreach (array_diff($cacheAfter, $cacheBefore) as $created) {
        @unlink($created);
    }
}

if ($failures > 0) {
    fwrite(STDERR, "{$failures} test(s) failed\n");
    exit(1);
}

echo "all mimir routing tests passed\n";
exit(0);

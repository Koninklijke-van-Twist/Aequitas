<?php
/**
 * Simuleert een onbereikbare Mímir en controleert de directe BC-fallback.
 * Run: php tests/mimir_fallback_test.php
 */

$logFile = sys_get_temp_dir() . '/aequitas-mimir-fallback-test.log';
@unlink($logFile);
ini_set('error_log', $logFile);
ini_set('log_errors', '1');

$mimirApi = 'mimir_test_key_should_not_leak';
$mimirBase = 'http://127.0.0.1:9';
$baseUrl = 'https://bc.example:7148/';
$environment = 'Production';
$auth = ['mode' => 'basic', 'user' => 'bcuser', 'pass' => 'bc-secret'];
$auth_list = ['Production' => $auth];

$calls = [];
$GLOBALS['AEQUITAS_ODATA_BC_FETCH'] = static function (string $url, array $auth, int $ttl) use (&$calls): array {
    $calls[] = [
        'url' => $url,
        'user' => (string) ($auth['user'] ?? ''),
        'ttl' => $ttl,
    ];
    if (preg_match('#/ODataV4/Compan(?:y|ies)(?:\\?|$)#', $url) === 1) {
        return [
            ['Name' => 'KVT Gas'],
            ['Name' => 'Hunter van Twist'],
            ['name' => 'Koninklijke van Twist'],
        ];
    }
    return [['No' => 'WO-1']];
};

require dirname(__DIR__) . '/web/odata.php';
require dirname(__DIR__) . '/web/auth_helper.php';

function fail(string $message): void
{
    fwrite(STDERR, "FAIL: $message\n");
    exit(1);
}

function fallback_log(): string
{
    global $logFile;
    $raw = @file_get_contents($logFile);
    return is_string($raw) ? $raw : '';
}

function fallback_count(): int
{
    return substr_count(fallback_log(), '[Aequitas] Mímir failed, falling back to direct OData:');
}

if (odata_mimir_connect_timeout_seconds() !== 10) {
    fail('connect-timeout moet 10s zijn');
}
if (odata_mimir_timeout_seconds_for_sapi('cli') !== 600) {
    fail('CLI-timeout moet 600s blijven');
}
if (odata_mimir_timeout_seconds_for_sapi('fpm-fcgi') !== 90 || odata_mimir_timeout_seconds_for_sapi('apache2handler') !== 90) {
    fail('web-timeout moet ongeveer 90s zijn');
}
if (PHP_SAPI === 'cli' && odata_mimir_timeout_seconds() !== 600) {
    fail('huidige CLI-sapi moet de lange timeout gebruiken');
}

$names = odata_mimir_list_companies(null);
$expectedNames = ['Hunter van Twist', 'Koninklijke van Twist', 'KVT Gas'];
if ($names !== $expectedNames) {
    fail('company-fallback gaf ' . json_encode($names) . ' i.p.v. de gesorteerde BC-namen');
}
if (!odata_mimir_circuit_open()) {
    fail('circuit moet open na de eerste Mímir-fout');
}
$expectedCompanyUrl = 'https://bc.example:7148/Production/ODataV4/Companies?$select=Name';
if (count($calls) !== 1 || ($calls[0]['url'] ?? '') !== $expectedCompanyUrl) {
    fail('company-fallback riep de directe BC-fetch niet aan: ' . json_encode($calls));
}
if ($calls[0]['user'] !== 'bcuser') {
    fail('company-fallback gebruikte niet de BC-credentials');
}

$mimirBase = 'http://192.0.2.1:9';
$started = microtime(true);
$rows = odata_get_all(
    "https://mimir.invalid/Production/ODataV4/Company('Koninklijke%20van%20Twist')/AppWerkorders?\$select=No",
    $auth,
    120
);
$elapsed = microtime(true) - $started;
if ($elapsed >= 2.0) {
    fail('circuit breaker sloeg Mímir niet over (' . round($elapsed, 3) . 's)');
}
if (($rows[0]['No'] ?? '') !== 'WO-1') {
    fail('entity-fallback gaf niet de gestubde BC-rijen terug');
}
$entityCall = $calls[1] ?? null;
$expectedEntityUrl = "https://bc.example:7148/Production/ODataV4/Company('Koninklijke%20van%20Twist')/AppWerkorders?\$select=No";
if (!is_array($entityCall) || $entityCall['url'] !== $expectedEntityUrl || $entityCall['user'] !== 'bcuser' || $entityCall['ttl'] !== 120) {
    fail('entity-fallback URL/auth/ttl klopt niet: ' . json_encode($entityCall));
}
if (fallback_count() !== 1) {
    fail('alleen de eerste Mímir-fout mag een fallback loggen, log=' . fallback_log());
}
$log = fallback_log();
if (strpos($log, 'mimir_test_key_should_not_leak') !== false || strpos($log, 'bc-secret') !== false) {
    fail('log bevat een geheim');
}
if (strpos($log, '[Aequitas] Mímir failed, falling back to direct OData:') === false) {
    fail('logregel mist het verwachte prefix');
}

odata_mimir_circuit_reset();
$mimirBase = 'http://127.0.0.1:9';
$beforeQuery = count($calls);
$queryRows = odata_mimir_query('KVT Gas', 'AppResource', ['$select' => 'No,Name'], 60);
if (($queryRows[0]['No'] ?? '') !== 'WO-1') {
    fail('odata_mimir_query viel niet terug op de stub');
}
$queryCall = $calls[$beforeQuery] ?? null;
if (!is_array($queryCall) || strpos($queryCall['url'], "https://bc.example:7148/Production/ODataV4/Company('KVT%20Gas')/AppResource?") !== 0) {
    fail('query-fallback bouwde niet de pre-Mímir BC-URL: ' . json_encode($queryCall));
}

odata_mimir_circuit_reset();
$beforeFetch = count($calls);
$fetchRows = odata_mimir_fetch_all(
    "https://mimir.invalid/Production/ODataV4/Company('KVT%20Gas')/AppWerkorders?\$select=No",
    15
);
if (($fetchRows[0]['No'] ?? '') !== 'WO-1') {
    fail('odata_mimir_fetch_all viel niet terug');
}
$fetchCall = $calls[$beforeFetch] ?? null;
if (!is_array($fetchCall) || $fetchCall['url'] !== "https://bc.example:7148/Production/ODataV4/Company('KVT%20Gas')/AppWerkorders?\$select=No") {
    fail('fetch_all-fallback herschreef de URL niet: ' . json_encode($fetchCall));
}

odata_mimir_circuit_reset();
$beforeJson = count($calls);
$started = microtime(true);
$page = odata_get_json(
    "https://mimir.invalid/Production/ODataV4/Company('KVT%20Gas')/Prijslijstregels?\$select=No",
    $auth
);
$firstJson = microtime(true) - $started;
if ($firstJson >= 5.0) {
    fail('nightly odata_get_json bleef te lang op Mímir hangen (' . round($firstJson, 3) . 's)');
}
if (($page['value'][0]['No'] ?? '') !== 'WO-1') {
    fail('odata_get_json (nightly) viel niet terug op de stub');
}
$jsonCall = $calls[$beforeJson] ?? null;
$expectedJsonUrl = "https://bc.example:7148/Production/ODataV4/Company('KVT%20Gas')/Prijslijstregels?\$select=No";
if (!is_array($jsonCall) || $jsonCall['url'] !== $expectedJsonUrl || $jsonCall['user'] !== 'bcuser') {
    fail('nightly-fallback herschreef de URL niet: ' . json_encode($jsonCall));
}
$started = microtime(true);
$secondPage = odata_get_json($expectedJsonUrl, $auth);
$secondJson = microtime(true) - $started;
if ($secondJson >= 2.0) {
    fail('circuit breaker sloeg Mímir bij nightly-paginatie niet over (' . round($secondJson, 3) . 's)');
}
if (($secondPage['value'][0]['No'] ?? '') !== 'WO-1') {
    fail('tweede nightly-pagina viel niet terug');
}

odata_mimir_circuit_reset();
$map = odata_mimir_company_environment_map(null);
if (($map['Hunter van Twist'] ?? '') !== 'Production' || ($map['KVT Gas'] ?? '') !== 'Production') {
    fail('environment-map viel niet terug op BC: ' . json_encode($map));
}

odata_mimir_circuit_reset();
$GLOBALS['demeter_company_environment_map'] = null;
$GLOBALS['demeter_companies_by_environment'] = null;
$GLOBALS['demeter_active_environments'] = null;
$discovered = auth_discover_companies_across_active_environments(30);
$discoveredNames = is_array($discovered['companies'] ?? null) ? $discovered['companies'] : [];
sort($discoveredNames, SORT_NATURAL | SORT_FLAG_CASE);
$expectedDiscovered = $expectedNames;
sort($expectedDiscovered, SORT_NATURAL | SORT_FLAG_CASE);
if ($discoveredNames !== $expectedDiscovered || ($discovered['map']['KVT Gas'] ?? '') !== 'Production') {
    fail('company-discovery viel niet terug op BC: ' . json_encode($discovered));
}

odata_mimir_circuit_reset();
$mimirBase = 'http://127.0.0.1:9';
$sandboxAuth = ['mode' => 'basic', 'user' => 'sandbox-user', 'pass' => 'sandbox-secret'];
$auth_list = [
    'Production' => $auth,
    'Sandbox' => $sandboxAuth,
];
$GLOBALS['demeter_company_environment_map'] = [
    'Hunter van Twist' => 'Sandbox',
    'KVT Gas' => 'Production',
];

$encodedUrl = odata_bc_url_from_odata_url("https://mimir.invalid/My%20Env/ODataV4/Company('X')/Projecten?\$select=No");
$expectedEncoded = "https://bc.example:7148/My%20Env/ODataV4/Company('X')/Projecten?\$select=No";
if ($encodedUrl !== $expectedEncoded) {
    fail('env-segment moet één keer gecodeerd blijven: ' . $encodedUrl);
}

$loggedBeforeSecondEnv = fallback_count();
$beforeSandbox = count($calls);
$sandboxRows = odata_get_all(
    "https://mimir.invalid/Sandbox/ODataV4/Company('Hunter%20van%20Twist')/AppWerkorders?\$select=No",
    $auth,
    30
);
if (($sandboxRows[0]['No'] ?? '') !== 'WO-1') {
    fail('tweede environment gaf niet de gestubde rij terug');
}
$sandboxCall = $calls[$beforeSandbox] ?? null;
if (!is_array($sandboxCall) || strpos((string) $sandboxCall['url'], "https://bc.example:7148/Sandbox/ODataV4/Company(") !== 0 || $sandboxCall['user'] !== 'sandbox-user') {
    fail('bedrijf in Sandbox moet de Sandbox-auth en -URL gebruiken: ' . json_encode($sandboxCall));
}
if (fallback_count() !== $loggedBeforeSecondEnv + 1) {
    fail('een verse Mímir-fout moet precies één fallback loggen, log=' . fallback_log());
}
$loggedWhileOpen = fallback_count();
$callsWhileOpen = count($calls);
odata_get_all(
    "https://mimir.invalid/Sandbox/ODataV4/Company('Hunter%20van%20Twist')/AppWerkorders?\$select=No",
    $auth,
    30
);
if (fallback_count() !== $loggedWhileOpen) {
    fail('open circuit mag niet opnieuw een fallback loggen, log=' . fallback_log());
}
if (count($calls) !== $callsWhileOpen + 1) {
    fail('open circuit moet wel de directe fetch doen');
}

odata_mimir_circuit_reset();
$beforeHunterQuery = count($calls);
$hunterRows = odata_mimir_query('Hunter van Twist', 'AppWerkorders', ['$select' => 'No'], 30);
if (($hunterRows[0]['No'] ?? '') !== 'WO-1') {
    fail('query voor een Sandbox-bedrijf viel niet terug');
}
$hunterCall = $calls[$beforeHunterQuery] ?? null;
if (!is_array($hunterCall) || strpos((string) $hunterCall['url'], "https://bc.example:7148/Sandbox/ODataV4/Company('Hunter%20van%20Twist')/AppWerkorders?") !== 0 || $hunterCall['user'] !== 'sandbox-user') {
    fail('query moet het bedrijf in de environment-map opzoeken: ' . json_encode($hunterCall));
}

odata_mimir_circuit_reset();
$beforeMimirSegment = count($calls);
odata_get_all(
    "https://mimir.invalid/mimir/ODataV4/Company('Hunter%20van%20Twist')/AppWerkorders?\$select=No",
    $auth,
    30
);
$mimirSegmentCall = $calls[$beforeMimirSegment] ?? null;
if (!is_array($mimirSegmentCall) || strpos((string) $mimirSegmentCall['url'], "https://bc.example:7148/Sandbox/ODataV4/Company(") !== 0 || $mimirSegmentCall['user'] !== 'sandbox-user') {
    fail('placeholder-environment mimir moet via de company-map naar Sandbox: ' . json_encode($mimirSegmentCall));
}

odata_mimir_circuit_reset();
$beforeBothEnvs = count($calls);
$bothNames = odata_mimir_list_companies(null);
if ($bothNames !== $expectedNames) {
    fail('company-lijst over beide environments gaf ' . json_encode($bothNames));
}
$productionCompanies = 'https://bc.example:7148/Production/ODataV4/Companies?$select=Name';
$sandboxCompanies = 'https://bc.example:7148/Sandbox/ODataV4/Companies?$select=Name';
$envCalls = array_slice($calls, $beforeBothEnvs);
if (count($envCalls) !== 2 || ($envCalls[0]['url'] ?? '') !== $productionCompanies || ($envCalls[0]['user'] ?? '') !== 'bcuser' || ($envCalls[1]['url'] ?? '') !== $sandboxCompanies || ($envCalls[1]['user'] ?? '') !== 'sandbox-user') {
    fail('company-lijst moet elk auth_list-environment met zijn eigen auth bevragen: ' . json_encode($envCalls));
}

odata_mimir_circuit_reset();
$loggedBeforeCaller = fallback_count();
$callsBeforeCaller = count($calls);
$callerError = null;
try {
    odata_mimir_or_direct(
        static function (): array {
            throw new RuntimeException('caller-fout buiten Mímir');
        },
        static function (): array {
            return [['No' => 'SHOULD-NOT']];
        }
    );
} catch (Throwable $exception) {
    $callerError = $exception;
}
if (!$callerError instanceof RuntimeException) {
    fail('caller-exception moet blijven staan');
}
if (odata_mimir_circuit_open()) {
    fail('een exception van de caller mag het circuit niet openen');
}
if (count($calls) !== $callsBeforeCaller || fallback_count() !== $loggedBeforeCaller) {
    fail('een exception van de caller mag geen fallback starten');
}

odata_mimir_circuit_reset();
$callsBeforeTranslate = count($calls);
$translateError = null;
try {
    odata_mimir_fetch_all('https://example.invalid/not-odata', 10);
} catch (Throwable $exception) {
    $translateError = $exception;
}
if (!$translateError instanceof Throwable || strpos($translateError->getMessage(), 'kon niet worden vertaald') === false) {
    fail('onvertaalbare URL moet die fout blijven gooien');
}
if (odata_mimir_circuit_open() || count($calls) !== $callsBeforeTranslate) {
    fail('een URL-vertaalfout mag het circuit niet openen');
}

$savedEnvironment = $environment;
$environment = 'mimir';
$cacheKey = build_cache_key(
    "https://bc.example:7148/Sandbox/ODataV4/Company('Hunter%20van%20Twist')/AppWerkorders?\$select=No",
    $sandboxAuth
);
$environment = $savedEnvironment;
$cacheSuffix = '|sandbox-user|Sandbox';
if (substr($cacheKey, -strlen($cacheSuffix)) !== $cacheSuffix || strpos($cacheKey, '|mimir') !== false) {
    fail('cache-key moet de echte BC-environment gebruiken: ' . $cacheKey);
}

$loggedBeforeRethrow = fallback_count();
$callsBeforeRethrow = count($calls);
odata_mimir_circuit_reset();
$mimirBase = 'http://127.0.0.1:9';
$baseUrl = 'https://mimir.invalid/';
$environment = 'mimir';
$auth = [];
$auth_list = [];
$rethrown = null;
try {
    odata_get_all('https://mimir.invalid/mimir/ODataV4/Company(\'X\')/AppWerkorders', ['mode' => 'basic', 'user' => '', 'pass' => ''], 30);
    fail('zonder BC-credentials moet de oorspronkelijke Mímir-fout terugkomen');
} catch (Throwable $exception) {
    $rethrown = $exception;
}
if (!$rethrown instanceof Throwable || strpos($rethrown->getMessage(), 'Mímir') === false) {
    fail('hergooide fout is niet de Mímir-fout: ' . ($rethrown instanceof Throwable ? $rethrown->getMessage() : 'geen'));
}
if (stripos($rethrown->getMessage(), 'credential') !== false) {
    fail('hergooide fout maskeert Mímir met een credentials-melding: ' . $rethrown->getMessage());
}
if (count($calls) !== $callsBeforeRethrow) {
    fail('zonder BC-credentials mag de directe fetch niet starten');
}
if (fallback_count() !== $loggedBeforeRethrow) {
    fail('zonder BC-credentials mag er geen fallback gelogd worden');
}

odata_mimir_circuit_reset();
$mimirApi = '';
$mimirBase = 'http://127.0.0.1:9';
$baseUrl = 'https://bc.example:7148/';
$environment = 'Production';
$auth = ['mode' => 'basic', 'user' => 'bcuser', 'pass' => 'bc-secret'];
$auth_list = ['Production' => $auth];
$loggedBeforeDirect = fallback_count();
$directOnlyUrl = 'https://mimir.invalid/Production/ODataV4/Company(\'KVT%20Gas\')/AppWerkorders?$select=No';
$directRows = odata_get_all($directOnlyUrl, $auth, 45);
if (odata_mimir_circuit_open()) {
    fail('lege $mimirApi mag Mímir niet proberen');
}
if (fallback_count() !== $loggedBeforeDirect) {
    fail('lege $mimirApi mag geen Mímir-fallback loggen');
}
$directCall = $calls[count($calls) - 1] ?? null;
if (($directRows[0]['No'] ?? '') !== 'WO-1' || !is_array($directCall) || $directCall['url'] !== $directOnlyUrl) {
    fail('lege $mimirApi moet de oude directe route ongewijzigd gebruiken: ' . json_encode($directCall));
}

$authFile = sys_get_temp_dir() . '/aequitas-auth-globals.php';
file_put_contents(
    $authFile,
    "<?php\n"
    . "\$baseUrl = 'https://bc-from-file.example:7148/';\n"
    . "\$base = 'https://bc-from-file.example:7148/';\n"
    . "\$environment = 'Sandbox';\n"
    . "\$auth = ['mode' => 'basic', 'user' => 'file-user', 'pass' => 'file-secret'];\n"
    . "\$auth_list = ['Sandbox' => \$auth];\n"
    . "\$mimirBase = 'https://should-not-replace.example';\n"
);
unset($GLOBALS['baseUrl'], $GLOBALS['base'], $GLOBALS['environment'], $GLOBALS['auth'], $GLOBALS['auth_list'], $GLOBALS['AEQUITAS_BC_AUTH_LOAD_TRIED']);
unset($baseUrl, $base, $environment, $auth, $auth_list);
$GLOBALS['AEQUITAS_AUTH_PHP_PATH'] = $authFile;
$GLOBALS['baseUrl'] = 'https://keep.example:7148/';
$GLOBALS['mimirBase'] = '';
odata_bc_ensure_config_loaded();
if (($GLOBALS['baseUrl'] ?? '') !== 'https://keep.example:7148/') {
    fail('lazy auth-load mag een gezette baseUrl niet overschrijven');
}
if (($GLOBALS['mimirBase'] ?? null) !== '') {
    fail('lazy auth-load mag een gezette (lege) mimirBase niet overschrijven');
}
if (($GLOBALS['environment'] ?? '') !== 'Sandbox' || ($GLOBALS['base'] ?? '') !== 'https://bc-from-file.example:7148/') {
    fail('lazy auth-load moet environment en base naar $GLOBALS kopiëren');
}
$loadedAuth = $GLOBALS['auth'] ?? null;
$loadedList = $GLOBALS['auth_list'] ?? null;
if (!is_array($loadedAuth) || ($loadedAuth['user'] ?? '') !== 'file-user' || !is_array($loadedList) || ($loadedList['Sandbox']['user'] ?? '') !== 'file-user') {
    fail('lazy auth-load moet auth en auth_list naar $GLOBALS kopiëren');
}
if (odata_bc_environment() !== 'Sandbox') {
    fail('gekopieerde environment is niet zichtbaar voor de BC-helpers');
}
odata_bc_ensure_config_loaded();
if (($GLOBALS['baseUrl'] ?? '') !== 'https://keep.example:7148/' || ($GLOBALS['environment'] ?? '') !== 'Sandbox') {
    fail('een tweede ensure mag de gekopieerde config niet wissen');
}
@unlink($authFile);

$authFileNull = sys_get_temp_dir() . '/aequitas-auth-null-globals.php';
file_put_contents(
    $authFileNull,
    "<?php\n"
    . "\$baseUrl = 'https://bc-from-file.example:7148/';\n"
    . "\$environment = 'Sandbox';\n"
    . "\$auth = ['mode' => 'basic', 'user' => 'file-user', 'pass' => 'file-secret'];\n"
    . "\$auth_list = ['Sandbox' => \$auth];\n"
    . "\$mimirApi = 'mimir_from_file_should_not_leak';\n"
    . "\$mimirBase = 'https://should-not-replace.example';\n"
);
unset($GLOBALS['mimirApi'], $GLOBALS['auth'], $GLOBALS['auth_list'], $GLOBALS['environment'], $GLOBALS['AEQUITAS_BC_AUTH_LOAD_TRIED']);
unset($mimirApi, $auth, $auth_list, $environment);
(static function (): void {
    global $mimirApi, $auth, $auth_list;
})();
if (!array_key_exists('mimirApi', $GLOBALS) || $GLOBALS['mimirApi'] !== null || $GLOBALS['auth'] !== null || $GLOBALS['auth_list'] !== null) {
    fail('global op een ontbrekende variabele moet een null-entry in $GLOBALS maken');
}
$GLOBALS['baseUrl'] = 'https://keep.example:7148/';
$GLOBALS['mimirBase'] = '';
$GLOBALS['AEQUITAS_AUTH_PHP_PATH'] = $authFileNull;
odata_bc_ensure_config_loaded();
if (($GLOBALS['mimirApi'] ?? '') !== 'mimir_from_file_should_not_leak') {
    fail('een null-global mimirApi moet alsnog uit auth.php komen');
}
$nullAuth = $GLOBALS['auth'] ?? null;
$nullList = $GLOBALS['auth_list'] ?? null;
if (!is_array($nullAuth) || ($nullAuth['user'] ?? '') !== 'file-user' || !is_array($nullList) || ($nullList['Sandbox']['user'] ?? '') !== 'file-user') {
    fail('null-globals auth en auth_list moeten uit auth.php komen');
}
if (($GLOBALS['baseUrl'] ?? '') !== 'https://keep.example:7148/' || ($GLOBALS['mimirBase'] ?? null) !== '') {
    fail('null-global reparatie mag gezette baseUrl of lege mimirBase niet overschrijven');
}
if (odata_mimir_api_key() !== 'mimir_from_file_should_not_leak') {
    fail('gekopieerde mimirApi is niet zichtbaar via odata_mimir_api_key');
}
@unlink($authFileNull);
unset($GLOBALS['AEQUITAS_AUTH_PHP_PATH']);

require_once dirname(__DIR__) . '/web/aequitas_data.php';

odata_mimir_circuit_reset();
unset(
    $GLOBALS['demeter_company_environment_map'],
    $GLOBALS['demeter_companies_by_environment'],
    $GLOBALS['demeter_active_environments']
);
$mimirApi = 'mimir_test_key_should_not_leak';
$mimirBase = 'http://127.0.0.1:9';
$baseUrl = 'https://bc.example:7148/';
$environment = 'Production';
$auth = ['mode' => 'basic', 'user' => 'bcuser', 'pass' => 'bc-secret'];
unset($auth_list);
$beforePage = count($calls);
try {
    $pageStats = aequitas_paginate_entity('KVT Gas', 'Prijslijstregels', ['$select' => 'No'], static function (): bool {
        return true;
    });
} catch (Throwable $pageError) {
    fail('nightly-paginatie met alleen $auth moet op BC terugvallen: ' . $pageError->getMessage());
}
if (($pageStats['read'] ?? 0) < 1) {
    fail('nightly-paginatie met alleen $auth las geen rij: ' . json_encode($pageStats));
}
$pageCall = null;
for ($i = $beforePage; $i < count($calls); $i++) {
    if (strpos((string) ($calls[$i]['url'] ?? ''), '/Prijslijstregels?') !== false) {
        $pageCall = $calls[$i];
    }
}
if (!is_array($pageCall) || $pageCall['user'] !== 'bcuser' || strpos((string) $pageCall['url'], 'https://bc.example:7148/Production/ODataV4/Company(') !== 0) {
    fail('pagina-fallback gebruikte niet de oorspronkelijke $auth: ' . json_encode($pageCall));
}
if (auth_get_auth_for_environment('Production') !== []) {
    fail('auth_get_auth_for_environment moet in Mímir-modus zonder $auth_list de lege sentinel houden');
}

odata_mimir_circuit_reset();
unset(
    $GLOBALS['demeter_company_environment_map'],
    $GLOBALS['demeter_companies_by_environment'],
    $GLOBALS['demeter_active_environments']
);
$GLOBALS['demeter_company_environment_map'] = ['KVT Gas' => 'Production'];
$environment = 'Production';
$auth = ['mode' => 'basic', 'user' => 'bcuser', 'pass' => 'bc-secret'];
$auth_list = ['Sandbox' => ['mode' => 'basic', 'user' => 'sand', 'pass' => 'sand-secret']];
$beforePrimary = count($calls);
try {
    $primaryStats = aequitas_paginate_entity('KVT Gas', 'Prijslijstregels', ['$select' => 'No'], static function (): bool {
        return true;
    });
} catch (Throwable $primaryError) {
    fail('primair environment zonder auth_list-entry moet $auth gebruiken: ' . $primaryError->getMessage());
}
if (($primaryStats['read'] ?? 0) < 1) {
    fail('primair environment las geen rij: ' . json_encode($primaryStats));
}
$primaryCall = null;
for ($i = $beforePrimary; $i < count($calls); $i++) {
    if (strpos((string) ($calls[$i]['url'] ?? ''), '/Prijslijstregels?') !== false) {
        $primaryCall = $calls[$i];
    }
}
if (!is_array($primaryCall) || $primaryCall['user'] !== 'bcuser' || strpos((string) $primaryCall['url'], 'https://bc.example:7148/Production/ODataV4/Company(') !== 0) {
    fail('bedrijf op het primaire environment viel niet terug op $auth: ' . json_encode($primaryCall));
}

$log = fallback_log();
if (strpos($log, 'sandbox-secret') !== false || strpos($log, 'file-secret') !== false || strpos($log, 'bc-secret') !== false || strpos($log, 'sand-secret') !== false || strpos($log, 'mimir_test_key_should_not_leak') !== false || strpos($log, 'mimir_from_file_should_not_leak') !== false) {
    fail('log bevat een geheim');
}

echo "OK\n";

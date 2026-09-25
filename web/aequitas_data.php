<?php

/**
 * Includes/requires
 */
require_once __DIR__ . '/aequitas_config.php';
require_once __DIR__ . '/bc_data.php';
require_once __DIR__ . '/odata.php';

/**
 * Functies
 */

function aequitas_cache_base_dir(): string
{
    $dir = __DIR__ . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'aequitas'
        . DIRECTORY_SEPARATOR . 'v' . AEQUITAS_CACHE_VERSION;
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }

    return $dir;
}

function aequitas_company_slug(string $company): string
{
    $slug = strtolower(trim($company));
    $slug = preg_replace('/[^a-z0-9]+/', '_', $slug) ?? '';
    $slug = trim((string) $slug, '_');

    return $slug !== '' ? $slug : 'company';
}

function aequitas_company_cache_files(string $company): array
{
    $base = aequitas_cache_base_dir() . DIRECTORY_SEPARATOR . aequitas_company_slug($company);

    return [
        'meta' => $base . '.meta.json',
        'items' => $base . '.items.jsonl',
        'prices' => $base . '.prices.jsonl',
        'price_index' => $base . '.price_index.json',
    ];
}

function aequitas_scalar_string(mixed $value): string
{
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }

    if (is_scalar($value) || $value === null) {
        return trim((string) $value);
    }

    return '';
}

function aequitas_scalar_float(mixed $value): float
{
    if (is_int($value) || is_float($value)) {
        return (float) $value;
    }

    $text = str_replace(',', '.', aequitas_scalar_string($value));
    if ($text === '' || !is_numeric($text)) {
        return 0.0;
    }

    return (float) $text;
}

function aequitas_parse_date(mixed $value): string
{
    $text = aequitas_scalar_string($value);
    if ($text === '') {
        return '';
    }

    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $text, $match) !== 1) {
        return '';
    }

    $date = $match[1];
    if ($date < '1900-01-01') {
        return '';
    }

    $parts = explode('-', $date);
    if (!checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
        return '';
    }

    return $date;
}

function aequitas_date_in_range(string $start, string $end, string $today): bool
{
    // Lege / 0001-01-01 begindatum = al geldig; lege / 0001-01-01 einddatum = oneindig.
    if ($start !== '' && $start > $today) {
        return false;
    }

    if ($end !== '' && $end < $today) {
        return false;
    }

    return true;
}

function aequitas_price_lines_odata_date_filter(?string $today = null): string
{
    $today = $today ?? (new DateTimeImmutable('today'))->format('Y-m-d');

    // Starting_Date <= vandaag (of leeg/0001-01-01), Ending_Date >= vandaag of 0001-01-01 (oneindig).
    return '(Starting_Date eq 0001-01-01 or Starting_Date le ' . $today . ')'
        . ' and (Ending_Date eq 0001-01-01 or Ending_Date ge ' . $today . ')';
}

function aequitas_norm_token(string $value): string
{
    return strtolower(trim($value));
}

function aequitas_contains_any(string $haystack, array $needles): bool
{
    foreach ($needles as $needle) {
        if ($needle !== '' && str_contains($haystack, $needle)) {
            return true;
        }
    }

    return false;
}

function aequitas_is_item_asset(string $assetType): bool
{
    $value = aequitas_norm_token($assetType);
    if ($value === '') {
        return true;
    }

    return aequitas_contains_any($value, ['item', 'artikel']);
}

function aequitas_is_purchase_source(string $sourceType): bool
{
    $value = aequitas_norm_token($sourceType);
    if ($value === '') {
        return true;
    }

    if (aequitas_contains_any($value, ['customer', 'klant', 'campaign', 'campagne', 'job', 'project', 'contact'])) {
        return false;
    }

    return true;
}

function aequitas_is_price_amount(string $amountType): bool
{
    $value = aequitas_norm_token($amountType);
    if ($value === '') {
        return true;
    }

    if (aequitas_contains_any($value, ['discount', 'korting'])) {
        return false;
    }

    return true;
}

function aequitas_is_active_status(string $status): bool
{
    $value = aequitas_norm_token($status);
    if ($value === '') {
        return true;
    }

    if (aequitas_contains_any($value, ['draft', 'concept', 'inactive', 'inactief'])) {
        return false;
    }

    return true;
}

function aequitas_slim_item(array $row): array
{
    return [
        'No' => aequitas_scalar_string($row['No'] ?? ''),
        'Description' => aequitas_scalar_string($row['Description'] ?? ''),
        'Vendor_No' => aequitas_scalar_string($row['Vendor_No'] ?? ''),
        'Vendor_Name' => aequitas_scalar_string($row['LVS_Vendor_Name'] ?? $row['Vendor_Name'] ?? ''),
        'Last_Direct_Cost' => aequitas_scalar_float($row['Last_Direct_Cost'] ?? 0),
        'Base_Unit_of_Measure' => aequitas_scalar_string($row['Base_Unit_of_Measure'] ?? ''),
        'Blocked' => (bool) ($row['Blocked'] ?? false),
    ];
}

function aequitas_slim_price_line(array $row): array
{
    return [
        'Price_List_Code' => aequitas_scalar_string($row['Price_List_Code'] ?? ''),
        'Line_No' => (int) ($row['Line_No'] ?? 0),
        'PriceListDescription' => aequitas_scalar_string($row['PriceListDescription'] ?? ''),
        'Status' => aequitas_scalar_string($row['Status'] ?? ''),
        'Source_Type' => aequitas_scalar_string($row['Source_Type'] ?? ''),
        'Source_No' => aequitas_scalar_string($row['Source_No'] ?? ''),
        'Asset_Type' => aequitas_scalar_string($row['Asset_Type'] ?? ''),
        'Asset_No' => aequitas_scalar_string($row['Asset_No'] ?? ''),
        'Description' => aequitas_scalar_string($row['Description'] ?? ''),
        'Unit_of_Measure_Code' => aequitas_scalar_string($row['Unit_of_Measure_Code'] ?? ''),
        'Minimum_Quantity' => aequitas_scalar_float($row['Minimum_Quantity'] ?? 0),
        'Amount_Type' => aequitas_scalar_string($row['Amount_Type'] ?? ''),
        'DirectUnitCost' => aequitas_scalar_float($row['DirectUnitCost'] ?? 0),
        'Starting_Date' => aequitas_parse_date($row['Starting_Date'] ?? ''),
        'Ending_Date' => aequitas_parse_date($row['Ending_Date'] ?? ''),
    ];
}

function aequitas_bc_auth(string $company = ''): array
{
    global $baseUrl;

    $companyName = trim($company);
    if ($companyName !== '') {
        auth_set_current_company_context($companyName, 1);
        $env = auth_get_environment_for_company($companyName, 1);
    } else {
        $env = auth_get_primary_environment();
    }

    $env = trim((string) $env);
    $authConfig = $env !== '' ? auth_get_auth_for_environment($env) : [];

    // Mímir-modus: OData-URL's worden in odata_get_json vertaald; BC baseUrl/auth zijn dan niet nodig.
    $mimirEnabled = function_exists('auth_mimir_enabled') && auth_mimir_enabled();

    if ($env === '' && !$mimirEnabled) {
        throw new RuntimeException('Environment ontbreekt in auth-configuratie.');
    }

    if ($authConfig === [] && !$mimirEnabled) {
        throw new RuntimeException('Geen auth-configuratie gevonden voor environment: ' . $env);
    }

    return [
        'baseUrl' => trim((string) ($baseUrl ?? '')),
        'environment' => $env,
        'auth' => $authConfig,
    ];
}

function aequitas_resolve_next_url(string $currentUrl, mixed $next): string
{
    $nextUrl = trim((string) $next);
    if ($nextUrl === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $nextUrl) === 1) {
        return $nextUrl;
    }

    $parts = parse_url($currentUrl);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
        return $nextUrl;
    }

    $origin = $parts['scheme'] . '://' . $parts['host'];
    if (!empty($parts['port'])) {
        $origin .= ':' . $parts['port'];
    }

    if (str_starts_with($nextUrl, '/')) {
        return $origin . $nextUrl;
    }

    $path = (string) ($parts['path'] ?? '/');
    $dir = rtrim(str_replace('\\', '/', dirname($path)), '/');

    return $origin . $dir . '/' . $nextUrl;
}

function aequitas_write_jsonl_row($handle, array $row): void
{
    $json = json_encode($row, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new RuntimeException('Cache JSONL encoderen mislukt');
    }

    fwrite($handle, $json . "\n");
}

function aequitas_replace_cache_file(string $tmpPath, string $finalPath): void
{
    aequitas_commit_cache_files([[$tmpPath, $finalPath]]);
}

/**
 * Vervang cachebestanden pas als elk tijdelijk bestand klaarstaat.
 * Mislukt een stap, dan gaat elke al geplaatste file terug naar de vorige versie.
 *
 * @param array<int, array{0: string, 1: string}> $pairs
 */
function aequitas_commit_cache_files(array $pairs): void
{
    $done = [];
    $pendingFinal = null;
    $pendingBackup = null;

    try {
        foreach ($pairs as $pair) {
            $tmpPath = $pair[0];
            $finalPath = $pair[1];
            if (!is_file($tmpPath)) {
                throw new RuntimeException('Tijdelijk cachebestand ontbreekt: ' . $tmpPath);
            }

            $backup = $finalPath . '.bak';
            $pendingFinal = null;
            $pendingBackup = null;
            if (is_file($finalPath)) {
                if (is_file($backup) && !@unlink($backup) && is_file($backup)) {
                    throw new RuntimeException('Oude cache-backup kon niet worden vervangen: ' . $backup);
                }
                if (!@rename($finalPath, $backup)) {
                    throw new RuntimeException('Oud cachebestand kon niet worden veiliggesteld: ' . $finalPath);
                }
                $pendingFinal = $finalPath;
                $pendingBackup = $backup;
            }

            if (!@rename($tmpPath, $finalPath)) {
                throw new RuntimeException('Cachebestand kon niet worden geplaatst: ' . $finalPath);
            }

            $done[] = [$finalPath, $pendingBackup];
            $pendingFinal = null;
            $pendingBackup = null;
        }

        foreach ($done as $entry) {
            $backup = $entry[1];
            if (is_string($backup) && is_file($backup)) {
                @unlink($backup);
            }
        }
    } catch (Throwable $error) {
        if ($pendingFinal !== null && $pendingBackup !== null && !is_file($pendingFinal) && is_file($pendingBackup)) {
            @rename($pendingBackup, $pendingFinal);
        }

        for ($index = count($done) - 1; $index >= 0; $index--) {
            [$finalPath, $backup] = $done[$index];
            if (is_file($finalPath)) {
                @unlink($finalPath);
            }
            if (is_string($backup) && is_file($backup)) {
                @rename($backup, $finalPath);
            }
        }

        throw $error;
    }
}

function aequitas_write_meta_file(string $path, array $meta): void
{
    $json = json_encode($meta, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new RuntimeException('Cache-meta encoderen mislukt');
    }

    $tmp = $path . '.tmp';
    file_put_contents($tmp, $json, LOCK_EX);
    aequitas_replace_cache_file($tmp, $path);
}

function aequitas_read_jsonl(string $path): Generator
{
    if (!is_file($path)) {
        return;
    }

    $handle = fopen($path, 'r');
    if ($handle === false) {
        return;
    }

    try {
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $row = json_decode($line, true);
            if (is_array($row)) {
                yield $row;
            }
        }
    } finally {
        fclose($handle);
    }
}

function aequitas_is_usable_price_line(array $line, string $today): bool
{
    if (!aequitas_is_item_asset((string) ($line['Asset_Type'] ?? ''))) {
        return false;
    }

    if (!aequitas_is_purchase_source((string) ($line['Source_Type'] ?? ''))) {
        return false;
    }

    if (!aequitas_is_price_amount((string) ($line['Amount_Type'] ?? ''))) {
        return false;
    }

    if (!aequitas_is_active_status((string) ($line['Status'] ?? ''))) {
        return false;
    }

    $start = aequitas_parse_date($line['Starting_Date'] ?? '');
    $end = aequitas_parse_date($line['Ending_Date'] ?? '');

    return aequitas_date_in_range($start, $end, $today);
}

function aequitas_price_line_sort_key(array $line): string
{
    $start = aequitas_parse_date($line['Starting_Date'] ?? '');
    if ($start === '') {
        $start = '0001-01-01';
    }

    $lineNo = str_pad((string) ((int) ($line['Line_No'] ?? 0)), 10, '0', STR_PAD_LEFT);

    return $start . '-' . $lineNo;
}

function aequitas_prices_equal(float $left, float $right): bool
{
    return (int) round($left * 100) === (int) round($right * 100);
}

function aequitas_item_should_keep(array $item, array $priceInfo): bool
{
    if (!empty($priceInfo['conflict'])) {
        return true;
    }

    $lastDirectCost = aequitas_scalar_float($item['Last_Direct_Cost'] ?? 0);
    $purchasePrice = aequitas_scalar_float($priceInfo['purchase_price'] ?? 0);

    return !aequitas_prices_equal($lastDirectCost, $purchasePrice);
}

function aequitas_paginate_entity(string $company, string $entitySet, array $query, callable $onRow): array
{
    $ctx = aequitas_bc_auth($company);
    // Lege $baseUrl is geldig in Mímir-modus; odata_get_json vertaalt het pad.
    $mimirEnabled = function_exists('odata_mimir_enabled') && odata_mimir_enabled();
    if ($ctx['baseUrl'] === '' && !$mimirEnabled) {
        throw new RuntimeException('baseUrl ontbreekt in auth-configuratie.');
    }

    $kept = 0;
    $read = 0;
    $pages = 0;
    $url = bc_company_entity_url($ctx['baseUrl'], $ctx['environment'], $company, $entitySet, $query);

    while ($url !== '') {
        $resp = odata_get_json($url, $ctx['auth']);
        if (!isset($resp['value']) || !is_array($resp['value'])) {
            throw new RuntimeException("OData response missing 'value' array");
        }

        $rows = $resp['value'];
        $rowCount = count($rows);
        $nextLink = aequitas_resolve_next_url($url, $resp['@odata.nextLink'] ?? '');
        unset($resp);

        $pages++;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $read++;
            if ($onRow($row)) {
                $kept++;
            }
        }

        unset($rows, $row);
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }

        $url = $nextLink;
    }

    return [
        'kept' => $kept,
        'read' => $read,
        'pages' => $pages,
    ];
}

function aequitas_write_prices_file(string $company, string $targetPath): array
{
    $today = (new DateTimeImmutable('today'))->format('Y-m-d');
    $handle = fopen($targetPath, 'wb');
    if ($handle === false) {
        throw new RuntimeException('Kan prijslijst-cache niet schrijven');
    }

    try {
        return aequitas_paginate_entity(
            $company,
            AEQUITAS_PRICE_LINES_ENTITY,
            [
                '$select' => AEQUITAS_PRICE_LINES_SELECT,
                '$filter' => aequitas_price_lines_odata_date_filter($today),
            ],
            static function (array $row) use ($handle, $today): bool {
                $line = aequitas_slim_price_line($row);
                if ($line['Asset_No'] === '' || !aequitas_is_usable_price_line($line, $today)) {
                    return false;
                }

                aequitas_write_jsonl_row($handle, $line);
                return true;
            }
        );
    } finally {
        fclose($handle);
    }
}

function aequitas_build_price_index_from_file(string $pricesPath, string $indexPath): array
{
    $today = (new DateTimeImmutable('today'))->format('Y-m-d');
    $linesByItem = [];

    foreach (aequitas_read_jsonl($pricesPath) as $line) {
        if (!aequitas_is_usable_price_line($line, $today)) {
            continue;
        }

        $itemNo = aequitas_scalar_string($line['Asset_No'] ?? '');
        if ($itemNo === '') {
            continue;
        }

        $linesByItem[$itemNo][] = $line;
    }

    $index = [];
    foreach ($linesByItem as $itemNo => $matches) {
        usort($matches, static function (array $a, array $b): int {
            return aequitas_price_line_sort_key($b) <=> aequitas_price_line_sort_key($a);
        });

        $index[$itemNo] = [
            'purchase_price' => aequitas_scalar_float($matches[0]['DirectUnitCost'] ?? 0),
            'conflict' => count($matches) > 1,
        ];
    }

    ksort($index, SORT_NATURAL | SORT_FLAG_CASE);
    $json = json_encode($index, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new RuntimeException('Prijsindex encoderen mislukt');
    }

    file_put_contents($indexPath, $json, LOCK_EX);

    return array_keys($index);
}

function aequitas_load_price_index(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') {
        return [];
    }

    $index = json_decode($raw, true);

    return is_array($index) ? $index : [];
}

function aequitas_write_items_map(string $path, array $map): int
{
    $handle = fopen($path, 'wb');
    if ($handle === false) {
        throw new RuntimeException('Kan item-cache niet schrijven');
    }

    $kept = 0;
    try {
        ksort($map, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($map as $item) {
            if (!is_array($item)) {
                continue;
            }
            aequitas_write_jsonl_row($handle, $item);
            $kept++;
        }
    } finally {
        fclose($handle);
    }

    return $kept;
}

function aequitas_apply_item_to_map(array &$map, array $item, array $priceIndex): void
{
    $itemNo = aequitas_scalar_string($item['No'] ?? '');
    if ($itemNo === '' || !empty($item['Blocked'])) {
        unset($map[$itemNo]);
        return;
    }

    $priceInfo = $priceIndex[$itemNo] ?? null;
    if (!is_array($priceInfo) || !aequitas_item_should_keep($item, $priceInfo)) {
        unset($map[$itemNo]);
        return;
    }

    $map[$itemNo] = $item;
}

function aequitas_fetch_items_for_numbers_into_map(string $company, array $itemNos, array $priceIndex, array &$map): array
{
    $read = 0;
    $pages = 0;
    $batches = array_chunk(array_values($itemNos), AEQUITAS_ITEM_BATCH_SIZE);

    foreach ($batches as $batch) {
        $filters = [];
        foreach ($batch as $itemNo) {
            $safe = str_replace("'", "''", aequitas_scalar_string($itemNo));
            if ($safe !== '') {
                $filters[] = "No eq '" . $safe . "'";
            }
        }

        if ($filters === []) {
            continue;
        }

        $stats = aequitas_paginate_entity(
            $company,
            AEQUITAS_ITEMS_ENTITY,
            [
                '$select' => AEQUITAS_ITEMS_SELECT,
                '$filter' => implode(' or ', $filters),
            ],
            static function (array $row) use (&$map, $priceIndex): bool {
                $item = aequitas_slim_item($row);
                aequitas_apply_item_to_map($map, $item, $priceIndex);
                return $item['No'] !== '';
            }
        );

        $read += (int) ($stats['read'] ?? 0);
        $pages += (int) ($stats['pages'] ?? 0);
    }

    return [
        'read' => $read,
        'pages' => $pages,
    ];
}

/**
 * Nightly: volledige AppItemCard-sync voor artikelen op de prijsindex.
 */
function aequitas_sync_company_items(
    string $company,
    array $priceIndex,
    array $itemNos,
    string $targetPath
): array {
    $map = [];
    $stats = aequitas_fetch_items_for_numbers_into_map($company, $itemNos, $priceIndex, $map);
    $kept = aequitas_write_items_map($targetPath, $map);

    return [
        'kept' => $kept,
        'read' => (int) ($stats['read'] ?? 0),
        'pages' => (int) ($stats['pages'] ?? 0),
    ];
}

function aequitas_refresh_company(string $company): array
{
    $files = aequitas_company_cache_files($company);
    $tmpPrices = $files['prices'] . '.tmp';
    $tmpItems = $files['items'] . '.tmp';
    $tmpIndex = $files['price_index'] . '.tmp';
    $tmpMeta = $files['meta'] . '.tmp';

    try {
        $priceStats = aequitas_write_prices_file($company, $tmpPrices);
        $itemNos = aequitas_build_price_index_from_file($tmpPrices, $tmpIndex);
        $priceIndex = aequitas_load_price_index($tmpIndex);
        $itemStats = aequitas_sync_company_items($company, $priceIndex, $itemNos, $tmpItems);

        $meta = [
            'version' => AEQUITAS_CACHE_VERSION,
            'company' => $company,
            'cached_at' => time(),
            'item_count' => (int) ($itemStats['kept'] ?? 0),
            'price_line_count' => (int) ($priceStats['kept'] ?? 0),
            'price_line_read' => (int) ($priceStats['read'] ?? 0),
            'item_read' => (int) ($itemStats['read'] ?? 0),
            'item_pages' => (int) ($itemStats['pages'] ?? 0),
            'price_line_pages' => (int) ($priceStats['pages'] ?? 0),
            'unique_items' => count($itemNos),
        ];
        $metaJson = json_encode($meta, JSON_UNESCAPED_UNICODE);
        if ($metaJson === false) {
            throw new RuntimeException('Cache-meta encoderen mislukt');
        }
        if (file_put_contents($tmpMeta, $metaJson, LOCK_EX) === false) {
            throw new RuntimeException('Cache-meta schrijven mislukt');
        }

        aequitas_commit_cache_files([
            [$tmpPrices, $files['prices']],
            [$tmpIndex, $files['price_index']],
            [$tmpItems, $files['items']],
            [$tmpMeta, $files['meta']],
        ]);

        return $meta;
    } catch (Throwable $error) {
        @unlink($tmpPrices);
        @unlink($tmpItems);
        @unlink($tmpIndex);
        @unlink($tmpMeta);
        throw $error;
    }
}

function aequitas_company_name_from_slug(string $slug): string
{
    foreach (AEQUITAS_COMPANIES as $company) {
        if (aequitas_company_slug((string) $company) === $slug) {
            return (string) $company;
        }
    }

    return trim(str_replace('_', ' ', $slug));
}

function aequitas_read_company_meta(string $company): ?array
{
    $files = aequitas_company_cache_files($company);
    if (!is_file($files['meta'])) {
        return null;
    }

    $raw = @file_get_contents($files['meta']);
    if ($raw === false || $raw === '') {
        return null;
    }

    $meta = json_decode($raw, true);
    if (!is_array($meta) || (int) ($meta['version'] ?? 0) !== AEQUITAS_CACHE_VERSION) {
        return null;
    }

    return $meta;
}

function aequitas_read_company_cache(string $company): ?array
{
    $meta = aequitas_read_company_meta($company);
    if ($meta === null) {
        return null;
    }

    $files = aequitas_company_cache_files($company);
    if (!is_file($files['items']) || !is_file($files['prices'])) {
        return null;
    }

    return [
        '_meta' => $meta,
        'files' => $files,
    ];
}

function aequitas_cached_companies(): array
{
    $dir = aequitas_cache_base_dir();
    $entries = @scandir($dir);
    if (!is_array($entries)) {
        return [];
    }

    $companies = [];
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..' || !str_ends_with($entry, '.meta.json')) {
            continue;
        }

        $raw = @file_get_contents($dir . DIRECTORY_SEPARATOR . $entry);
        $meta = is_string($raw) ? json_decode($raw, true) : null;
        $name = trim((string) ($meta['company'] ?? ''));
        if ($name !== '' && (int) ($meta['version'] ?? 0) === AEQUITAS_CACHE_VERSION) {
            $companies[$name] = $name;
        }
    }

    $names = array_values($companies);
    natcasesort($names);

    return array_values($names);
}

function aequitas_make_table_row(array $item, array $matches): ?array
{
    $itemNo = aequitas_scalar_string($item['No'] ?? '');
    if ($itemNo === '' || $matches === []) {
        return null;
    }

    usort($matches, static function (array $a, array $b): int {
        return aequitas_price_line_sort_key($b) <=> aequitas_price_line_sort_key($a);
    });

    $selected = $matches[0];
    $lastDirectCost = aequitas_scalar_float($item['Last_Direct_Cost'] ?? 0);
    $purchasePrice = aequitas_scalar_float($selected['DirectUnitCost'] ?? 0);
    $conflict = count($matches) > 1;
    $mismatch = !aequitas_prices_equal($lastDirectCost, $purchasePrice);
    if (!$mismatch && !$conflict) {
        return null;
    }

    return [
        'item_no' => $itemNo,
        'description' => aequitas_scalar_string($item['Description'] ?? ''),
        'vendor_no' => aequitas_scalar_string($item['Vendor_No'] ?? ''),
        'vendor_name' => aequitas_scalar_string($item['Vendor_Name'] ?? ''),
        'last_direct_cost' => $lastDirectCost,
        'minimum_quantity' => aequitas_scalar_float($selected['Minimum_Quantity'] ?? 0),
        'base_unit' => aequitas_scalar_string($item['Base_Unit_of_Measure'] ?? ''),
        'unit' => aequitas_scalar_string($selected['Unit_of_Measure_Code'] ?? ''),
        'purchase_price' => $purchasePrice,
        'starting_date' => aequitas_parse_date($selected['Starting_Date'] ?? ''),
        'ending_date' => aequitas_parse_date($selected['Ending_Date'] ?? ''),
        'settlement_price' => round($lastDirectCost * AEQUITAS_SETTLEMENT_FACTOR, 2),
        'price_mismatch' => $mismatch,
        'conflict' => $conflict,
        'conflicts' => $conflict ? $matches : [],
    ];
}

function aequitas_build_table_rows_from_cache(string $company, ?string $today = null): array
{
    $files = aequitas_company_cache_files($company);
    $today = $today ?? (new DateTimeImmutable('today'))->format('Y-m-d');
    $linesByItem = [];

    foreach (aequitas_read_jsonl($files['prices']) as $line) {
        if (!aequitas_is_usable_price_line($line, $today)) {
            continue;
        }

        $itemNo = aequitas_scalar_string($line['Asset_No'] ?? '');
        if ($itemNo === '') {
            continue;
        }

        $linesByItem[$itemNo][] = $line;
    }

    $rows = [];
    foreach (aequitas_read_jsonl($files['items']) as $item) {
        $row = aequitas_make_table_row($item, $linesByItem[aequitas_scalar_string($item['No'] ?? '')] ?? []);
        if ($row !== null) {
            $rows[] = $row;
        }
    }

    usort($rows, static function (array $a, array $b): int {
        return strnatcasecmp((string) $a['item_no'], (string) $b['item_no']);
    });

    return $rows;
}

function aequitas_vendor_options(array $rows): array
{
    $options = [];
    foreach ($rows as $row) {
        $vendorNo = aequitas_scalar_string($row['vendor_no'] ?? '');
        if ($vendorNo === '' || isset($options[$vendorNo])) {
            continue;
        }

        $vendorName = aequitas_scalar_string($row['vendor_name'] ?? '');
        $options[$vendorNo] = $vendorName !== '' ? $vendorNo . ' — ' . $vendorName : $vendorNo;
    }

    uasort($options, static function (string $a, string $b): int {
        return strnatcasecmp($a, $b);
    });

    return $options;
}

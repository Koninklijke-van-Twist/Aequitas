<?php

/**
 * Includes/requires
 */
require_once __DIR__ . '/aequitas_config.php';

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
    if ($start !== '' && $start > $today) {
        return false;
    }

    if ($end !== '' && $end < $today) {
        return false;
    }

    return true;
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

function aequitas_require_bc(): void
{
    require_once __DIR__ . '/bc_data.php';
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

function aequitas_odata_get_page(string $url, array $auth, bool $withPrefer = true): array
{
    $headers = [
        'Accept: application/json',
        'Accept-Language: nl-NL,nl;q=0.9,en;q=0.8',
    ];
    if ($withPrefer) {
        $headers[] = 'Prefer: odata.maxpagesize=' . AEQUITAS_PAGE_SIZE;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Aequitas-ODataClient/1.0 (Windows; nl-NL)',
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT => 180,
        CURLOPT_HTTPHEADER => $headers,
    ]);

    if (($auth['mode'] ?? '') === 'basic') {
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, (string) ($auth['user'] ?? '') . ':' . (string) ($auth['pass'] ?? ''));
    } elseif (($auth['mode'] ?? '') === 'ntlm') {
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
        curl_setopt($ch, CURLOPT_USERPWD, (string) ($auth['user'] ?? '') . ':' . (string) ($auth['pass'] ?? ''));
    }

    $raw = curl_exec($ch);
    if ($raw === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('cURL error: ' . $error);
    }

    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code < 200 || $code >= 300) {
        if ($withPrefer && ($code === 400 || $code === 501)) {
            unset($raw);
            return aequitas_odata_get_page($url, $auth, false);
        }

        $snippet = function_exists('mb_substr') ? mb_substr((string) $raw, 0, 400) : substr((string) $raw, 0, 400);
        unset($raw);
        throw new RuntimeException('HTTP ' . $code . ' from OData: ' . $snippet);
    }

    $json = json_decode((string) $raw, true);
    unset($raw);

    if (!is_array($json) || !isset($json['value']) || !is_array($json['value'])) {
        throw new RuntimeException("OData response missing 'value' array");
    }

    return $json;
}

function aequitas_write_jsonl_row($handle, array $row): void
{
    $json = json_encode($row, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new RuntimeException('Cache JSONL encoderen mislukt');
    }

    fwrite($handle, $json . "\n");
}

function aequitas_paginate_entity(string $company, string $entitySet, array $query, callable $onRow): array
{
    aequitas_require_bc();
    global $baseUrl;

    $environment = auth_get_environment_for_company($company, 1);
    $auth = auth_get_auth_for_environment($environment);
    $pageSize = AEQUITAS_PAGE_SIZE;
    $query['$top'] = $pageSize;
    $kept = 0;
    $pages = 0;
    $read = 0;
    $previousSignature = '';
    $url = bc_company_entity_url($baseUrl, $environment, $company, $entitySet, $query);

    while ($url !== '') {
        $resp = aequitas_odata_get_page($url, $auth);
        $rows = $resp['value'];
        $rowCount = count($rows);
        $nextLink = $resp['@odata.nextLink'] ?? null;
        unset($resp);

        $first = is_array($rows[0] ?? null) ? aequitas_scalar_string(($rows[0]['No'] ?? '') ?: ($rows[0]['Asset_No'] ?? '')) : '';
        $lastRow = $rowCount > 0 ? $rows[$rowCount - 1] : null;
        $last = is_array($lastRow) ? aequitas_scalar_string(($lastRow['No'] ?? '') ?: ($lastRow['Asset_No'] ?? '')) : '';
        $signature = $rowCount . '|' . $first . '|' . $last;
        if ($pages > 0 && $signature !== '' && $signature === $previousSignature) {
            break;
        }
        $previousSignature = $signature;
        $pages++;
        $read += $rowCount;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            if ($onRow($row)) {
                $kept++;
            }
        }

        unset($rows, $row, $lastRow);
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }

        if (is_string($nextLink) && trim($nextLink) !== '') {
            $url = aequitas_resolve_next_url($url, $nextLink);
            continue;
        }

        if ($rowCount >= $pageSize) {
            $query['$skip'] = $read;
            $url = bc_company_entity_url($baseUrl, $environment, $company, $entitySet, $query);
            continue;
        }

        $url = '';
    }

    return [
        'kept' => $kept,
        'pages' => $pages,
        'read' => $read,
    ];
}

function aequitas_replace_cache_file(string $tmpPath, string $finalPath): void
{
    if (!is_file($tmpPath)) {
        throw new RuntimeException('Tijdelijk cachebestand ontbreekt: ' . $tmpPath);
    }

    if (is_file($finalPath) && !@unlink($finalPath) && is_file($finalPath)) {
        throw new RuntimeException('Oud cachebestand kon niet worden vervangen: ' . $finalPath);
    }

    if (!@rename($tmpPath, $finalPath)) {
        throw new RuntimeException('Cachebestand kon niet worden geplaatst: ' . $finalPath);
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

function aequitas_read_company_meta(string $company): ?array
{
    $files = aequitas_company_cache_files($company);
    if (!is_file($files['meta']) || !is_file($files['items']) || !is_file($files['prices'])) {
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

    return [
        '_meta' => $meta,
        'files' => aequitas_company_cache_files($company),
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

        $path = $dir . DIRECTORY_SEPARATOR . $entry;
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            continue;
        }

        $meta = json_decode($raw, true);
        $name = trim((string) ($meta['company'] ?? ''));
        if ($name === '' || (int) ($meta['version'] ?? 0) !== AEQUITAS_CACHE_VERSION) {
            continue;
        }

        $companies[$name] = $name;
    }

    $names = array_values($companies);
    natcasesort($names);

    return array_values($names);
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
        'price_mismatch' => !aequitas_prices_equal($lastDirectCost, $purchasePrice),
        'conflict' => $conflict,
        'conflicts' => $conflict ? $matches : [],
    ];
}

function aequitas_build_table_rows(array $items, array $priceLines, ?string $today = null): array
{
    $today = $today ?? (new DateTimeImmutable('today'))->format('Y-m-d');
    $linesByItem = [];

    foreach ($priceLines as $line) {
        if (!is_array($line) || !aequitas_is_usable_price_line($line, $today)) {
            continue;
        }

        $itemNo = aequitas_scalar_string($line['Asset_No'] ?? '');
        if ($itemNo === '') {
            continue;
        }

        $linesByItem[$itemNo][] = $line;
    }

    $rows = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

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

function aequitas_refresh_company(string $company): array
{
    $files = aequitas_company_cache_files($company);
    $today = (new DateTimeImmutable('today'))->format('Y-m-d');
    $tmpItems = $files['items'] . '.tmp';
    $tmpPrices = $files['prices'] . '.tmp';

    $itemHandle = fopen($tmpItems, 'wb');
    if ($itemHandle === false) {
        throw new RuntimeException('Kan item-cache niet schrijven');
    }

    try {
        $itemStats = aequitas_paginate_entity(
            $company,
            AEQUITAS_ITEMS_ENTITY,
            ['$select' => AEQUITAS_ITEMS_SELECT],
            static function (array $row) use ($itemHandle): bool {
                $item = aequitas_slim_item($row);
                if ($item['No'] === '' || $item['Blocked']) {
                    return false;
                }

                aequitas_write_jsonl_row($itemHandle, $item);
                return true;
            }
        );
    } catch (Throwable $error) {
        fclose($itemHandle);
        @unlink($tmpItems);
        throw $error;
    }
    fclose($itemHandle);

    $priceHandle = fopen($tmpPrices, 'wb');
    if ($priceHandle === false) {
        @unlink($tmpItems);
        throw new RuntimeException('Kan prijslijst-cache niet schrijven');
    }

    try {
        $priceStats = aequitas_paginate_entity(
            $company,
            AEQUITAS_PRICE_LINES_ENTITY,
            ['$select' => AEQUITAS_PRICE_LINES_SELECT],
            static function (array $row) use ($priceHandle, $today): bool {
                $line = aequitas_slim_price_line($row);
                if ($line['Asset_No'] === '' || !aequitas_is_usable_price_line($line, $today)) {
                    return false;
                }

                aequitas_write_jsonl_row($priceHandle, $line);
                return true;
            }
        );
    } catch (Throwable $error) {
        fclose($priceHandle);
        @unlink($tmpItems);
        @unlink($tmpPrices);
        throw $error;
    }

    fclose($priceHandle);

    $meta = [
        'version' => AEQUITAS_CACHE_VERSION,
        'company' => $company,
        'cached_at' => time(),
        'item_count' => (int) ($itemStats['kept'] ?? 0),
        'price_line_count' => (int) ($priceStats['kept'] ?? 0),
        'item_pages' => (int) ($itemStats['pages'] ?? 0),
        'price_line_pages' => (int) ($priceStats['pages'] ?? 0),
    ];

    try {
        aequitas_replace_cache_file($tmpItems, $files['items']);
        aequitas_replace_cache_file($tmpPrices, $files['prices']);
        aequitas_write_meta_file($files['meta'], $meta);
    } catch (Throwable $error) {
        @unlink($tmpItems);
        @unlink($tmpPrices);
        throw $error;
    }

    return $meta;
}

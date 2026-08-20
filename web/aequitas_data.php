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

function aequitas_company_cache_path(string $company): string
{
    return aequitas_cache_base_dir() . DIRECTORY_SEPARATOR . aequitas_company_slug($company) . '.json';
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

function aequitas_fetch_items(string $company): array
{
    aequitas_require_bc();
    $rows = bc_fetch_rows($company, AEQUITAS_ITEMS_ENTITY, [
        '$select' => AEQUITAS_ITEMS_SELECT,
    ], 1);

    $items = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $item = aequitas_slim_item($row);
        if ($item['No'] === '' || $item['Blocked']) {
            continue;
        }

        $items[] = $item;
    }

    return $items;
}

function aequitas_fetch_price_lines(string $company): array
{
    aequitas_require_bc();
    $rows = bc_fetch_rows($company, AEQUITAS_PRICE_LINES_ENTITY, [
        '$select' => AEQUITAS_PRICE_LINES_SELECT,
    ], 1);

    $lines = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $line = aequitas_slim_price_line($row);
        if ($line['Asset_No'] === '') {
            continue;
        }

        $lines[] = $line;
    }

    return $lines;
}

function aequitas_write_company_cache(string $company, array $items, array $priceLines): array
{
    $meta = [
        'version' => AEQUITAS_CACHE_VERSION,
        'company' => $company,
        'cached_at' => time(),
        'item_count' => count($items),
        'price_line_count' => count($priceLines),
    ];

    $payload = [
        '_meta' => $meta,
        'items' => $items,
        'price_lines' => $priceLines,
    ];

    $path = aequitas_company_cache_path($company);
    $tmp = $path . '.tmp';
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new RuntimeException('Cache JSON encoderen mislukt voor ' . $company);
    }

    file_put_contents($tmp, $json, LOCK_EX);
    rename($tmp, $path);

    return $meta;
}

function aequitas_read_company_cache(string $company): ?array
{
    $path = aequitas_company_cache_path($company);
    if (!is_file($path)) {
        return null;
    }

    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') {
        return null;
    }

    $payload = json_decode($raw, true);
    if (!is_array($payload) || !is_array($payload['items'] ?? null) || !is_array($payload['price_lines'] ?? null)) {
        return null;
    }

    $meta = is_array($payload['_meta'] ?? null) ? $payload['_meta'] : [];
    $version = (int) ($meta['version'] ?? 0);
    if ($version !== AEQUITAS_CACHE_VERSION) {
        return null;
    }

    return $payload;
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
        if ($entry === '.' || $entry === '..' || pathinfo($entry, PATHINFO_EXTENSION) !== 'json') {
            continue;
        }

        $path = $dir . DIRECTORY_SEPARATOR . $entry;
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            continue;
        }

        $payload = json_decode($raw, true);
        $name = trim((string) (($payload['_meta']['company'] ?? '')));
        if ($name === '') {
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

        $itemNo = aequitas_scalar_string($item['No'] ?? '');
        if ($itemNo === '') {
            continue;
        }

        $matches = $linesByItem[$itemNo] ?? [];
        if ($matches === []) {
            continue;
        }

        usort($matches, static function (array $a, array $b): int {
            return aequitas_price_line_sort_key($b) <=> aequitas_price_line_sort_key($a);
        });

        $selected = $matches[0];
        $lastDirectCost = aequitas_scalar_float($item['Last_Direct_Cost'] ?? 0);
        $purchasePrice = aequitas_scalar_float($selected['DirectUnitCost'] ?? 0);
        $conflict = count($matches) > 1;

        $rows[] = [
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
    $items = aequitas_fetch_items($company);
    $priceLines = aequitas_fetch_price_lines($company);

    return aequitas_write_company_cache($company, $items, $priceLines);
}

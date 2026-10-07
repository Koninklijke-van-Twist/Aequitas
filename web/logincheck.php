<?php

function is_trusted_requester(): bool
{
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    $server = $_SERVER['SERVER_ADDR'] ?? '';
    $trusted = ['127.0.0.1', '::1'];
    if ($remote === $server && $remote !== '') {
        return true;
    }
    if (in_array($remote, $trusted, true)) {
        return true;
    }
    return false;
}

/**
 * $allowedUsers uit auth.php is optioneel (zelfde gedrag als Kothar/Ktesios):
 * weglaten of [] laat elke geldige Entra-login toe; een lijst beperkt tot die adressen.
 * Een ongeldige waarde (geen lijst/string) beperkt de toegang tot niemand (fail-closed).
 */
$aequitasAllowedUsersRaw = $allowedUsers ?? [];
if (is_string($aequitasAllowedUsersRaw)) {
    $aequitasAllowedUsersRaw = [$aequitasAllowedUsersRaw];
} elseif (!is_array($aequitasAllowedUsersRaw)) {
    error_log('Aequitas: $allowedUsers in auth.php is geen lijst; toegang geweigerd.');
    $aequitasAllowedUsersRaw = [null];
}
$aequitasRestrictUsers = count($aequitasAllowedUsersRaw) > 0;
$aequitasAllowList = [];
foreach ($aequitasAllowedUsersRaw as $aequitasAllowedEmail) {
    $aequitasAllowedEmail = is_string($aequitasAllowedEmail) ? strtolower(trim($aequitasAllowedEmail)) : '';
    if ($aequitasAllowedEmail !== '') {
        $aequitasAllowList[] = $aequitasAllowedEmail;
    }
}

if (is_trusted_requester()) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }

    $currentEmail = strtolower(trim((string) ($_SESSION['user']['email'] ?? '')));
    $defaultAllowedUser = $aequitasAllowList[0] ?? '';
    if ($currentEmail === '' && $defaultAllowedUser !== '') {
        if (!is_array($_SESSION['user'] ?? null)) {
            $_SESSION['user'] = [];
        }

        $_SESSION['user']['email'] = $defaultAllowedUser;
    }
}

if (!is_trusted_requester()) {
    require __DIR__ . "/../login/lib.php";

    $aequitasSessionEmail = strtolower(trim((string) ($_SESSION['user']['email'] ?? '')));
    $aequitasIsAllowed = $aequitasSessionEmail !== ''
        && (!$aequitasRestrictUsers || in_array($aequitasSessionEmail, $aequitasAllowList, true));
    if (!$aequitasIsAllowed) {
        require __DIR__ . "/../login/403.php";
        die();
    }

    $analyticsEmail = strtolower(trim((string) ($_SESSION['user']['email'] ?? '')));
    $analyticsApiKey = trim((string) ($_SESSION['user']['api_key'] ?? ''));
    $analyticsOid = strtolower(trim((string) ($_SESSION['user']['oid'] ?? '')));
    if ($analyticsEmail !== '' && $analyticsApiKey !== '' && $analyticsOid !== '') {
        $analyticsScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $analyticsHost = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $analyticsBase = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'))), '/');
        $analyticsUrl = $analyticsScheme . '://' . $analyticsHost . $analyticsBase . '/analytics/analytics.php?' . http_build_query([
            'user_email' => $analyticsEmail,
            'api_key' => $analyticsApiKey,
            'oid' => $analyticsOid,
        ], '', '&', PHP_QUERY_RFC3986);

        if (function_exists('curl_init')) {
            $analyticsCurl = curl_init($analyticsUrl);
            curl_setopt_array($analyticsCurl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 1,
                CURLOPT_TIMEOUT => 1,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_HTTPHEADER => ['X-API-Key: ' . $analyticsApiKey],
            ]);
            curl_exec($analyticsCurl);
            curl_close($analyticsCurl);
        }
    }
}
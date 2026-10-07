<?php
/**
 * Controleert de $allowedUsers-poort in web/logincheck.php zonder echte login-app.
 * Run: php tests/logincheck_test.php
 */

$root = sys_get_temp_dir() . '/aequitas-logincheck-test-' . getmypid();
@mkdir($root . '/web', 0777, true);
@mkdir($root . '/login', 0777, true);
copy(dirname(__DIR__) . '/web/logincheck.php', $root . '/web/logincheck.php');
file_put_contents($root . '/login/lib.php', "<?php\n\$_SESSION['user'] = ['email' => (string) getenv('TEST_EMAIL')];\n");
file_put_contents($root . '/login/403.php', "<?php\necho 'DENIED';\n");

$failures = 0;

/**
 * @param string $authCode PHP-code die auth.php zou bevatten ('' = geen $allowedUsers)
 */
function run_case(string $root, string $label, string $authCode, string $email, string $expected): void
{
    global $failures;
    $script = $root . '/case.php';
    file_put_contents($script, "<?php\n"
        . "set_error_handler(static function (int \$no, string \$msg): bool { echo 'PHPERROR: ' . \$msg; exit(1); });\n"
        . "\$_SERVER['REMOTE_ADDR'] = '10.0.0.5';\n"
        . "\$_SERVER['SERVER_ADDR'] = '10.0.0.1';\n"
        . $authCode . "\n"
        . "require " . var_export($root . '/web/logincheck.php', true) . ";\n"
        . "echo 'ALLOWED';\n");
    $cmd = 'TEST_EMAIL=' . escapeshellarg($email) . ' ' . escapeshellarg(PHP_BINARY)
        . ' -d error_log=/dev/null ' . escapeshellarg($script) . ' 2>&1';
    $output = trim((string) shell_exec($cmd));
    if ($output === $expected) {
        echo "OK  {$label}\n";
        return;
    }
    $failures++;
    echo "FAIL {$label}: verwacht {$expected}, kreeg {$output}\n";
}

run_case($root, 'ontbrekende $allowedUsers laat elke login toe', '', 'iemand@kvt.nl', 'ALLOWED');
run_case($root, 'null $allowedUsers laat elke login toe', '$allowedUsers = null;', 'iemand@kvt.nl', 'ALLOWED');
run_case($root, 'lege $allowedUsers laat elke login toe', '$allowedUsers = [];', 'iemand@kvt.nl', 'ALLOWED');
run_case($root, 'adres in lijst (hoofdletterongevoelig)', '$allowedUsers = [" TFalken@KVT.nl "];', 'tfalken@kvt.nl', 'ALLOWED');
run_case($root, 'adres niet in lijst', '$allowedUsers = ["tfalken@kvt.nl"];', 'iemand@kvt.nl', 'DENIED');
run_case($root, 'losse string werkt als lijst van één', '$allowedUsers = "tfalken@kvt.nl";', 'tfalken@kvt.nl', 'ALLOWED');
run_case($root, 'lijst met alleen lege entry blijft dicht', '$allowedUsers = [""];', 'iemand@kvt.nl', 'DENIED');
run_case($root, 'ongeldig type blijft dicht', '$allowedUsers = 42;', 'iemand@kvt.nl', 'DENIED');
run_case($root, 'lege sessie-email wordt geweigerd', '', '', 'DENIED');

array_map('unlink', glob($root . '/*/*.php') ?: []);
@unlink($root . '/case.php');
@rmdir($root . '/web');
@rmdir($root . '/login');
@rmdir($root);

if ($failures > 0) {
    exit(1);
}
echo "all logincheck tests passed\n";

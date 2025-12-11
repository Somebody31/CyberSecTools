<?php
require_once __DIR__ . '/../security/SecurityUtils.php';
require_once __DIR__ . '/../db.php';

SecurityUtils::setSecurityHeaders();
header('Content-Type: application/json; charset=utf-8');

function isFunctionEnabled($fn) {
    if (!function_exists($fn)) return false;
    $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
    return !in_array($fn, $disabled, true);
}

function which($cmd) {
    $isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $check = $isWin ? ('where ' . $cmd . ' 2>&1') : ('command -v ' . $cmd . ' 2>&1');
    if (!isFunctionEnabled('shell_exec')) return null;
    $out = trim((string)@shell_exec($check));
    return $out !== '' ? $out : null;
}

$diagnostics = [
    'php' => [
        'version' => PHP_VERSION,
        'os' => PHP_OS,
        'disable_functions' => (string)ini_get('disable_functions'),
        'exec_enabled' => isFunctionEnabled('exec'),
        'shell_exec_enabled' => isFunctionEnabled('shell_exec'),
        'allow_url_fopen' => (bool)ini_get('allow_url_fopen'),
        'default_socket_timeout' => (int)ini_get('default_socket_timeout')
    ],
    'commands' => [
        'ping' => which('ping'),
        'traceroute' => which('traceroute'),
        'tracert' => which('tracert'),
        'dig' => which('dig'),
        'nslookup' => which('nslookup'),
        'whois' => which('whois')
    ],
    'network' => [
        'dns_google' => @dns_get_record('google.com', DNS_A) ? 'ok' : 'fail',
        'tcp_443_google' => (function(){ $s=@fsockopen('google.com',443,$e,$es,3.0); if($s){fclose($s); return 'ok';} return 'fail'; })(),
        'tcp_53_8888' => (function(){ $s=@fsockopen('8.8.8.8',53,$e,$es,3.0); if($s){fclose($s); return 'ok';} return 'fail'; })()
    ],
    'database' => [
        'connected' => $mysqli && !$mysqli->connect_errno ? 'ok' : 'fail'
    ]
];

echo json_encode(['status' => 'ok', 'diagnostics' => $diagnostics], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>



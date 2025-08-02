<?php
echo "=== DEBUGGING DNSBL DETECTION ===\n\n";

$test_ip = '127.0.0.2';
$reversed_ip = '2.0.0.127';

echo "Testing IP: $test_ip (reversed: $reversed_ip)\n\n";

$dnsbls = [
    'zen.spamhaus.org',
    'sbl.spamhaus.org',
    'xbl.spamhaus.org',
    'pbl.spamhaus.org',
    'dbl.spamhaus.org',
    'bl.spamcop.net',
    'cbl.abuseat.org'
];

foreach ($dnsbls as $dnsbl) {
    $lookup = $reversed_ip . '.' . $dnsbl;
    echo "Testing: $lookup\n";
    
    $result = gethostbyname($lookup);
    $listed = ($result !== $lookup);
    
    echo "  gethostbyname result: $result\n";
    echo "  Listed: " . ($listed ? "YES" : "NO") . "\n";
    
    if ($listed) {
        echo "  🚨 THIS SHOULD BE LISTED!\n";
    }
    echo "\n";
}

echo "=== TESTING WITH CURL ===\n\n";

foreach ($dnsbls as $dnsbl) {
    $lookup = $reversed_ip . '.' . $dnsbl;
    echo "Testing: $lookup\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "http://" . $lookup,
        CURLOPT_TIMEOUT => 2,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_NOBODY => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $listed = ($httpCode == 200 || $httpCode == 127) && empty($error);
    
    echo "  HTTP Code: $httpCode\n";
    echo "  Error: $error\n";
    echo "  Listed: " . ($listed ? "YES" : "NO") . "\n";
    
    if ($listed) {
        echo "  🚨 THIS SHOULD BE LISTED!\n";
    }
    echo "\n";
}

unlink('debug_dnsbl.php');
?> 
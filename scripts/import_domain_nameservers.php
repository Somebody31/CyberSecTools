<?php
// Import nameservers for a list of domains into domain_nameservers
// Usage examples:
//  php scripts/import_domain_nameservers.php --source=file:/path/to/domains.txt --limit=500
//  php scripts/import_domain_nameservers.php --source=table:domains_mx --limit=1000

require_once __DIR__ . '/../db.php';

@set_time_limit(0);
@ini_set('memory_limit', '512M');

function parseArgs($argv) {
    $args = ['source' => null, 'limit' => 1000];
    foreach ($argv as $arg) {
        if (strpos($arg, '--source=') === 0) { $args['source'] = substr($arg, 9); }
        if (strpos($arg, '--limit=') === 0) { $args['limit'] = (int)substr($arg, 8); }
    }
    return $args;
}

function ensureTable($mysqli) {
    $sql = "CREATE TABLE IF NOT EXISTS domain_nameservers (
        domain_name VARCHAR(255) NOT NULL,
        nameserver  VARCHAR(255) NOT NULL,
        registrar   VARCHAR(128) NULL,
        creation_date DATE NULL,
        expiration_date DATE NULL,
        PRIMARY KEY (domain_name, nameserver),
        KEY idx_nameserver (nameserver),
        KEY idx_domain (domain_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (!$mysqli->query($sql)) {
        throw new Exception('Failed to create domain_nameservers: ' . $mysqli->error);
    }
}

function loadSeedDomains($mysqli, $source, $limit) {
    $domains = [];
    if (!$source) { $source = 'table:domains_mx'; }
    if (strpos($source, 'table:') === 0) {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', substr($source, 6));
        $col = 'domain';
        if ($table === 'domain_ip_history') { $col = 'domain'; }
        elseif ($table === 'domain_nameservers') { $col = 'domain_name'; }
        elseif ($table === 'whois_records') { $col = 'domain_name'; }
        $res = $mysqli->query("SELECT DISTINCT `{$col}` AS domain FROM `{$table}` ORDER BY domain LIMIT " . (int)$limit);
        if ($res) { while ($row = $res->fetch_assoc()) { $domains[] = $row['domain']; } $res->close(); }
    } elseif (strpos($source, 'file:') === 0) {
        $path = substr($source, 5);
        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $d) { $d = trim($d); if ($d) { $domains[] = $d; } if (count($domains) >= $limit) break; }
        }
    }
    return array_values(array_unique(array_filter($domains)));
}

function resolveNs($domain) {
    $records = @dns_get_record(rtrim($domain, '.') . '.', DNS_NS);
    $list = [];
    if ($records) { foreach ($records as $r) { if (!empty($r['target'])) { $list[] = strtolower(rtrim($r['target'], '.')); } } }
    return array_values(array_unique($list));
}

function upsertNs($mysqli, $domain, $nsList) {
    if (empty($nsList)) return 0;
    $stmt = $mysqli->prepare("INSERT IGNORE INTO domain_nameservers (domain_name, nameserver) VALUES (?, ?)");
    if (!$stmt) return 0;
    $count = 0;
    foreach ($nsList as $ns) {
        $d = rtrim($domain, '.');
        $n = rtrim($ns, '.');
        $stmt->bind_param('ss', $d, $n);
        if (@$stmt->execute()) { $count++; }
    }
    $stmt->close();
    return $count;
}

try {
    if (!$mysqli) { throw new Exception('No database connection.'); }
    ensureTable($mysqli);
    $args = parseArgs($_SERVER['argv'] ?? []);
    $domains = loadSeedDomains($mysqli, $args['source'], $args['limit']);
    $totalInserted = 0; $processed = 0;
    foreach ($domains as $domain) {
        $processed++;
        $ns = resolveNs($domain);
        $totalInserted += upsertNs($mysqli, $domain, $ns);
        if (($processed % 50) === 0) { error_log("Processed {$processed} domains, inserted {$totalInserted} NS rows..."); }
        usleep(80000);
    }
    echo "Completed. Domains processed: {$processed}. NS rows inserted: {$totalInserted}\n";
} catch (Exception $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage() . "\n";
}



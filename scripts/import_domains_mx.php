<?php
// Build domains_mx from a seed list of domains
// Usage:
//  php scripts/import_domains_mx.php --source=table:domain_ip_history --limit=500
//  php scripts/import_domains_mx.php --source=file:/path/to/domains.txt

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
    $sql = "CREATE TABLE IF NOT EXISTS domains_mx (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        domain VARCHAR(255) NOT NULL,
        mx_record VARCHAR(255) NOT NULL,
        PRIMARY KEY (id),
        KEY idx_domain (domain),
        KEY idx_mx (mx_record)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (!$mysqli->query($sql)) {
        throw new Exception('Failed to create domains_mx: ' . $mysqli->error);
    }
}

function loadSeedDomains($mysqli, $source, $limit) {
    $domains = [];
    if (!$source) {
        $source = 'table:domain_ip_history';
    }
    if (strpos($source, 'table:') === 0) {
        $table = substr($source, 6);
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $res = $mysqli->query("SELECT DISTINCT domain FROM `{$table}` ORDER BY domain LIMIT " . (int)$limit);
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

function resolveMx($domain) {
    $records = @dns_get_record(rtrim($domain, '.') . '.', DNS_MX);
    $mx = [];
    if ($records) { foreach ($records as $r) { if (!empty($r['target'])) { $mx[] = strtolower(rtrim($r['target'], '.')); } } }
    return array_values(array_unique($mx));
}

function upsertMx($mysqli, $domain, $mxList) {
    if (empty($mxList)) return 0;
    $stmt = $mysqli->prepare("INSERT INTO domains_mx (domain, mx_record) VALUES (?, ?)");
    if (!$stmt) return 0;
    $count = 0;
    foreach ($mxList as $mx) {
        $d = rtrim($domain, '.');
        $m = rtrim($mx, '.');
        $stmt->bind_param('ss', $d, $m);
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
        $mx = resolveMx($domain);
        $totalInserted += upsertMx($mysqli, $domain, $mx);
        if (($processed % 50) === 0) { error_log("Processed {$processed} domains, inserted {$totalInserted} rows into domains_mx..."); }
        usleep(80000); // be polite
    }
    echo "Completed. Domains processed: {$processed}. Rows inserted: {$totalInserted}\n";
} catch (Exception $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage() . "\n";
}



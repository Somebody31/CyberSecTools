<?php
// Populate whois_records for a list of domains using internal TCP WHOIS (no external APIs)
// Usage:
//  php scripts/import_whois_records.php --source=table:domains_mx --limit=500
//  php scripts/import_whois_records.php --source=file:/path/to/domains.txt

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../models/WhoisLookupModel.php';

@set_time_limit(0);
@ini_set('memory_limit', '512M');

function parseArgs($argv) {
    $args = ['source' => null, 'limit' => 500];
    foreach ($argv as $arg) {
        if (strpos($arg, '--source=') === 0) { $args['source'] = substr($arg, 9); }
        if (strpos($arg, '--limit=') === 0) { $args['limit'] = (int)substr($arg, 8); }
    }
    return $args;
}

function ensureTable($mysqli) {
    $sql = "CREATE TABLE IF NOT EXISTS whois_records (
        domain_name VARCHAR(255) NOT NULL PRIMARY KEY,
        registrar   VARCHAR(128) NULL,
        registrant  VARCHAR(255) NULL,
        organization VARCHAR(255) NULL,
        admin_email VARCHAR(255) NULL,
        creation_date DATE NULL,
        expiration_date DATE NULL,
        KEY idx_registrar (registrar)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (!$mysqli->query($sql)) {
        throw new Exception('Failed to create whois_records: ' . $mysqli->error);
    }
}

function loadSeedDomains($mysqli, $source, $limit) {
    $domains = [];
    if (!$source) { $source = 'table:domains_mx'; }
    if (strpos($source, 'table:') === 0) {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', substr($source, 6));
        $col = 'domain';
        if (in_array($table, ['whois_records','domain_nameservers'])) { $col = 'domain_name'; }
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

function upsertWhois($mysqli, $domain, $details) {
    if (!$details || !is_array($details)) return false;
    $registrar = $details['Registrar'] ?? null;
    $registrant = $details['Registrant'] ?? null;
    $org = $details['Organization'] ?? null;
    $adminEmail = $details['Admin Email'] ?? null;
    $creation = !empty($details['Creation Date']) ? substr($details['Creation Date'], 0, 10) : null;
    $expiry = !empty($details['Expiration Date']) ? substr($details['Expiration Date'], 0, 10) : null;

    $stmt = $mysqli->prepare(
        "INSERT INTO whois_records (domain_name, registrar, registrant, organization, admin_email, creation_date, expiration_date)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE registrar=VALUES(registrar), registrant=VALUES(registrant), organization=VALUES(organization), admin_email=VALUES(admin_email), creation_date=VALUES(creation_date), expiration_date=VALUES(expiration_date)"
    );
    if (!$stmt) return false;
    $stmt->bind_param('sssssss', $domain, $registrar, $registrant, $org, $adminEmail, $creation, $expiry);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

try {
    if (!$mysqli) { throw new Exception('No database connection.'); }
    ensureTable($mysqli);
    $args = parseArgs($_SERVER['argv'] ?? []);
    $domains = loadSeedDomains($mysqli, $args['source'], $args['limit']);
    $whoisModel = new WhoisLookupModel($mysqli);
    $processed = 0; $updated = 0;
    foreach ($domains as $domain) {
        $processed++;
        if (!$mysqli || $mysqli->connect_errno || !$mysqli->ping()) {
            // Reconnect if server went away
            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            if ($mysqli && !$mysqli->connect_errno) {
                $mysqli->set_charset('utf8mb4');
            } else {
                error_log('Reconnection failed: ' . ($mysqli ? $mysqli->connect_error : 'unknown'));
                usleep(200000);
                continue;
            }
            // Rebind model with fresh connection
            $whoisModel = new WhoisLookupModel($mysqli);
        }
        $res = $whoisModel->lookup($domain);
        if (!isset($res['error']) && isset($res['details'])) {
            if (upsertWhois($mysqli, $domain, $res['details'])) { $updated++; }
        }
        if (($processed % 25) === 0) { error_log("Processed {$processed} domains, updated {$updated} WHOIS rows..."); }
        usleep(120000);
    }
    echo "Completed. Domains processed: {$processed}. WHOIS rows upserted: {$updated}\n";
} catch (Exception $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage() . "\n";
}



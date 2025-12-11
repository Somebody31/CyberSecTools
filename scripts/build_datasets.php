<?php
// Minimal CLI/script to build domain_nameservers and whois_records from your existing data
// Usage (CLI): php scripts/build_datasets.php
// It can also be invoked via web for one-off run (ensure it's protected if public).

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../models/WhoisLookupModel.php';

@set_time_limit(0);
@ini_set('memory_limit', '512M');

function ensureTables($mysqli) {
    $sql1 = "CREATE TABLE IF NOT EXISTS domain_nameservers (
        domain_name VARCHAR(255) NOT NULL,
        nameserver  VARCHAR(255) NOT NULL,
        registrar   VARCHAR(128) NULL,
        creation_date DATE NULL,
        expiration_date DATE NULL,
        PRIMARY KEY (domain_name, nameserver),
        KEY idx_nameserver (nameserver),
        KEY idx_domain (domain_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $sql2 = "CREATE TABLE IF NOT EXISTS whois_records (
        domain_name VARCHAR(255) NOT NULL PRIMARY KEY,
        registrar   VARCHAR(128) NULL,
        registrant  VARCHAR(255) NULL,
        organization VARCHAR(255) NULL,
        admin_email VARCHAR(255) NULL,
        creation_date DATE NULL,
        expiration_date DATE NULL,
        KEY idx_registrar (registrar)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $sql3 = "CREATE TABLE IF NOT EXISTS domain_ip_history (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        domain VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        location VARCHAR(128) NULL,
        owner VARCHAR(255) NULL,
        network VARCHAR(128) NULL,
        as_number VARCHAR(64) NULL,
        first_seen DATE NOT NULL,
        last_seen DATE NOT NULL,
        PRIMARY KEY (id),
        KEY idx_domain (domain),
        KEY idx_ip (ip_address)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $sql4 = "CREATE TABLE IF NOT EXISTS domains_mx (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        domain VARCHAR(255) NOT NULL,
        mx_record VARCHAR(255) NOT NULL,
        PRIMARY KEY (id),
        KEY idx_domain (domain),
        KEY idx_mx (mx_record)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $sql5 = "CREATE TABLE IF NOT EXISTS lookup_logs (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        tool VARCHAR(100) NOT NULL,
        input VARCHAR(1000) NOT NULL,
        error_message VARCHAR(500) NULL,
        ip_address VARCHAR(45) NULL,
        user_agent VARCHAR(255) NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_tool (tool),
        KEY idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $sql6 = "CREATE TABLE IF NOT EXISTS ip_blocks (
        ip_address VARCHAR(45) NOT NULL PRIMARY KEY,
        reason VARCHAR(255) NULL,
        blocked_until DATETIME NULL,
        created_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!$mysqli->query($sql1)) {
        throw new Exception('Failed to create domain_nameservers: ' . $mysqli->error);
    }
    if (!$mysqli->query($sql2)) {
        throw new Exception('Failed to create whois_records: ' . $mysqli->error);
    }
    if (!$mysqli->query($sql3)) {
        throw new Exception('Failed to create domain_ip_history: ' . $mysqli->error);
    }
    if (!$mysqli->query($sql4)) {
        throw new Exception('Failed to create domains_mx: ' . $mysqli->error);
    }
    if (!$mysqli->query($sql5)) {
        throw new Exception('Failed to create lookup_logs: ' . $mysqli->error);
    }
    if (!$mysqli->query($sql6)) {
        throw new Exception('Failed to create ip_blocks: ' . $mysqli->error);
    }
}

function collectSeedDomains($mysqli) {
    $set = [];
    $res = $mysqli->query("SELECT DISTINCT domain FROM domains_mx");
    if ($res) {
        while ($row = $res->fetch_assoc()) { $set[$row['domain']] = true; }
        $res->close();
    }
    $res = $mysqli->query("SELECT DISTINCT domain FROM domain_ip_history");
    if ($res) {
        while ($row = $res->fetch_assoc()) { $set[$row['domain']] = true; }
        $res->close();
    }
    return array_keys($set);
}

function upsertNameservers($mysqli, $domain, $nsList) {
    if (empty($nsList)) return;
    $stmt = $mysqli->prepare("INSERT IGNORE INTO domain_nameservers (domain_name, nameserver) VALUES (?, ?)");
    if (!$stmt) return;
    foreach ($nsList as $ns) {
        $ns = rtrim($ns, '.');
        $stmt->bind_param('ss', $domain, $ns);
        $stmt->execute();
    }
    $stmt->close();
}

function upsertWhois($mysqli, $domain, $details) {
    if (!$details || !is_array($details)) return;
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
    if (!$stmt) return;
    $stmt->bind_param('sssssss', $domain, $registrar, $registrant, $org, $adminEmail, $creation, $expiry);
    $stmt->execute();
    $stmt->close();
}

try {
    if (!$mysqli) {
        throw new Exception('No database connection.');
    }

    ensureTables($mysqli);
    $domains = collectSeedDomains($mysqli);
    $whoisModel = new WhoisLookupModel($mysqli);

    $processed = 0;
    foreach ($domains as $domain) {
        $processed++;
        // NS
        $nsRecords = @dns_get_record(rtrim($domain, '.') . '.', DNS_NS);
        $nameservers = [];
        if ($nsRecords) {
            foreach ($nsRecords as $r) {
                if (!empty($r['target'])) $nameservers[] = $r['target'];
            }
        }
        upsertNameservers($mysqli, $domain, $nameservers);

        // WHOIS
        $whois = $whoisModel->lookup($domain);
        if (!isset($whois['error']) && isset($whois['details'])) {
            upsertWhois($mysqli, $domain, $whois['details']);
        }

        if ($processed % 50 === 0) {
            error_log("Processed {$processed} domains...");
        }

        usleep(120000); // 120ms per domain to be polite
    }

    echo "Completed. Total domains processed: {$processed}\n";
} catch (Exception $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage() . "\n";
}




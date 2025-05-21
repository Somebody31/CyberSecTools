<?php
// backend.php - Contains backend logic for Reverse MX Lookup Tool

function read_domains_mx($csv_file) {
    $domains = [];
    ini_set('memory_limit', '-1'); // Remove memory limit for large files
    if (($handle = fopen($csv_file, "r")) !== FALSE) {
        $header = fgetcsv($handle); // skip header
        while (($data = fgetcsv($handle)) !== FALSE) {
            $domain = trim($data[0]);
            $mxs = isset($data[1]) ? explode(';', $data[1]) : [];
            $mxs = array_filter(array_map('trim', $mxs));
            if ($domain !== '') {
                $domains[] = [
                    'domain' => $domain,
                    'mxs' => $mxs
                ];
            }
        }
        fclose($handle);
    }
    return $domains;
}

function get_mx_hosts($domain) {
    $mxhosts = [];
    if (getmxrr($domain, $hosts)) {
        foreach ($hosts as $host) {
            $mxhosts[] = strtolower(trim($host, '.'));
        }
    }
    return $mxhosts;
}

function filter_domains_by_mx($domains, $mx_hosts) {
    $filtered = [];
    foreach ($domains as $row) {
        if (count(array_intersect($mx_hosts, $row['mxs'])) > 0) {
            $filtered[] = $row['domain'];
        }
    }
    return $filtered;
}

function get_mx_and_filtered_domains($query, $domains) {
    $mx_domain = '';
    $mx_hosts = [];
    $error = '';
    $filtered = [];
    $show_results = false;
    if ($query !== '') {
        $mx_domain = htmlspecialchars($query);
        $mx_hosts = get_mx_hosts($query);
        // If no MX records, try parent domain
        if (empty($mx_hosts) && strpos($query, '.') !== false) {
            $parts = explode('.', $query);
            if (count($parts) > 2) {
                array_shift($parts); // remove leftmost part
                $parent_domain = implode('.', $parts);
                $mx_hosts = get_mx_hosts($parent_domain);
                if (!empty($mx_hosts)) {
                    $mx_domain .= ' (using MX of ' . htmlspecialchars($parent_domain) . ')';
                }
            }
        }
        if (empty($mx_hosts)) {
            $error = 'No MX records found for this domain.';
        } else {
            $filtered = filter_domains_by_mx($domains, $mx_hosts);
            $show_results = true;
        }
    }
    return [
        'mx_domain' => $mx_domain,
        'mx_hosts' => $mx_hosts,
        'error' => $error,
        'filtered' => $filtered,
        'show_results' => $show_results
    ];
}

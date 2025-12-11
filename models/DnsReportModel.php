<?php
class DnsReportModel {
    private $mysqli;

    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }

    public function getReport($domain) {
        if (empty($domain) || !filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return ['error' => 'A valid domain name was not provided.'];
        }

        $report = [
            'parent_tests' => [],
            'local_tests' => [],
            'soa_tests' => [],
            'mx_tests' => [],
            'www_tests' => [],
        ];

        function is_public_ip($ip) {
            return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        function get_authoritative_ns_domain($domain) {
            $domain_parts = explode('.', $domain);
            while (count($domain_parts) > 1) {
                $current_domain = implode('.', $domain_parts);
                if (@dns_get_record($current_domain, DNS_NS)) {
                    return $current_domain;
                }
                array_shift($domain_parts);
            }
            return false;
        }

        $auth_domain = get_authoritative_ns_domain($domain);
        if (!$auth_domain) {
            $auth_domain = $domain;
        }

        $parent_ns_records = @dns_get_record($auth_domain, DNS_NS);
        if ($parent_ns_records) {
            $info = [];
            foreach ($parent_ns_records as $record) {
                $ip = gethostbyname($record['target']);
                $info[] = "{$record['target']}. [{$ip}] [TTL={$record['ttl']}]";
            }
            $report['parent_tests'][] = ['status' => 'INFO', 'case' => 'NS records listed at parent servers', 'info' => implode("\n", $info)];
            $report['parent_tests'][] = ['status' => 'OK', 'case' => 'Domain listed at parent servers', 'info' => 'Good! The parent servers have information about your domain.'];
        } else {
            $report['parent_tests'][] = ['status' => 'FAIL', 'case' => 'NS records listed at parent servers', 'info' => 'Could not retrieve NS records for the domain.'];
            return $report;
        }

        $local_ns_records = $parent_ns_records;
        $info = [];
        $local_ips = [];
        foreach ($local_ns_records as $record) {
            $ip = gethostbyname($record['target']);
            $local_ips[] = $ip;
            $info[] = "{$record['target']}. [{$ip}] [TTL={$record['ttl']}]";
        }
        $report['local_tests'][] = ['status' => 'INFO', 'case' => 'NS records at your local servers', 'info' => implode("\n", $info)];

        if (count($local_ns_records) >= 2) {
            $report['local_tests'][] = ['status' => 'OK', 'case' => 'Number of nameservers', 'info' => 'Good! You have at least 2 nameservers.'];
        } else {
            $report['local_tests'][] = ['status' => 'WARNING', 'case' => 'Number of nameservers', 'info' => 'Warning! You should have at least 2 nameservers.'];
        }

        $subnets = [];
        foreach ($local_ips as $ip) { $subnets[] = substr($ip, 0, strrpos($ip, '.')); }
        if (count(array_unique($subnets)) > 1 || count($subnets) <= 1) {
            $report['local_tests'][] = ['status' => 'OK', 'case' => 'Nameservers are on different IP subnets', 'info' => 'Good! All your nameservers are in separate class C (/24) subnets.'];
        } else {
            $report['local_tests'][] = ['status' => 'WARNING', 'case' => 'Nameservers are on different IP subnets', 'info' => 'Warning! Some or all of your nameservers are in the same class C (/24) subnet.'];
        }

        $all_public = true;
        foreach ($local_ips as $ip) { if (!is_public_ip($ip)) { $all_public = false; break; } }
        if ($all_public) {
            $report['local_tests'][] = ['status' => 'OK', 'case' => 'Nameservers have public IPs', 'info' => 'Good! All your NS records have public IP addresses.'];
        } else {
            $report['local_tests'][] = ['status' => 'FAIL', 'case' => 'Nameservers have public IPs', 'info' => 'Error! At least one of your nameservers has a private IP address.'];
        }

        $soa_record = @dns_get_record($domain, DNS_SOA);
        if ($soa_record) {
            $soa = $soa_record[0];
            $soa_info = [
                'Primary NS' => $soa['mname'],
                'Admin Email' => $soa['rname'],
                'Serial Number' => $soa['serial'],
                'Refresh' => $soa['refresh'] . ' seconds',
                'Retry' => $soa['retry'] . ' seconds',
                'Expire' => $soa['expire'] . ' seconds',
                'Minimum TTL' => $soa['minimum-ttl'] . ' seconds'
            ];
            $report['soa_tests'][] = ['status' => 'INFO', 'case' => 'SOA record details', 'info' => implode("\n", array_map(function($k, $v) { return "{$k}: {$v}"; }, array_keys($soa_info), $soa_info))];
            
            $primary_ns_ip = gethostbyname($soa['mname']);
            if ($primary_ns_ip && $primary_ns_ip !== $soa['mname']) {
                $report['soa_tests'][] = ['status' => 'OK', 'case' => 'Primary nameserver is reachable', 'info' => "Primary NS {$soa['mname']} resolves to {$primary_ns_ip}"];
            } else {
                $report['soa_tests'][] = ['status' => 'FAIL', 'case' => 'Primary nameserver is reachable', 'info' => "Primary NS {$soa['mname']} could not be resolved"];
            }
        } else {
            $report['soa_tests'][] = ['status' => 'FAIL', 'case' => 'SOA record exists', 'info' => 'No SOA record found for the domain.'];
        }

        $mx_records = @dns_get_record($domain, DNS_MX);
        if ($mx_records) {
            $info = [];
            foreach ($mx_records as $record) {
                $ip = gethostbyname($record['target']);
                $info[] = "Priority: {$record['pri']}, Target: {$record['target']} [{$ip}] [TTL={$record['ttl']}]";
            }
            $report['mx_tests'][] = ['status' => 'INFO', 'case' => 'MX records', 'info' => implode("\n", $info)];
            
            if (count($mx_records) >= 2) {
                $report['mx_tests'][] = ['status' => 'OK', 'case' => 'Multiple MX records', 'info' => 'Good! You have multiple MX records for redundancy.'];
            } else {
                $report['mx_tests'][] = ['status' => 'WARNING', 'case' => 'Multiple MX records', 'info' => 'Warning! Consider adding a secondary MX record for redundancy.'];
            }
            
            $all_mx_public = true;
            foreach ($mx_records as $record) {
                $ip = gethostbyname($record['target']);
                if ($ip && !is_public_ip($ip)) {
                    $all_mx_public = false;
                    break;
                }
            }
            if ($all_mx_public) {
                $report['mx_tests'][] = ['status' => 'OK', 'case' => 'MX records have public IPs', 'info' => 'Good! All your MX records have public IP addresses.'];
            } else {
                $report['mx_tests'][] = ['status' => 'FAIL', 'case' => 'MX records have public IPs', 'info' => 'Error! At least one of your MX records has a private IP address.'];
            }
        } else {
            $report['mx_tests'][] = ['status' => 'FAIL', 'case' => 'MX records exist', 'info' => 'No MX records found for the domain.'];
        }

        $www_a_records = @dns_get_record("www.{$domain}", DNS_A);
        if ($www_a_records) {
            $info = [];
            foreach ($www_a_records as $record) {
                $info[] = "{$record['ip']} [TTL={$record['ttl']}]";
            }
            $report['www_tests'][] = ['status' => 'INFO', 'case' => 'WWW A records', 'info' => implode("\n", $info)];
            
            $all_www_public = true;
            foreach ($www_a_records as $record) {
                if (!is_public_ip($record['ip'])) {
                    $all_www_public = false;
                    break;
                }
            }
            if ($all_www_public) {
                $report['www_tests'][] = ['status' => 'OK', 'case' => 'WWW records have public IPs', 'info' => 'Good! All your WWW A records have public IP addresses.'];
            } else {
                $report['www_tests'][] = ['status' => 'FAIL', 'case' => 'WWW records have public IPs', 'info' => 'Error! At least one of your WWW A records has a private IP address.'];
            }
        } else {
            $report['www_tests'][] = ['status' => 'WARNING', 'case' => 'WWW A records exist', 'info' => 'No WWW A records found. This is normal if you don\'t use www subdomain.'];
        }

        return $report;
    }
}

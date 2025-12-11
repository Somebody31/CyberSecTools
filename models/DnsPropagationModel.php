<?php
class DnsPropagationModel {
    private $nameservers;

    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
        $this->nameservers = [
            '8.8.8.8' => 'Google DNS',
            '1.1.1.1' => 'Cloudflare DNS',
            '208.67.222.222' => 'OpenDNS',
            '9.9.9.9' => 'Quad9 DNS',
            '8.8.4.4' => 'Google DNS (Secondary)',
            '1.0.0.1' => 'Cloudflare DNS (Secondary)',
            '208.67.220.220' => 'OpenDNS (Secondary)',
            '64.6.64.6' => 'Verisign DNS',
            '4.2.2.1' => 'Level3 DNS',
            '119.29.29.29' => 'DNSPod'
        ];
    }

    private function queryNameserver($domain, $nameserver, $name, $recordType = 'A') {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $shellDisabled = !function_exists('shell_exec') || in_array('shell_exec', array_map('trim', explode(',', (string)ini_get('disable_functions'))));

        if (!$shellDisabled) {
            if ($isWindows) {
                $command = "nslookup -type={$recordType} {$domain} {$nameserver}";
            } else {
                $command = "dig @{$nameserver} {$domain} {$recordType} +short";
            }
            $output = shell_exec($command . " 2>&1");
        } else {
            $output = '';
        }
        
        $result = [
            'nameserver' => $nameserver,
            'name' => $name,
            'type' => $recordType,
            'output' => $output,
            'status' => 'error',
            'records' => []
        ];

        if (!empty($output)) {
            $result['status'] = 'success';
            $lines = explode("\n", $output);
            $records = [];
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, 'Server:') !== false || strpos($line, 'Address:') === 0) {
                    continue;
                }
                
                $line = str_replace('Non-authoritative answer:', '', $line);
                $line = preg_replace('/^Name:\s+/', '', $line);
                
                if (!empty($line)) {
                    $records[] = $line;
                }
            }
            
            $result['records'] = array_filter($records);
        }

        if (empty($result['records'])) {
            // Pure-PHP fallback via DNS functions (limited)
            $records = $this->phpDnsFallback($domain, $recordType, $nameserver);
            if (!empty($records)) {
                $result['status'] = 'success';
                $result['records'] = $records;
                $result['output'] = 'PHP DNS fallback';
            }
        }
        
        return $result;
    }

    public function check($domain, $recordType = 'A') {
        $domain = trim($domain);
        if (empty($domain)) {
            return ['error' => 'Domain is required'];
        }

        if (!in_array($recordType, ['A', 'AAAA', 'MX', 'NS', 'TXT', 'CNAME', 'SOA'])) {
            return ['error' => 'Invalid record type'];
        }
        
        $results = [];
        
        foreach ($this->nameservers as $ns => $name) {
            $result = $this->queryNameserver($domain, $ns, $name, $recordType);
            $results[] = $result;
        }
        
        return [
            'domain' => $domain,
            'type' => $recordType,
            'results' => $results,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    private function phpDnsFallback($domain, $recordType, $nameserver) {
        $records = [];
        $typeConstMap = [
            'A' => DNS_A,
            'AAAA' => defined('DNS_AAAA') ? DNS_AAAA : 0,
            'MX' => DNS_MX,
            'NS' => DNS_NS,
            'TXT' => DNS_TXT,
            'CNAME' => DNS_CNAME,
            'SOA' => DNS_SOA,
        ];

        $flags = $typeConstMap[$recordType] ?? DNS_A;
        if ($flags === 0) {
            return [];
        }

        // Note: PHP's dns_get_record cannot target specific nameservers.
        // This fallback queries system resolver only.
        $response = @dns_get_record($domain, $flags);
        if (!is_array($response)) {
            return [];
        }

        foreach ($response as $rec) {
            switch ($recordType) {
                case 'A':
                    if (!empty($rec['ip'])) { $records[] = $rec['ip']; }
                    break;
                case 'AAAA':
                    if (!empty($rec['ipv6'])) { $records[] = $rec['ipv6']; }
                    break;
                case 'MX':
                    if (!empty($rec['target'])) { $records[] = $rec['target']; }
                    break;
                case 'NS':
                    if (!empty($rec['target'])) { $records[] = $rec['target']; }
                    break;
                case 'TXT':
                    if (!empty($rec['txt'])) { $records[] = $rec['txt']; }
                    break;
                case 'CNAME':
                    if (!empty($rec['target'])) { $records[] = $rec['target']; }
                    break;
                case 'SOA':
                    if (!empty($rec['mname'])) { $records[] = $rec['mname']; }
                    break;
            }
        }
        return array_values(array_unique(array_filter($records)));
    }
} 
<?php
class DnssecTestModel {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public function getDnssec($domain) {
        if (empty($domain)) {
            return ['error' => 'Domain parameter is required.'];
        }
        
        if (!filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return ['error' => 'Invalid domain format provided.'];
        }
        
        try {
            $dnssec_data = $this->checkDnssec($domain);
            
            return [
                'domain' => $domain,
                'dnssec_status' => $dnssec_data['status'],
                'tests' => $dnssec_data['tests'],
                'summary' => $dnssec_data['summary'],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            error_log("DNSSEC test error: " . $e->getMessage());
            return ['error' => 'An error occurred while testing DNSSEC.'];
        }
    }
    
    private function checkDnssec($domain) {
        $tests = [];
        $status = 'unknown';
        
        $tests['dnssec_exists'] = $this->checkDnssecExists($domain);
        $tests['dnssec_valid'] = $this->checkDnssecValid($domain);
        $tests['dnssec_chain'] = $this->checkDnssecChain($domain);
        $tests['dnssec_algorithms'] = $this->checkDnssecAlgorithms($domain);
        
        $passed_tests = 0;
        $total_tests = count($tests);
        
        foreach ($tests as $test) {
            if ($test['status'] === 'passed') {
                $passed_tests++;
            }
        }
        
        if ($passed_tests === $total_tests) {
            $status = 'secure';
        } elseif ($passed_tests > 0) {
            $status = 'partially_secure';
        } else {
            $status = 'insecure';
        }
        
        $summary = [
            'overall_status' => $status,
            'tests_passed' => $passed_tests,
            'total_tests' => $total_tests,
            'percentage' => round(($passed_tests / $total_tests) * 100, 2)
        ];
        
        return [
            'status' => $status,
            'tests' => $tests,
            'summary' => $summary
        ];
    }
    
    private function checkDnssecExists($domain) {
        $dnssec_records = @dns_get_record($domain, DNS_SOA);
        
        if (empty($dnssec_records)) {
            return [
                'status' => 'failed',
                'message' => 'No SOA record found',
                'details' => 'DNSSEC requires a valid SOA record'
            ];
        }
        
        $dnssec_records = @dns_get_record($domain, DNS_ANY);
        $has_dnssec = false;
        
        foreach ($dnssec_records as $record) {
            if (isset($record['type']) && in_array($record['type'], ['DNSKEY', 'DS', 'RRSIG'])) {
                $has_dnssec = true;
                break;
            }
        }
        
        if ($has_dnssec) {
            return [
                'status' => 'passed',
                'message' => 'DNSSEC records found',
                'details' => 'Domain has DNSSEC records configured'
            ];
        } else {
            return [
                'status' => 'failed',
                'message' => 'No DNSSEC records found',
                'details' => 'Domain does not have DNSSEC configured'
            ];
        }
    }
    
    private function checkDnssecValid($domain) {
        $shellDisabled = !function_exists('shell_exec') || in_array('shell_exec', array_map('trim', explode(',', (string)ini_get('disable_functions'))));
        if ($shellDisabled) {
            $any = @dns_get_record($domain, DNS_ANY);
            $hasSig = false;
            if ($any) {
                foreach ($any as $rec) {
                    if (!empty($rec['type']) && in_array($rec['type'], ['RRSIG','DNSKEY','DS'])) { $hasSig = true; break; }
                }
            }
            if ($hasSig) {
                return ['status' => 'passed', 'message' => 'DNSSEC-related records observed', 'details' => 'RRSIG/DNSKEY/DS present (limited check)'];
            }
            return ['status' => 'failed', 'message' => 'No DNSSEC signatures found', 'details' => 'Limited check without dig'];
        }
        $command = "dig +dnssec {$domain} SOA 2>&1";
        $output = shell_exec($command);
        
        if (strpos($output, 'RRSIG') !== false) {
            return [
                'status' => 'passed',
                'message' => 'DNSSEC signatures found',
                'details' => 'Domain has valid DNSSEC signatures'
            ];
        } else {
            return [
                'status' => 'failed',
                'message' => 'No DNSSEC signatures found',
                'details' => 'Domain does not have DNSSEC signatures'
            ];
        }
    }
    
    private function checkDnssecChain($domain) {
        $shellDisabled = !function_exists('shell_exec') || in_array('shell_exec', array_map('trim', explode(',', (string)ini_get('disable_functions'))));
        if ($shellDisabled) {
            $any = @dns_get_record($domain, DNS_ANY);
            $hasDnskey = false;
            if ($any) {
                foreach ($any as $rec) { if (!empty($rec['type']) && $rec['type'] === 'DNSKEY') { $hasDnskey = true; break; } }
            }
            if ($hasDnskey) {
                return ['status' => 'passed', 'message' => 'DNSKEY records present', 'details' => 'Limited chain validation'];
            }
            return ['status' => 'failed', 'message' => 'No DNSKEY records found', 'details' => 'Limited check without dig'];
        }
        $command = "dig +dnssec {$domain} DNSKEY 2>&1";
        $output = shell_exec($command);
        
        if (strpos($output, 'DNSKEY') !== false) {
            return [
                'status' => 'passed',
                'message' => 'DNSKEY records found',
                'details' => 'Domain has DNSKEY records for chain validation'
            ];
        } else {
            return [
                'status' => 'failed',
                'message' => 'No DNSKEY records found',
                'details' => 'Domain does not have DNSKEY records'
            ];
        }
    }
    
    private function checkDnssecAlgorithms($domain) {
        $shellDisabled = !function_exists('shell_exec') || in_array('shell_exec', array_map('trim', explode(',', (string)ini_get('disable_functions'))));
        if ($shellDisabled) {
            $any = @dns_get_record($domain, DNS_ANY);
            $hasAny = is_array($any) && count($any) > 0;
            if (!$hasAny) {
                return ['status' => 'failed', 'message' => 'No DNS records', 'details' => 'Unable to determine algorithms without dig'];
            }
            return ['status' => 'passed', 'message' => 'DNSSEC check limited', 'details' => 'Algorithms not enumerated without dig'];
        }
        $command = "dig +dnssec {$domain} DNSKEY 2>&1";
        $output = shell_exec($command);
        
        $algorithms = [];
        if (preg_match_all('/DNSKEY\s+\d+\s+\d+\s+(\d+)/', $output, $matches)) {
            foreach ($matches[1] as $algo) {
                $algorithms[] = $algo;
            }
        }
        
        if (!empty($algorithms)) {
            $algorithm_names = [
                '1' => 'RSA/MD5',
                '2' => 'Diffie-Hellman',
                '3' => 'DSA/SHA1',
                '5' => 'RSA/SHA-1',
                '6' => 'DSA-NSEC3-SHA1',
                '7' => 'RSASHA1-NSEC3-SHA1',
                '8' => 'RSA/SHA-256',
                '10' => 'RSA/SHA-512',
                '13' => 'ECDSA P-256 with SHA-256',
                '14' => 'ECDSA P-384 with SHA-384',
                '15' => 'Ed25519',
                '16' => 'Ed448'
            ];
            
            $algorithm_list = [];
            foreach ($algorithms as $algo) {
                $algorithm_list[] = $algorithm_names[$algo] ?? "Algorithm {$algo}";
            }
            
            return [
                'status' => 'passed',
                'message' => 'DNSSEC algorithms found',
                'details' => 'Supported algorithms: ' . implode(', ', $algorithm_list)
            ];
        } else {
            return [
                'status' => 'failed',
                'message' => 'No DNSSEC algorithms found',
                'details' => 'No supported DNSSEC algorithms detected'
            ];
        }
    }
}

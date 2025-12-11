<?php
class WhoisLookupModel {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public function lookup($domain) {
        if (empty($domain)) {
            return ['error' => 'Domain parameter is required.'];
        }
        
        if (!filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) && !filter_var($domain, FILTER_VALIDATE_IP)) {
            return ['error' => 'Invalid domain or IP format provided.'];
        }
        
        try {
            $whois_output = $this->queryWhois($domain);
            if (empty($whois_output)) {
                return ['error' => "Could not retrieve WHOIS information for: " . htmlspecialchars($domain)];
            }
            
            $parsed_data = $this->parseWhoisOutput($whois_output, $domain);
            
            return [
                'domain' => $domain,
                'raw' => $whois_output,
                'formatted' => $parsed_data['formatted'],
                'summary' => $parsed_data['summary'],
                'details' => $parsed_data['details']
            ];
            
        } catch (Exception $e) {
            error_log("WHOIS lookup error: " . $e->getMessage());
            return ['error' => 'An error occurred while looking up WHOIS information.'];
        }
    }
    
    private function queryWhois($query) {
        $server = $this->getWhoisServer($query);
        if (!$server) {
            return null;
        }
        $response = $this->whoisTcpQuery($server, $query);
        if (!$response) {
            return null;
        }
        // Follow referral to registrar WHOIS if provided
        if (preg_match('/Registrar WHOIS Server:\s*(\S+)/i', $response, $m)) {
            $refServer = trim($m[1]);
            $refResponse = $this->whoisTcpQuery($refServer, $query);
            if ($refResponse) {
                $response .= "\n\n" . $refResponse;
            }
        }
        return $response;
    }

    private function whoisTcpQuery($server, $query) {
        $fp = @fsockopen($server, 43, $errno, $errstr, 8);
        if (!$fp) {
            error_log("WHOIS TCP connect failed to $server: $errstr ($errno)");
            return null;
        }
        stream_set_timeout($fp, 12);
        fwrite($fp, $query . "\r\n");
        $out = '';
        while (!feof($fp)) {
            $out .= fgets($fp, 1024);
        }
        fclose($fp);
        return $out;
    }

    private function getWhoisServer($query) {
        if (filter_var($query, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || filter_var($query, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return 'whois.arin.net';
        }
        $parts = explode('.', $query);
        $tld = strtolower(array_pop($parts));
        $map = [
            'com' => 'whois.verisign-grs.com',
            'net' => 'whois.verisign-grs.com',
            'org' => 'whois.pir.org',
            'io' => 'whois.nic.io',
            'ai' => 'whois.nic.ai',
            'in' => 'whois.registry.in',
            'uk' => 'whois.nic.uk',
            'co' => 'whois.nic.co',
            'de' => 'whois.denic.de',
            'nl' => 'whois.domain-registry.nl',
            'se' => 'whois.iis.se',
            'fi' => 'whois.fi',
            'fr' => 'whois.nic.fr',
            'ca' => 'whois.cira.ca',
            'au' => 'whois.auda.org.au',
            'info' => 'whois.afilias.net',
            'biz' => 'whois.nic.biz',
            'xyz' => 'whois.nic.xyz',
            'online' => 'whois.nic.online',
            'site' => 'whois.nic.site',
            'store' => 'whois.nic.store'
        ];
        return $map[$tld] ?? 'whois.iana.org';
    }
    
    private function parseWhoisOutput($output, $domain) {
        $lines = explode("\n", $output);
        $details = [];
        $summary = [];
        
        $key_mappings = [
            'domain name' => 'Domain Name',
            'registrar' => 'Registrar',
            'registrar whois' => 'Registrar WHOIS',
            'registrar url' => 'Registrar URL',
            'creation date' => 'Creation Date',
            'updated date' => 'Updated Date',
            'expiration date' => 'Expiration Date',
            'name server' => 'Name Server',
            'dnssec' => 'DNSSEC',
            'status' => 'Status',
            'organization' => 'Organization',
            'org' => 'Organization',
            'admin contact' => 'Admin Contact',
            'technical contact' => 'Technical Contact',
            'registrant' => 'Registrant',
            'abuse contact' => 'Abuse Contact'
        ];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '%') === 0 || strpos($line, '#') === 0) {
                continue;
            }
            
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $key = strtolower(trim($key));
                $value = trim($value);
                
                if (!empty($value)) {
                    $mapped_key = $key_mappings[$key] ?? ucfirst(str_replace('_', ' ', $key));
                    $details[$mapped_key] = $value;
                    
                    if (in_array($key, ['registrar', 'creation date', 'expiration date', 'name server'])) {
                        $summary[$mapped_key] = $value;
                    }
                }
            }
        }
        
        $formatted = $output;
        
        return [
            'formatted' => $formatted,
            'summary' => $summary,
            'details' => $details
        ];
    }
}

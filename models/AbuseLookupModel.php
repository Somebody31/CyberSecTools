<?php
class AbuseLookupModel {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public function lookup($query) {
        if (empty($query)) {
            return ['error' => 'Query parameter is required.'];
        }
        
        if (!filter_var($query, FILTER_VALIDATE_IP) && !filter_var($query, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return ['error' => 'Invalid input. Please provide a valid IP address or domain name.'];
        }
        
        try {
            $whois_output = $this->executeWhois($query);
            if (empty($whois_output)) {
                return ['error' => "Could not retrieve WHOIS information for: " . htmlspecialchars($query)];
            }
            
            $abuse_net_output = $this->executeAbuseNet($query);
            
            $results = $this->parseWhoisResults($whois_output, $abuse_net_output, $query);
            
            return $results;
            
        } catch (Exception $e) {
            error_log("Abuse lookup error: " . $e->getMessage());
            return ['error' => 'An error occurred while looking up abuse contact information.'];
        }
    }
    
    private function executeWhois($query) {
        $shellDisabled = !function_exists('shell_exec') || in_array('shell_exec', array_map('trim', explode(',', (string)ini_get('disable_functions'))));
        if ($shellDisabled) {
            return $this->whoisTcp($query);
        }
        $command = "whois " . escapeshellarg($query) . " 2>&1";
        $output = shell_exec($command);
        
        if (empty($output) || strpos($output, 'No match') !== false || strpos($output, 'not found') !== false) {
            return null;
        }
        
        return $output;
    }
    
    private function executeAbuseNet($query) {
        $shellDisabled = !function_exists('shell_exec') || in_array('shell_exec', array_map('trim', explode(',', (string)ini_get('disable_functions'))));
        if ($shellDisabled) {
            return $this->whoisTcp($query, 'whois.abuse.net');
        }
        $command = "whois -h whois.abuse.net " . escapeshellarg($query) . " 2>&1";
        $output = shell_exec($command);
        
        if (empty($output) || strpos($output, 'No match') !== false || strpos($output, 'not found') !== false) {
            return null;
        }
        
        return $output;
    }

    private function whoisTcp($query, $server = null) {
        if (!$server) {
            // crude TLD map similar to WhoisLookupModel
            $parts = explode('.', $query);
            $tld = strtolower(end($parts));
            $map = [
                'com' => 'whois.verisign-grs.com',
                'net' => 'whois.verisign-grs.com',
                'org' => 'whois.pir.org',
                'io' => 'whois.nic.io',
                'ai' => 'whois.nic.ai'
            ];
            $server = $map[$tld] ?? 'whois.iana.org';
        }
        $fp = @fsockopen($server, 43, $errno, $errstr, 8);
        if (!$fp) return null;
        stream_set_timeout($fp, 12);
        fwrite($fp, $query . "\r\n");
        $out = '';
        while (!feof($fp)) { $out .= fgets($fp, 1024); }
        fclose($fp);
        return $out ?: null;
    }
    
    private function parseWhoisResults($whois_output, $abuse_net_output, $query) {
        $results = [
            'query' => $query,
            'registrar' => null,
            'whois_abuse' => null,
            'abuse_net' => null,
            'organization' => null,
            'network' => null,
            'full_record' => $whois_output,
            'abuse_net_record' => $abuse_net_output
        ];
        
        $email_pattern = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/';
        
        $lines = explode("\n", $whois_output);
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (preg_match('/^Registrar:\s*(.*)$/i', $line, $matches)) {
                $results['registrar'] = trim($matches[1]);
            }
            
            if (preg_match('/^Organization:\s*(.*)$/i', $line, $matches)) {
                $results['organization'] = trim($matches[1]);
            }
            
            if (preg_match('/^NetName:\s*(.*)$/i', $line, $matches)) {
                $results['network'] = trim($matches[1]);
            }
            
            if (stripos($line, 'abuse') !== false && preg_match($email_pattern, $line, $matches)) {
                if (is_null($results['whois_abuse'])) {
                    $results['whois_abuse'] = trim($matches[0]);
                }
            }
            
            if ((stripos($line, 'abuse') !== false || stripos($line, 'security') !== false || stripos($line, 'spam') !== false) && 
                preg_match($email_pattern, $line, $matches)) {
                if (is_null($results['whois_abuse'])) {
                    $results['whois_abuse'] = trim($matches[0]);
                }
            }
        }
        
        if (!empty($abuse_net_output)) {
            if (preg_match($email_pattern, $abuse_net_output, $matches)) {
                $results['abuse_net'] = trim($matches[0]);
            }
        }
        
        $results['summary'] = $this->generateSummary($results);
        
        return $results;
    }
    
    private function generateSummary($results) {
        $summary = [];
        
        if ($results['whois_abuse']) {
            $summary[] = "✅ Found abuse contact in WHOIS: " . $results['whois_abuse'];
        }
        
        if ($results['abuse_net']) {
            $summary[] = "✅ Found abuse contact via abuse.net: " . $results['abuse_net'];
        }
        
        if ($results['registrar']) {
            $summary[] = "📋 Registrar: " . $results['registrar'];
        }
        
        if ($results['organization']) {
            $summary[] = "🏢 Organization: " . $results['organization'];
        }
        
        if ($results['network']) {
            $summary[] = "🌐 Network: " . $results['network'];
        }
        
        if (empty($summary)) {
            $summary[] = "⚠️ No abuse contact information found in WHOIS records.";
        }
        
        return $summary;
    }
}

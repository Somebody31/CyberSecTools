<?php
class ReverseIpLookupModel {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public function reverseLookup($ip) {
        if (empty($ip)) {
            return ['error' => 'IP address is required.'];
        }
        
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return ['error' => 'Invalid IP address format provided.'];
        }
        
        try {
            $domains = $this->findDomainsByIp($ip);
            
            return [
                'ip' => $ip,
                'domains' => $domains,
                'count' => count($domains),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            error_log("Reverse IP lookup error: " . $e->getMessage());
            return ['error' => 'An error occurred while performing reverse IP lookup.'];
        }
    }
    
    private function findDomainsByIp($ip) {
        if (!$this->mysqli || $this->mysqli->connect_errno) {
            return [];
        }
        
        try {
            // Use your existing schema `domain_ip_history`.
            $stmt = $this->mysqli->prepare(
                "SELECT domain, ip_address, last_seen, first_seen FROM domain_ip_history WHERE ip_address = ? ORDER BY last_seen DESC LIMIT 200"
            );
            
            if (!$stmt) {
                return [];
            }
            
            $stmt->bind_param('s', $ip);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $domains = [];
            while ($row = $result->fetch_assoc()) {
                $domains[] = [
                    'domain' => $row['domain'],
                    'ip' => $row['ip_address'],
                    'last_seen' => $row['last_seen'],
                    'first_seen' => $row['first_seen']
                ];
            }
            
            $stmt->close();
            
            if (empty($domains)) {
                return [];
            }
            
            return $domains;
            
        } catch (Exception $e) {
            error_log("Database query error: " . $e->getMessage());
            return [];
        }
    }
    
}

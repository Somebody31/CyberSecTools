<?php
class ReverseMxModel {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public function reverseLookup($mxHost) {
        if (empty($mxHost)) {
            return ['error' => 'MX host is required.'];
        }
        
        if (!filter_var($mxHost, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return ['error' => 'Invalid MX host format provided.'];
        }
        
        try {
            $domains = $this->findDomainsByMx($mxHost);
            
            return [
                'mx_host' => $mxHost,
                'domains' => $domains,
                'count' => count($domains),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            error_log("Reverse MX lookup error: " . $e->getMessage());
            return ['error' => 'An error occurred while performing reverse MX lookup.'];
        }
    }
    
    private function findDomainsByMx($mxHost) {
        if (!$this->mysqli || $this->mysqli->connect_errno) {
            return [];
        }
        
        try {
            // Use your existing schema: `domains_mx` with columns (`id`, `domain`, `mx_record`).
            $like = '%' . $mxHost . '%';
            // Allow more time for large result sets
            @set_time_limit(60);
            $stmt = $this->mysqli->prepare(
                "SELECT domain, mx_record FROM domains_mx WHERE mx_record LIKE ? ORDER BY domain"
            );
            
            if (!$stmt) {
                return [];
            }
            
            $stmt->bind_param('s', $like);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $domains = [];
            while ($row = $result->fetch_assoc()) {
                $domains[] = [
                    'domain' => $row['domain'],
                    'mx_host' => $mxHost,
                    'mx_record' => $row['mx_record']
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
?>




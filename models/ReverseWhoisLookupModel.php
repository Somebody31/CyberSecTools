<?php
class ReverseWhoisLookupModel {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public function reverseLookup($query) {
        if (empty($query)) {
            return ['error' => 'Search query is required.'];
        }
        
        if (strlen($query) < 3) {
            return ['error' => 'Search query must be at least 3 characters long.'];
        }
        
        try {
            $domains = $this->findDomainsByWhois($query);
            
            return [
                'query' => $query,
                'domains' => $domains,
                'count' => count($domains),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            error_log("Reverse WHOIS lookup error: " . $e->getMessage());
            return ['error' => 'An error occurred while performing reverse WHOIS lookup.'];
        }
    }
    
    private function findDomainsByWhois($query) {
        if (!$this->mysqli || $this->mysqli->connect_errno) {
            return [];
        }
        
        try {
            $search_term = '%' . $query . '%';
            $stmt = $this->mysqli->prepare("
                SELECT DISTINCT domain_name, registrar, registrant, creation_date, expiration_date 
                FROM whois_records 
                WHERE registrant LIKE ? OR organization LIKE ? OR admin_email LIKE ? 
                ORDER BY creation_date DESC 
                LIMIT 100
            ");
            
            if (!$stmt) {
                return [];
            }
            
            $stmt->bind_param('sss', $search_term, $search_term, $search_term);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $domains = [];
            while ($row = $result->fetch_assoc()) {
                $domains[] = [
                    'domain' => $row['domain_name'],
                    'registrar' => $row['registrar'],
                    'registrant' => $row['registrant'],
                    'creation_date' => $row['creation_date'],
                    'expiration_date' => $row['expiration_date']
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

<?php
class ReverseNsLookupModel {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public function reverseLookup($nameserver) {
        if (empty($nameserver)) {
            return ['error' => 'Nameserver parameter is required.'];
        }
        
        if (!filter_var($nameserver, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return ['error' => 'Invalid nameserver format provided.'];
        }
        
        try {
            $domains = $this->findDomainsByNameserver($nameserver);
            
            return [
                'nameserver' => $nameserver,
                'domains' => $domains,
                'count' => count($domains),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            error_log("Reverse NS lookup error: " . $e->getMessage());
            return ['error' => 'An error occurred while performing reverse NS lookup.'];
        }
    }
    
    private function findDomainsByNameserver($nameserver) {
        if (!$this->mysqli || $this->mysqli->connect_errno) {
            return [];
        }
        
        try {
            $stmt = $this->mysqli->prepare("
                SELECT DISTINCT domain_name, nameserver, registrar, creation_date, expiration_date 
                FROM domain_nameservers 
                WHERE nameserver = ? 
                ORDER BY creation_date DESC 
                LIMIT 100
            ");
            
            if (!$stmt) {
                return [];
            }
            
            $stmt->bind_param('s', $nameserver);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $domains = [];
            while ($row = $result->fetch_assoc()) {
                $domains[] = [
                    'domain' => $row['domain_name'],
                    'nameserver' => $row['nameserver'],
                    'registrar' => $row['registrar'],
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

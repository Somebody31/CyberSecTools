<?php
class NameserverSitesModel {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public function getSites($nameserver) {
        if (empty($nameserver)) {
            return ['error' => 'Nameserver parameter is required.'];
        }
        
        if (!filter_var($nameserver, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return ['error' => 'Invalid nameserver format provided.'];
        }
        
        try {
            $sites = $this->findSitesByNameserver($nameserver);
            
            return [
                'nameserver' => $nameserver,
                'sites' => $sites,
                'count' => count($sites),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            error_log("Nameserver sites error: " . $e->getMessage());
            return ['error' => 'An error occurred while searching for sites.'];
        }
    }
    
    private function findSitesByNameserver($nameserver) {
        if (!$this->mysqli || $this->mysqli->connect_errno) {
            return [];
        }
        
        try {
            $stmt = $this->mysqli->prepare("
                SELECT DISTINCT domain_name, registrar, creation_date, expiration_date 
                FROM domain_nameservers 
                WHERE nameserver = ? 
                ORDER BY domain_name 
                LIMIT 100
            ");
            
            if (!$stmt) {
                return [];
            }
            
            $stmt->bind_param('s', $nameserver);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $sites = [];
            while ($row = $result->fetch_assoc()) {
                $sites[] = [
                    'domain' => $row['domain_name'],
                    'registrar' => $row['registrar'],
                    'creation_date' => $row['creation_date'],
                    'expiration_date' => $row['expiration_date']
                ];
            }
            
            $stmt->close();
            
            if (empty($sites)) {
                return [];
            }
            
            return $sites;
            
        } catch (Exception $e) {
            error_log("Database query error: " . $e->getMessage());
            return [];
        }
    }
    
}

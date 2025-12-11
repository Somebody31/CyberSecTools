<?php
class MacLookupModel {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public function lookupMac($mac) {
        if (!preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $mac)) {
            return ['error' => 'Invalid MAC address format'];
        }
        
        $macParts = preg_split('/[:-]/', $mac);
        $macPrefix = strtoupper(sprintf("%02s:%02s:%02s", $macParts[0], $macParts[1], $macParts[2]));
        
        if (!$this->mysqli) {
            return ['error' => 'Database connection failed'];
        }
        
        try {
            $stmt = $this->mysqli->prepare("SELECT macPrefix, vendorName, private, blockType, lastUpdate FROM vendors WHERE macPrefix = ?");
            if (!$stmt) {
                return ['error' => 'Database query preparation failed'];
            }
            
            $stmt->bind_param('s', $macPrefix);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $stmt->close();
                
                return [
                    'summary' => [
                        'MAC Address' => $mac,
                        'Vendor' => $row['vendorName']
                    ],
                    'details' => [
                        'MAC Prefix' => $row['macPrefix'],
                        'Vendor Name' => $row['vendorName'],
                        'Last Update' => $row['lastUpdate']
                    ]
                ];
            } else {
                $stmt->close();
                return ['error' => 'No vendor found for this MAC address'];
            }
        } catch (Exception $e) {
            error_log("MAC lookup error: " . $e->getMessage());
            return ['error' => 'Database query failed'];
        }
    }
}

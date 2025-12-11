<?php
class IpHistoryModel {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }

    public function getDomainHistory($domain) {
        try {
            $history = [];
            
            $aRecords = dns_get_record($domain, DNS_A);
            foreach ($aRecords as $record) {
                if (isset($record['ip'])) {
                    $whoisInfo = $this->getWhoisInfo($record['ip']);
                    $this->saveRecord($domain, $record['ip'], $whoisInfo);
                    $history[] = [
                        'ip_address' => $record['ip'],
                        'location' => $whoisInfo['location'],
                        'owner' => $whoisInfo['owner'],
                        'network' => $whoisInfo['network'],
                        'as_number' => $whoisInfo['as_number'],
                        'last_seen' => date('Y-m-d')
                    ];
                }
            }

            $aaaaRecords = dns_get_record($domain, DNS_AAAA);
            foreach ($aaaaRecords as $record) {
                if (isset($record['ipv6'])) {
                    $whoisInfo = $this->getWhoisInfo($record['ipv6']);
                    $this->saveRecord($domain, $record['ipv6'], $whoisInfo);
                    $history[] = [
                        'ip_address' => $record['ipv6'],
                        'location' => $whoisInfo['location'],
                        'owner' => $whoisInfo['owner'],
                        'network' => $whoisInfo['network'],
                        'as_number' => $whoisInfo['as_number'],
                        'last_seen' => date('Y-m-d')
                    ];
                }
            }

            $stmt = $this->mysqli->prepare("
                SELECT DISTINCT
                    ip_address,
                    location,
                    owner,
                    network,
                    as_number,
                    DATE_FORMAT(last_seen, '%Y-%m-%d') as last_seen
                FROM domain_ip_history 
                WHERE domain = ?
                  AND last_seen < CURDATE()
                ORDER BY last_seen DESC
                LIMIT 10
            ");

            if ($stmt) {
                $stmt->bind_param('s', $domain);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    if (!$this->isIpInHistory($row['ip_address'], $history)) {
                        $history[] = $row;
                    }
                }
            }

            return $history;

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function isIpInHistory($ip, $history) {
        foreach ($history as $record) {
            if ($record['ip_address'] === $ip) {
                return true;
            }
        }
        return false;
    }

    private function getWhoisInfo($ip) {
        $info = [
            'location' => 'Unknown',
            'owner' => 'Unknown',
            'network' => '',
            'as_number' => ''
        ];

        try {
            $whois = '';
            $shellDisabled = !function_exists('shell_exec') || in_array('shell_exec', array_map('trim', explode(',', (string)ini_get('disable_functions'))));
            if (!$shellDisabled) {
                $whois = @shell_exec("whois " . escapeshellarg($ip));
            }
            if (!$whois) {
                $whois = $this->whoisTcp($ip) ?: '';
            }
            
            if (preg_match('/(?:Organization|OrgName|owner|descr):\s*(.+?)(?:\n|$)/im', $whois, $matches)) {
                $info['owner'] = trim($matches[1]);
            }
            
            if (preg_match('/(?:Country|location):\s*(.+?)(?:\n|$)/im', $whois, $matches)) {
                $info['location'] = trim($matches[1]);
            }

            if (preg_match('/(?:CIDR|Network|route):\s*(.+?)(?:\n|$)/im', $whois, $matches)) {
                $info['network'] = trim($matches[1]);
            }

            if (preg_match('/(?:OriginAS|ASName|ASN):\s*(.+?)(?:\n|$)/im', $whois, $matches)) {
                $info['as_number'] = trim($matches[1]);
            }

            if ($info['location'] === 'Unknown') {
                $ipInfo = @file_get_contents("http://ip-api.com/json/" . $ip);
                if ($ipInfo) {
                    $ipData = json_decode($ipInfo, true);
                    if ($ipData && isset($ipData['country'])) {
                        $info['location'] = $ipData['country'];
                        if (isset($ipData['org'])) $info['owner'] = $ipData['org'];
                        if (isset($ipData['as'])) $info['as_number'] = $ipData['as'];
                    }
                }
            }

        } catch (Exception $e) {
            error_log("WhoisInfo error: " . $e->getMessage());
        }

        return $info;
    }

    private function saveRecord($domain, $ip, $info) {
        try {
            $stmt = $this->mysqli->prepare("
                INSERT INTO domain_ip_history 
                (domain, ip_address, location, owner, network, as_number, first_seen, last_seen)
                VALUES (?, ?, ?, ?, ?, ?, CURDATE(), CURDATE())
                ON DUPLICATE KEY UPDATE 
                    last_seen = CURDATE(),
                    location = VALUES(location),
                    owner = VALUES(owner),
                    network = VALUES(network),
                    as_number = VALUES(as_number)
            ");

            $stmt->bind_param('ssssss', 
                $domain,
                $ip,
                $info['location'],
                $info['owner'],
                $info['network'],
                $info['as_number']
            );

            $stmt->execute();
        } catch (Exception $e) {
            error_log("SaveRecord error: " . $e->getMessage());
        }
    }

    private function whoisTcp($query) {
        $server = filter_var($query, FILTER_VALIDATE_IP) ? 'whois.arin.net' : 'whois.iana.org';
        $fp = @fsockopen($server, 43, $errno, $errstr, 8);
        if (!$fp) return null;
        stream_set_timeout($fp, 12);
        fwrite($fp, $query . "\r\n");
        $out = '';
        while (!feof($fp)) { $out .= fgets($fp, 1024); }
        fclose($fp);
        return $out ?: null;
    }
}

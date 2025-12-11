<?php
class AsnLookupModel {
    public function lookupASN($asnQuery) {
        if (empty($asnQuery)) {
            return ['Error' => 'ASN query parameter is missing.'];
        }

        try {
            $asnInfo = $this->deepLookupASN($asnQuery);
            return $asnInfo;
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Failed to fetch') !== false || strpos($e->getMessage(), 'RDAP API') !== false) {
                throw new Exception("Failed to fetch data from RDAP API: " . $e->getMessage(), 502);
            } else {
                throw new Exception("An external or server error occurred: " . $e->getMessage(), 500);
            }
        }
    }

    private function fetchWithRetries(string $url, int $retries = 3): ?array {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36';
        
        $headers = [
            'User-Agent: ' . $userAgent,
            'Accept: application/rdap+json,application/json;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
            'Accept-Encoding: gzip, deflate, br',
            'Connection: keep-alive',
        ];

        $alternativeServers = [
            'https://rdap.arin.net/bootstrap/autnum/',
            'https://rdap.db.ripe.net/autnum/',
            'https://rdap.apnic.net/autnum/',
            'https://rdap.lacnic.net/autnum/'
        ];

        $urlsToTry = [$url];
        if (preg_match('/\/autnum\/(\d+)/', $url, $matches)) {
            $asnNumber = $matches[1];
            foreach ($alternativeServers as $server) {
                $urlsToTry[] = $server . $asnNumber;
            }
        }

        foreach ($urlsToTry as $tryUrl) {
            for ($i = 0; $i < $retries; $i++) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $tryUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

                curl_setopt($ch, CURLOPT_ENCODING, "");
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                
                curl_setopt($ch, CURLOPT_TCP_KEEPALIVE, 1);
                curl_setopt($ch, CURLOPT_TCP_KEEPIDLE, 120);
                curl_setopt($ch, CURLOPT_TCP_KEEPINTVL, 60);
            
                $response = curl_exec($ch);
                $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curl_error = curl_error($ch);
                curl_close($ch);

                if ($response !== false && $http_status >= 200 && $http_status < 300) {
                    return json_decode($response, true) ?: [];
                }
                
                if ($http_status === 429 || $http_status === 408 || $http_status >= 500) {
                    usleep(1000 * 1000 * pow(2, $i));
                    continue;
                }
                if ($curl_error) {
                    throw new Exception("Network error fetching {$url}: " . $curl_error);
                }
            }
        }
        throw new Exception("Failed to fetch {$url} after {$retries} attempts. Last status: {$http_status}.");
    }

    private function deepLookupASN(string $query): array {
        $asnNumber = null;
        if (preg_match('/^AS(\d+)$/i', $query, $matches)) {
            $asnNumber = $matches[1];
        } elseif (preg_match('/^(\d+)$/', $query, $matches)) {
            $asnNumber = $matches[1];
        } else {
            throw new Exception("Invalid ASN format. Please provide ASN in format 'AS12345' or '12345'.");
        }

        $bootstrapUrl = "https://rdap.arin.net/bootstrap/autnum/{$asnNumber}";
        
        $bootstrapData = $this->fetchWithRetries($bootstrapUrl);
        if (empty($bootstrapData) || !isset($bootstrapData['rdapConformance'])) {
            throw new Exception("Failed to fetch bootstrap data for ASN {$asnNumber}");
        }

        $referralUrl = null;
        if (isset($bootstrapData['links'])) {
            foreach ($bootstrapData['links'] as $link) {
                if (isset($link['rel']) && $link['rel'] === 'alternate' && isset($link['href'])) {
                    $referralUrl = $link['href'];
                    break;
                }
            }
        }

        if (!$referralUrl) {
            throw new Exception("No referral URL found in bootstrap data");
        }

        $mainData = $this->fetchWithRetries($referralUrl);
        if (empty($mainData)) {
            throw new Exception("Failed to fetch main ASN data from referral URL");
        }

        $entitiesByHandle = [];
        if (isset($mainData['entities'])) {
            foreach ($mainData['entities'] as $entity) {
                if (isset($entity['handle'])) {
                    $entitiesByHandle[$entity['handle']] = $entity;
                }
            }
        }

        return $this->parseRdapResponse($mainData, $entitiesByHandle);
    }

    private function parseRdapResponse(array $mainData, array $entitiesByHandle): array {
        $result = [
            'asn' => $mainData['handle'] ?? 'Unknown',
            'name' => $mainData['name'] ?? 'Unknown',
            'type' => $mainData['type'] ?? 'Unknown',
            'status' => $mainData['status'] ?? [],
            'description' => $mainData['description'] ?? [],
            'contacts' => [],
            'entities' => []
        ];

        $findVcardProp = function($vcard, $propName) {
            foreach ($vcard as $prop) {
                if (isset($prop[0]) && $prop[0] === $propName) {
                    return $prop[3] ?? '';
                }
            }
            return '';
        };

        $fillContactBlock = function($entity, $prefix) use (&$result, $findVcardProp) {
            if (isset($entity['vcardArray']) && is_array($entity['vcardArray'])) {
                foreach ($entity['vcardArray'] as $vcard) {
                    if (is_array($vcard)) {
                        $contact = [
                            'type' => $prefix,
                            'name' => $findVcardProp($vcard, 'fn'),
                            'email' => $findVcardProp($vcard, 'email'),
                            'phone' => $findVcardProp($vcard, 'tel'),
                            'address' => $findVcardProp($vcard, 'adr')
                        ];
                        $result['contacts'][] = $contact;
                    }
                }
            }
        };

        if (isset($mainData['entities'])) {
            foreach ($mainData['entities'] as $entity) {
                $fillContactBlock($entity, 'Entity');
                $result['entities'][] = [
                    'handle' => $entity['handle'] ?? 'Unknown',
                    'name' => $entity['name'] ?? 'Unknown',
                    'type' => $entity['type'] ?? 'Unknown',
                    'roles' => $entity['roles'] ?? []
                ];
            }
        }

        return $result;
    }
}

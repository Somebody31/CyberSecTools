<?php
class DnsLookupModel {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }

    public function lookup($domain, $type = null) {
        try {
            $records = [];

            $domain = rtrim($domain, '.') . '.';

            $wantTypes = $this->resolveWantedTypes($type);

            foreach ($wantTypes as $want) {
                switch ($want) {
                    case 'SOA':
                        $soa = @dns_get_record($domain, DNS_SOA);
                        if ($soa && isset($soa[0])) {
                            $r = $soa[0];
                            $records[] = [
                                'name' => $domain,
                                'ttl' => isset($r['ttl']) ? intval($r['ttl']) : null,
                                'class' => 'IN',
                                'type' => 'SOA',
                                'priority' => '',
                                'data' => sprintf('%s. %s. %d %d %d %d %d',
                                    $r['mname'],
                                    $r['rname'],
                                    $r['serial'],
                                    $r['refresh'],
                                    $r['retry'],
                                    $r['expire'],
                                    $r['minimum-ttl']
                                )
                            ];
                        }
                        break;
                    case 'NS':
                        $ns = @dns_get_record($domain, DNS_NS);
                        foreach ($ns as $r) {
                            $records[] = [
                                'name' => $domain,
                                'ttl' => isset($r['ttl']) ? intval($r['ttl']) : null,
                                'class' => 'IN',
                                'type' => 'NS',
                                'priority' => '',
                                'data' => rtrim($r['target'], '.') . '.'
                            ];
                        }
                        break;
                    case 'A':
                        $a = @dns_get_record($domain, DNS_A);
                        foreach ($a as $r) {
                            $records[] = [
                                'name' => $domain,
                                'ttl' => isset($r['ttl']) ? intval($r['ttl']) : null,
                                'class' => 'IN',
                                'type' => 'A',
                                'priority' => '',
                                'data' => $r['ip']
                            ];
                        }
                        break;
                    case 'AAAA':
                        $aaaa = @dns_get_record($domain, DNS_AAAA);
                        foreach ($aaaa as $r) {
                            $records[] = [
                                'name' => $domain,
                                'ttl' => isset($r['ttl']) ? intval($r['ttl']) : null,
                                'class' => 'IN',
                                'type' => 'AAAA',
                                'priority' => '',
                                'data' => $r['ipv6']
                            ];
                        }
                        break;
                    case 'MX':
                        $mx = @dns_get_record($domain, DNS_MX);
                        foreach ($mx as $r) {
                            $records[] = [
                                'name' => $domain,
                                'ttl' => isset($r['ttl']) ? intval($r['ttl']) : null,
                                'class' => 'IN',
                                'type' => 'MX',
                                'priority' => isset($r['pri']) ? intval($r['pri']) : '',
                                'data' => rtrim($r['target'], '.') . '.'
                            ];
                        }
                        break;
                    case 'CNAME':
                        $cn = @dns_get_record($domain, DNS_CNAME);
                        foreach ($cn as $r) {
                            $records[] = [
                                'name' => $domain,
                                'ttl' => isset($r['ttl']) ? intval($r['ttl']) : null,
                                'class' => 'IN',
                                'type' => 'CNAME',
                                'priority' => '',
                                'data' => rtrim($r['target'], '.') . '.'
                            ];
                        }
                        break;
                    case 'TXT':
                        $txt = @dns_get_record($domain, DNS_TXT);
                        foreach ($txt as $r) {
                            if (isset($r['txt'])) {
                                $records[] = [
                                    'name' => $domain,
                                    'ttl' => isset($r['ttl']) ? intval($r['ttl']) : null,
                                    'class' => 'IN',
                                    'type' => 'TXT',
                                    'priority' => '',
                                    'data' => $r['txt']
                                ];
                            }
                        }
                        break;
                }
            }

            return [
                'status' => 'success',
                'records' => $records
            ];

        } catch (Exception $e) {
            error_log("DNS Lookup Error: " . $e->getMessage());
            return [
                'status' => 'error',
                'error' => 'Could not retrieve DNS records'
            ];
        }
    }

    private function resolveWantedTypes($type) {
        $all = ['SOA','NS','A','AAAA','MX','CNAME','TXT'];
        if (!$type) return $all;
        $type = strtoupper($type);
        if (in_array($type, $all)) return [$type];
        return $all;
    }
}

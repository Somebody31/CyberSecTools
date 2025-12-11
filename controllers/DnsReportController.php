<?php
require_once __DIR__ . '/../models/DnsReportModel.php';

class DnsReportController {
    private $model;
    
    public function __construct($mysqli) {
        $this->model = new DnsReportModel($mysqli);
    }
    
    public function handleRequest($domain) {
        if (empty($domain)) {
            return [
                'query' => ['tool' => 'dns-report', 'domain' => ''],
                'response' => ['error' => 'Domain parameter is required.']
            ];
        }
        
        $result = $this->model->getReport($domain);
        
        if (isset($result['error'])) {
            return [
                'query' => ['tool' => 'dns-report', 'domain' => $domain],
                'response' => $result
            ];
        }
        
        $records = [];
        
        if (isset($result['parent_tests'])) {
            foreach ($result['parent_tests'] as $test) {
                if ($test['status'] === 'INFO' && strpos($test['case'], 'NS records') !== false) {
                    $lines = explode("\n", $test['info']);
                    foreach ($lines as $line) {
                        if (preg_match('/^(.+?)\. \[(.+?)\] \[TTL=(.+?)\]$/', $line, $matches)) {
                            $records[] = [
                                'type' => 'NS',
                                'name' => $matches[1],
                                'value' => $matches[2],
                                'ttl' => $matches[3]
                            ];
                        }
                    }
                }
            }
        }
        
        if (isset($result['soa_tests'])) {
            foreach ($result['soa_tests'] as $test) {
                if ($test['status'] === 'INFO' && strpos($test['case'], 'SOA record') !== false) {
                    $lines = explode("\n", $test['info']);
                    foreach ($lines as $line) {
                        if (preg_match('/^(.+?): (.+)$/', $line, $matches)) {
                            if ($matches[1] === 'Primary NS') {
                                $records[] = [
                                    'type' => 'SOA',
                                    'name' => 'Primary NS',
                                    'value' => $matches[2],
                                    'ttl' => ''
                                ];
                            }
                        }
                    }
                }
            }
        }
        
        if (isset($result['mx_tests'])) {
            foreach ($result['mx_tests'] as $test) {
                if ($test['status'] === 'INFO' && strpos($test['case'], 'MX records') !== false) {
                    $lines = explode("\n", $test['info']);
                    foreach ($lines as $line) {
                        if (preg_match('/^Priority: (\d+), Target: (.+?) \[(.+?)\] \[TTL=(.+?)\]$/', $line, $matches)) {
                            $records[] = [
                                'type' => 'MX',
                                'name' => $matches[1],
                                'value' => $matches[2],
                                'ttl' => $matches[4]
                            ];
                        }
                    }
                }
            }
        }
        
        if (isset($result['www_tests'])) {
            foreach ($result['www_tests'] as $test) {
                if ($test['status'] === 'INFO' && strpos($test['case'], 'WWW A records') !== false) {
                    $lines = explode("\n", $test['info']);
                    foreach ($lines as $line) {
                        if (preg_match('/^(.+?) \[TTL=(.+?)\]$/', $line, $matches)) {
                            $records[] = [
                                'type' => 'A',
                                'name' => 'www',
                                'value' => $matches[1],
                                'ttl' => $matches[2]
                            ];
                        }
                    }
                }
            }
        }
        
        return [
            'query' => ['tool' => 'dns-report', 'domain' => $domain],
            'response' => [
                'domain' => $domain,
                'report' => $result,
                'records' => $records
            ]
        ];
    }
}

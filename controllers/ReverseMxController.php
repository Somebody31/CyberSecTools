<?php
require_once __DIR__ . '/../models/ReverseMxModel.php';

class ReverseMxController {
    private $model;
    
    public function __construct($mysqli) {
        $this->model = new ReverseMxModel($mysqli);
    }
    
    public function handleRequest($mxHost) {
        if (empty($mxHost)) {
            return [
                'query' => ['tool' => 'reverse-mx-lookup', 'mailserver' => ''],
                'response' => ['error' => 'MX host is required.']
            ];
        }
        
        $result = $this->model->reverseLookup($mxHost);
        if (isset($result['error'])) {
            return [
                'query' => ['tool' => 'reverse-mx-lookup', 'mailserver' => $mxHost],
                'response' => ['error' => $result['error']]
            ];
        }

        $domains = isset($result['domains']) ? $result['domains'] : [];
        $flatDomains = [];
        foreach ($domains as $item) {
            if (is_array($item)) {
                if (isset($item['domain'])) {
                    $flatDomains[] = $item['domain'];
                } elseif (isset($item['domain_name'])) {
                    $flatDomains[] = $item['domain_name'];
                } elseif (isset($item[0])) {
                    $flatDomains[] = (string)$item[0];
                }
            } elseif (is_string($item)) {
                $flatDomains[] = $item;
            }
        }

        return [
            'query' => ['tool' => 'reverse-mx-lookup', 'mailserver' => $mxHost],
            'response' => [
                'mx_domain' => $mxHost,
                'domains' => $flatDomains,
                'count' => count($flatDomains)
            ]
        ];
    }
}
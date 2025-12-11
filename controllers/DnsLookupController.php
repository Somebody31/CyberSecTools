<?php
require_once __DIR__ . '/../models/DnsLookupModel.php';

class DnsLookupController {
    private $model;
    
    public function __construct($mysqli) {
        $this->model = new DnsLookupModel($mysqli);
    }
    
    public function handleRequest($domain, $type = null) {
        if (empty($domain)) {
            return ['response' => ['error' => 'Domain is required.']];
        }
        
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = rtrim($domain, '/');
        
        if (!filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return ['response' => ['error' => 'Invalid domain format.']];
        }
        
        $result = $this->model->lookup($domain, $type);
        return ['response' => $result];
    }
}
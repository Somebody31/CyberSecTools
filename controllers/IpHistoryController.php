<?php
require_once __DIR__ . '/../models/IpHistoryModel.php';

class IpHistoryController {
    private $model;
    
    public function __construct($mysqli) {
        $this->model = new IpHistoryModel($mysqli);
    }
    
    public function handleRequest($domain) {
        if (empty($domain)) {
            return ['response' => ['error' => 'Domain is required.']];
        }
        
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = rtrim($domain, '/. ');
        
        if (!filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return ['response' => ['error' => 'Invalid domain format.']];
        }
        
        try {
            $result = $this->model->getDomainHistory($domain);
            return ['response' => ['history' => $result]];
        } catch (Exception $e) {
            return ['response' => ['error' => 'Could not retrieve domain history.']];
        }
    }
}

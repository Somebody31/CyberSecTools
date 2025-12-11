<?php
require_once __DIR__ . '/../models/ReverseIpLookupModel.php';

class ReverseIpLookupController {
    private $model;
    
    public function __construct($mysqli) {
        $this->model = new ReverseIpLookupModel($mysqli);
    }
    
    public function handleRequest($ip) {
        if (empty($ip)) {
            return ['response' => ['error' => 'IP address is required.']];
        }
        
        $result = $this->model->reverseLookup($ip);
        return ['response' => $result];
    }
}

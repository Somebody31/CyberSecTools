<?php
require_once __DIR__ . '/../models/ReverseNsLookupModel.php';

class ReverseNsLookupController {
    private $model;
    
    public function __construct($mysqli) {
        $this->model = new ReverseNsLookupModel($mysqli);
    }
    
    public function handleRequest($nameserver) {
        if (empty($nameserver)) {
            return ['response' => ['error' => 'Nameserver is required.']];
        }
        
        $result = $this->model->reverseLookup($nameserver);
        return ['response' => $result];
    }
}

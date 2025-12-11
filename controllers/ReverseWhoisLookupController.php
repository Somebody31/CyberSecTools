<?php
require_once __DIR__ . '/../models/ReverseWhoisLookupModel.php';

class ReverseWhoisLookupController {
    private $model;
    
    public function __construct($mysqli) {
        $this->model = new ReverseWhoisLookupModel($mysqli);
    }
    
    public function handleRequest($query) {
        if (empty($query)) {
            return ['response' => ['error' => 'Search query is required.']];
        }
        
        $result = $this->model->reverseLookup($query);
        return ['response' => $result];
    }
}

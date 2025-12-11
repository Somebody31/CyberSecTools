<?php
require_once __DIR__ . '/../models/NameserverSitesModel.php';

class NameserverSitesController {
    private $model;
    
    public function __construct($mysqli) {
        $this->model = new NameserverSitesModel($mysqli);
    }
    
    public function handleRequest($nameserver) {
        if (empty($nameserver)) {
            return ['response' => ['error' => 'Nameserver is required.']];
        }
        
        $result = $this->model->getSites($nameserver);
        return ['response' => $result];
    }
}

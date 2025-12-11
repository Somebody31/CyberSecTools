<?php
require_once __DIR__ . '/../models/TracerouteModel.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../security/SecurityUtils.php';
require_once __DIR__ . '/../views/jsonView.php';

class TracerouteController {
    private $model;
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
        $this->model = new TracerouteModel($mysqli);
    }
    
    public function handleRequest($host) {
        if (empty($host)) {
            return [
                'query' => ['tool' => 'traceroute', 'host' => $host],
                'response' => ['error' => 'Host parameter is required']
            ];
        }
        
        try {
            $result = $this->model->trace($host);
            return [
                'query' => ['tool' => 'traceroute', 'host' => $host],
                'response' => $result
            ];
        } catch (Exception $e) {
            error_log("Traceroute Error: " . $e->getMessage());
            return [
                'query' => ['tool' => 'traceroute', 'host' => $host],
                'response' => ['error' => 'An error occurred while processing your request']
            ];
        }
    }
}

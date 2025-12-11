<?php
require_once __DIR__ . '/../models/GlobalPingModel.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../security/SecurityUtils.php';
require_once __DIR__ . '/../views/jsonView.php';

class GlobalPingController {
    private $model;
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
        $this->model = new GlobalPingModel($mysqli);
    }
    
    public function handleRequest($host) {
        if (empty($host)) {
            return [
                'query' => ['tool' => 'global-ping', 'host' => ''],
                'response' => [['Error' => 'Host parameter is required']]
            ];
        }
        
        try {
            $result = $this->model->pingHost($host);
            return [
                'query' => ['tool' => 'global-ping', 'host' => $host],
                'response' => $result
            ];
        } catch (Exception $e) {
            error_log("Global Ping Error: " . $e->getMessage());
            return [
                'query' => ['tool' => 'global-ping', 'host' => $host],
                'response' => [['Error' => 'An error occurred while processing your request']]
            ];
        }
    }

    public function addPing($host, $result) {
        return $this->model->addPing($host, $result);
    }
    
    public function updatePing($host, $result) {
        return $this->model->updatePing($host, $result);
    }
    
    public function deletePing($host) {
        return $this->model->deletePing($host);
    }
}

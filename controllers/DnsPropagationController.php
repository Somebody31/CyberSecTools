<?php
require_once __DIR__ . '/../models/DnsPropagationModel.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../security/SecurityUtils.php';
require_once __DIR__ . '/../views/jsonView.php';

class DnsPropagationController {
    private $model;
    private $mysqli;

    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
        $this->model = new DnsPropagationModel($mysqli);
    }

    public function handleRequest($domain, $type = 'A') {
        if (empty($domain)) {
            return [
                'query' => ['tool' => 'dns-propagation', 'domain' => $domain, 'type' => $type],
                'response' => ['error' => 'Domain parameter is required']
            ];
        }

        try {
            $result = $this->model->check($domain, $type);
            return [
                'query' => ['tool' => 'dns-propagation', 'domain' => $domain, 'type' => $type],
                'response' => $result
            ];
        } catch (Exception $e) {
            error_log("DNS Propagation Error: " . $e->getMessage());
            return [
                'query' => ['tool' => 'dns-propagation', 'domain' => $domain, 'type' => $type],
                'response' => ['error' => 'An error occurred while processing your request']
            ];
        }
    }
}

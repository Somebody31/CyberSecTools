<?php
require_once __DIR__ . '/../models/ReverseDnsModel.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../security/SecurityUtils.php';
require_once __DIR__ . '/../views/jsonView.php';

class ReverseDnsController {
    private $model;
    private $mysqli;

    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
        $this->model = new ReverseDnsModel($mysqli);
    }

    public function handleRequest($ip) {
        if (empty($ip)) {
            return [
                'query' => ['tool' => 'reverse-dns', 'ip' => $ip],
                'response' => ['error' => 'IP address parameter is required']
            ];
        }

        try {
            $result = $this->model->reverseLookup($ip);
            return [
                'query' => ['tool' => 'reverse-dns', 'ip' => $ip],
                'response' => $result
            ];
        } catch (Exception $e) {
            error_log("Reverse DNS Error: " . $e->getMessage());
            return [
                'query' => ['tool' => 'reverse-dns', 'ip' => $ip],
                'response' => ['error' => 'An error occurred while processing your request']
            ];
        }
    }
}

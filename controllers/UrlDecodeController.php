<?php
require_once __DIR__ . '/../models/UrlDecodeModel.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../security/SecurityUtils.php';
require_once __DIR__ . '/../views/jsonView.php';

class UrlDecodeController {
    private $model;
    private $mysqli;

    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
        $this->model = new UrlDecodeModel($mysqli);
    }

    public function handleRequest($url) {
        $result = $this->model->decode($url);
        return [
            'query' => ['tool' => 'url-decode', 'url' => $url],
            'response' => $result
        ];
    }
}

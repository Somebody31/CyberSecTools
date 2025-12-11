<?php
require_once __DIR__ . '/../models/HttpHeadersModel.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../security/SecurityUtils.php';
require_once __DIR__ . '/../views/jsonView.php';

class HttpHeadersController {
    private $model;
    private $mysqli;

    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
        $this->model = new HttpHeadersModel($mysqli);
    }

    public function handleRequest($url) {
        $result = $this->model->getHeaders($url);
        return [
            'query' => ['tool' => 'http-headers', 'url' => $url],
            'response' => $result
        ];
    }

    public function addHeaders($url, $result) {
        return $this->model->addHeaders($url, $result);
    }
    public function updateHeaders($url, $result) {
        return $this->model->updateHeaders($url, $result);
    }
    public function deleteHeaders($url) {
        return $this->model->deleteHeaders($url);
    }
}

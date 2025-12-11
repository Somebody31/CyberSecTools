<?php
require_once __DIR__ . '/../models/MacLookupModel.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../security/SecurityUtils.php';
require_once __DIR__ . '/../views/jsonView.php';

class MacLookupController {
    private $model;
    private $mysqli;

    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
        $this->model = new MacLookupModel($mysqli);
    }

    public function handleRequest($mac) {
        $result = $this->model->lookupMac($mac);
        return [
            'query' => ['tool' => 'mac-lookup', 'mac' => $mac],
            'response' => $result
        ];
    }

    public function addMac($mac, $result) {
        return $this->model->addMac($mac, $result);
    }
    public function updateMac($mac, $result) {
        return $this->model->updateMac($mac, $result);
    }
    public function deleteMac($mac) {
        return $this->model->deleteMac($mac);
    }
}

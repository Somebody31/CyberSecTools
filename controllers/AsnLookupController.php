<?php
require_once __DIR__ . '/../models/AsnLookupModel.php';

class AsnLookupController {
    private $model;
    
    public function __construct($mysqli) {
        $this->model = new AsnLookupModel($mysqli);
    }
    
    public function handleRequest($asnQuery) {
        if (empty($asnQuery)) {
            return [
                'query' => ['tool' => 'asn-lookup', 'asn' => ''],
                'response' => ['error' => 'ASN query parameter is required.']
            ];
        }
        
        try {
            $result = $this->model->lookupASN($asnQuery);
            return [
                'query' => ['tool' => 'asn-lookup', 'asn' => $asnQuery],
                'response' => $result
            ];
        } catch (Exception $e) {
            $errorCode = $e->getCode();
            if ($errorCode === 502) {
                http_response_code(502);
            } elseif ($errorCode === 500) {
                http_response_code(500);
            } else {
                http_response_code(500);
            }
            
            return [
                'query' => ['tool' => 'asn-lookup', 'asn' => $asnQuery],
                'response' => ['error' => $e->getMessage()]
            ];
        }
    }
}

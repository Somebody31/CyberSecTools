<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../security/SecurityUtils.php';

SecurityUtils::setSecurityHeaders();

require_once __DIR__ . '/../controllers/PortScannerController.php';
require_once __DIR__ . '/../views/jsonView.php';

$host = isset($_GET['host']) ? SecurityUtils::sanitizeInput(trim($_GET['host']), 'general', 255) : '';

$controller = new PortScannerController($mysqli);
$data = $controller->handleRequest($host);
log_lookup($mysqli, 'port-scanner', $host, !empty($data['response']['error']) ? $data['response']['error'] : null);
renderJson($data);
?> 
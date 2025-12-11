<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../security/SecurityUtils.php';

SecurityUtils::setSecurityHeaders();

require_once __DIR__ . '/../controllers/IpLocationController.php';
require_once __DIR__ . '/../views/jsonView.php';

$ip = isset($_GET['ip']) ? SecurityUtils::sanitizeInput(trim($_GET['ip']), 'ip', 45) : '';

$controller = new IpLocationController($mysqli);
$data = $controller->handleRequest($ip);
log_lookup($mysqli, 'ip-location', $ip, !empty($data['response']['error']) ? $data['response']['error'] : null);
renderJson($data);

<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../security/SecurityUtils.php';

SecurityUtils::setSecurityHeaders();

require_once __DIR__ . '/../controllers/UrlDecodeController.php';
require_once __DIR__ . '/../views/jsonView.php';

$input = isset($_GET['input']) ? SecurityUtils::sanitizeInput(trim($_GET['input']), 'general', 1000) : '';

$controller = new UrlDecodeController($mysqli);
$data = $controller->handleRequest($input);
log_lookup($mysqli, 'url-decode', $input, !empty($data['response']['error']) ? $data['response']['error'] : null);
renderJson($data);

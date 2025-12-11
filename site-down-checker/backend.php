<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../security/SecurityUtils.php';

SecurityUtils::setSecurityHeaders();

require_once __DIR__ . '/../controllers/SiteDownCheckerController.php';
require_once __DIR__ . '/../views/jsonView.php';

$url = isset($_GET['url']) ? SecurityUtils::sanitizeInput(trim($_GET['url']), 'url', 255) : '';

$controller = new SiteDownCheckerController($mysqli);
$data = $controller->handleRequest($url);
log_lookup($mysqli, 'site-down-checker', $url, !empty($data['response']['error']) ? $data['response']['error'] : null);
renderJson($data);
?>
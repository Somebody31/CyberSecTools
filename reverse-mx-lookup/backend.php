<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../security/SecurityUtils.php';

SecurityUtils::setSecurityHeaders();

require_once __DIR__ . '/../controllers/ReverseMxController.php';
require_once __DIR__ . '/../views/jsonView.php';

$mx = isset($_GET['mx']) ? SecurityUtils::sanitizeInput(trim($_GET['mx']), 'domain', 255) : '';
$download = isset($_GET['download']) ? strtolower(trim($_GET['download'])) : '';

$controller = new ReverseMxController($mysqli);
$data = $controller->handleRequest($mx);

if ($download === 'csv' && empty($data['response']['error'])) {
    $domains = $data['response']['domains'] ?? [];
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reverse-mx-' . ($mx ?: 'results') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Mailserver', $mx]);
    fputcsv($out, ['Domain Name']);
    foreach ($domains as $d) {
        fputcsv($out, [$d]);
    }
    fclose($out);
    exit;
}
log_lookup($mysqli, 'reverse-mx-lookup', $mx, !empty($data['response']['error']) ? $data['response']['error'] : null);
renderJson($data);
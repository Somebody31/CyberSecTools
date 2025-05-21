<?php
require_once __DIR__ . '/backend.php';
echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Get HTTP Headers</title>';
echo '<link rel="icon" type="image/png" href="/favicon.png">';
echo '<style>
    body { font-family: Segoe UI, Arial, sans-serif; background: #f6f8fa; margin: 0; }
    .container { max-width: 1150px; margin: 30px auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 32px 32px 24px 32px; }
    h1 { font-size: 2rem; font-weight: 600; margin-bottom: 8px; }
    .desc { color: #555; margin-bottom: 24px; }
    .search-box { display: flex; gap: 12px; margin-bottom: 24px; }
    .search-box input { flex: 1; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
    .search-box button { background: #7a0000; color: #fff; border: none; border-radius: 6px; padding: 0 28px; font-size: 1rem; font-weight: 600; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.04); transition: background 0.2s; }
    .search-box button:hover { background: #5a0000; }
    .tabs { display: flex; gap: 24px; border-bottom: 1px solid #e5e7eb; margin-bottom: 0; }
    .tab { padding: 12px 0; font-weight: 500; color: #222; cursor: pointer; border-bottom: 2px solid transparent; text-decoration: none; }
    .tab.active { border-bottom: 2px solid #7a0000; color: #7a0000; }
    .results-header { font-size: 1.2rem; font-weight: 500; margin: 24px 0 8px 0; }
    .results-count { color: #666; font-size: 1rem; margin-bottom: 8px; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th, td { text-align: left; padding: 18px 8px; }
    th { background: #f3f4f6; color: #444; font-weight: 600; border-bottom: 1px solid #e5e7eb; font-size: 1.05rem; }
    tr:nth-child(even) { background: #fafbfc; }
    tr:hover { background: #f1f5f9; }
    .icon-btn { opacity: 0.6; cursor: pointer; display: inline-block; font-size: 22px; margin-left: 8px; transition: opacity 0.2s; }
    .icon-btn:hover { opacity: 1; }
</style></head><body>';
echo '<div class="container">';
echo '<h1>Get HTTP Headers</h1>';
echo '<div class="desc">Analyze HTTP response headers from any website to evaluate security configurations, caching policies, and server information. Helps identify security misconfigurations and optimization opportunities.</div>';
echo '<form class="search-box" method="get">';
echo '<input type="text" name="url" placeholder="Enter URL (e.g. https://example.com)" value="' . (isset($_GET['url']) ? htmlspecialchars($_GET['url']) : '') . '" required />';
echo '<button type="submit">Get Headers</button>';
echo '</form>';
if (isset($_GET['url']) && trim($_GET['url']) !== '') {
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'html';
    $url = trim($_GET['url']);
    $html_active = $tab === 'html' ? 'active' : '';
    $json_active = $tab === 'json' ? 'active' : '';
    echo '<div class="tabs">';
    echo '<a href="?url=' . urlencode($url) . '&tab=html" class="tab ' . $html_active . '">HTML</a>';
    echo '<a href="?url=' . urlencode($url) . '&tab=json" class="tab ' . $json_active . '">JSON</a>';
    echo '</div>';
    echo '<div style="position:relative;box-shadow:0 2px 8px rgba(0,0,0,0.08);border-radius:0 0 12px 12px;background:#fff;padding:32px 32px 16px 32px;margin-bottom:32px;">';
    echo '<div style="position:absolute;top:24px;right:32px;display:flex;gap:12px;">';
    echo '<span title="Copy" class="icon-btn">📋</span>';
    echo '<span title="Download" class="icon-btn">⬇️</span>';
    echo '</div>';
    // Use backend logic
    $headers = get_http_headers($url);
    $count = is_array($headers) ? count($headers) : 0;
    echo '<div class="results-header" style="margin-top:0;">HTTP headers for <b>' . htmlspecialchars($url) . '</b></div>';
    echo '<div class="results-count">Found ' . $count . ' headers.</div>';
    if ($tab === 'json') {
        $json = [
            "query" => ["tool" => "http-headers", "url" => $url],
            "response" => [
                "header_count" => strval($count),
                "headers" => $headers
            ]
        ];
        $json_str = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        echo '<pre style="background:#f3f4f6;padding:16px 16px 56px 16px;border-radius:8px;overflow:auto;max-height:320px;font-size:1rem;box-shadow:none;margin:0;">' . htmlspecialchars($json_str) . '</pre>';
    } else {
        echo '<table style="margin-top:0;"><tr><th>Header</th><th>Value</th></tr>';
        if (is_array($headers)) {
            foreach ($headers as $row) {
                echo '<tr><td>' . htmlspecialchars($row['header']) . '</td><td>' . htmlspecialchars($row['value']) . '</td></tr>';
            }
        }
        echo '</table>';
    }
    echo '</div>';
}
echo '</div>';
echo '</body></html>';
?>

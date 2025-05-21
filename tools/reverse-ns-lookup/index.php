<?php
// index.php - Reverse NS Lookup Tool (PHP only, no external CSS/JS/HTML)
// UI inspired by reverse-mx-lookup/index.php

echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Reverse NS Lookup</title>';
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
    .results-count { color: #666; font-size: 1rem; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th, td { text-align: left; padding: 18px 8px; }
    th { background: #f3f4f6; color: #444; font-weight: 600; border-bottom: 1px solid #e5e7eb; font-size: 1.05rem; }
    tr:nth-child(even) { background: #fafbfc; }
    tr:hover { background: #f1f5f9; }
    .domain { font-weight: 500; font-size: 1.1rem; }
    .icon-btn { opacity: 0.6; cursor: pointer; display: inline-block; font-size: 22px; margin-left: 8px; transition: opacity 0.2s; }
    .icon-btn:hover { opacity: 1; }
</style></head><body>';

echo '<div class="container">';
echo '<h1>Reverse NS Lookup</h1>';
echo '<div class="desc">Takes a nameserver (e.g. ns.example.com) and quickly shows all other domains that share the same nameserver. Useful for identifying other domains that share the same web server or hosting company.</div>';
echo '<form class="search-box" method="get">';
echo '<input type="text" name="ns" placeholder="Enter name server (e.g. ns1.google.com)" value="' . (isset($_GET['ns']) ? htmlspecialchars($_GET['ns']) : '') . '" required />';
echo '<button type="submit">Lookup</button>';
echo '</form>';

if (isset($_GET['ns']) && trim($_GET['ns']) !== '') {
    require_once __DIR__ . '/backend.php';
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'html';
    $ns_query = trim($_GET['ns']);
    $html_active = $tab === 'html' ? 'active' : '';
    $json_active = $tab === 'json' ? 'active' : '';
    echo '<div style="background:#fff;border-radius:12px 12px 0 0;box-shadow:0 2px 8px rgba(0,0,0,0.08);padding:0 32px 0 32px;margin-bottom:0;">';
    echo '<div class="tabs">';
    echo '<a href="?ns=' . urlencode($ns_query) . '&tab=html" class="tab ' . $html_active . '">HTML</a>';
    echo '<a href="?ns=' . urlencode($ns_query) . '&tab=json" class="tab ' . $json_active . '">JSON</a>';
    echo '</div>';
    echo '</div>';

    echo '<div style="position:relative;box-shadow:0 2px 8px rgba(0,0,0,0.08);border-radius:0 0 12px 12px;background:#fff;padding:32px 32px 16px 32px;margin-bottom:32px;">';
    echo '<div style="position:absolute;top:24px;right:32px;display:flex;gap:12px;">';
    echo '<span title="Copy" class="icon-btn">📋</span>';
    echo '<span title="Download" class="icon-btn">⬇️</span>';
    echo '</div>';

    // Use backend logic
    $domains = get_reverse_ns_domains($ns_query);
    $count = count($domains);
    echo '<div class="results-header" style="margin-top:0;">Reverse NS results for ' . htmlspecialchars($ns_query) . '</div>';
    echo '<div class="results-count">There are ' . $count . ' domains using this nameserver.</div>';
    if ($tab === 'json') {
        $json = [
            "query" => ["tool" => "reverse-ns-lookup", "nameserver" => $ns_query],
            "response" => [
                "domain_count" => strval($count),
                "domains" => $domains
            ]
        ];
        $json_str = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        echo '<pre style="background:#f3f4f6;padding:16px 16px 56px 16px;border-radius:8px;overflow:auto;max-height:320px;font-size:1rem;box-shadow:none;margin:0;">' . htmlspecialchars($json_str) . '</pre>';
    } else {
        echo '<table style="margin-top:0;"><tr><th style="background:#f3f4f6;font-size:0.95rem;color:#6b7280;font-weight:500;letter-spacing:0.04em;">DOMAIN NAME</th></tr>';
        foreach ($domains as $domain) {
            echo '<tr><td class="domain">' . htmlspecialchars($domain) . '</td></tr>';
        }
        echo '</table>';
    }
    echo '</div>';
}
echo '</div>';
echo '</body></html>';
?>

<?php
// Modern UI for Port Scanner Tool, inspired by reverse-mx-lookup
require_once __DIR__ . '/backend.php';

function render_header() {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Port Scanner</title>';
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
        pre { background: #f3f4f6; padding: 16px; border-radius: 8px; overflow: auto; max-height: 320px; font-size: 1rem; box-shadow: none; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { text-align: left; padding: 12px 8px; }
        th { background: #f3f4f6; color: #444; font-weight: 600; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) { background: #fafbfc; }
        tr:hover { background: #f1f5f9; }
    </style></head><body>';
}

function render_footer() {
    echo '</body></html>';
}

function render_main() {
    $query = isset($_GET['host']) ? trim($_GET['host']) : '';
    $ports = isset($_GET['ports']) ? trim($_GET['ports']) : '';
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'html';
    $scan_results = $query !== '' && $ports !== '' ? [] : [];
    echo '<div class="container">';
    echo '<h1>Port Scanner</h1>';
    echo '<div class="desc">Scan open TCP ports on a host. Useful for network troubleshooting and security analysis.</div>';
    echo '<form class="search-box" method="get">';
    echo '<input type="text" name="host" placeholder="Enter host or IP (e.g. example.com)" value="' . htmlspecialchars($query) . '" required />';
    echo '<input type="text" name="ports" placeholder="Ports (e.g. 22,80,443)" value="' . htmlspecialchars($ports) . '" required />';
    echo '<button type="submit">Scan</button>';
    echo '</form>';
    if ($query !== '' && $ports !== '') {
        $html_active = $tab === 'html' ? 'active' : '';
        $json_active = $tab === 'json' ? 'active' : '';
        $tab_query = htmlspecialchars($query);
        $tab_ports = htmlspecialchars($ports);
        echo '<div class="tabs">';
        echo '<a href="?host=' . urlencode($tab_query) . '&ports=' . urlencode($tab_ports) . '&tab=html" class="tab ' . $html_active . '">HTML</a>';
        echo '<a href="?host=' . urlencode($tab_query) . '&ports=' . urlencode($tab_ports) . '&tab=json" class="tab ' . $json_active . '">JSON</a>';
        echo '</div>';
        echo '<div style="position:relative;box-shadow:0 2px 8px rgba(0,0,0,0.08);border-radius:0 0 12px 12px;background:#fff;padding:32px 32px 16px 32px;margin-bottom:32px;">';
        echo '<div class="results-header" style="margin-top:0;">Scan results for <b>' . htmlspecialchars($query) . '</b> on ports <b>' . htmlspecialchars($ports) . '</b></div>';
        if ($tab === 'json') {
            $json = [
                "query" => ["tool" => "port-scanner", "host" => $query, "ports" => $ports],
                "response" => ["results" => $scan_results]
            ];
            $json_str = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            echo '<pre>' . htmlspecialchars($json_str) . '</pre>';
        } else {
            echo '<table style="margin-top:0;"><tr><th>Port</th><th>Status</th></tr>';
            if (is_array($scan_results)) {
                foreach ($scan_results as $row) {
                    echo '<tr><td>' . htmlspecialchars($row['port']) . '</td><td>' . htmlspecialchars($row['status']) . '</td></tr>';
                }
            }
            echo '</table>';
        }
        echo '</div>';
    }
    echo '</div>';
}

render_header();
render_main();
render_footer();
?>

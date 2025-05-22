<?php
// index.php - Reverse MX Lookup Tool (PHP only, no external CSS/JS/HTML)
// Reads domains_mx.csv and displays a UI similar to the provided screenshot

require_once 'backend.php';

function render_header() {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Reverse MX Lookup</title>';
    echo '<link rel="icon" type="image/png" href="/favicon.png">';
    // Inline CSS for fonts, shadows, etc. (as PHP echo)
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
        th, td { text-align: left; padding: 12px 8px; }
        th { background: #f3f4f6; color: #444; font-weight: 600; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) { background: #fafbfc; }
        tr:hover { background: #f1f5f9; }
        .domain { font-weight: 500; }
    </style></head><body>';
}

function render_footer() {
    echo '</body></html>';
}

function render_main() {
    $query = isset($_GET['mx']) ? trim($_GET['mx']) : '';
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'html';
    $domains = read_domains_mx('domains_mx.csv');
    $backend_result = get_mx_and_filtered_domains($query, $domains);
    $mx_domain = $backend_result['mx_domain'];
    $mx_hosts = $backend_result['mx_hosts'];
    $error = $backend_result['error'];
    $filtered = $backend_result['filtered'];
    $show_results = $backend_result['show_results'];

    echo '<div class="container">';
    echo '<h1>Reverse MX Lookup</h1>';
    echo '<div class="desc">Takes a mail server (e.g. mail.google.com) and quickly shows all other domains that use the same mail server. Useful for identifying domains that are used as email aliases.</div>';
    echo '<form class="search-box" method="get">';
    echo '<input type="text" name="mx" placeholder="Enter mail server (e.g. mail.google.com)" value="' . htmlspecialchars($query) . '" required />';
    echo '<button type="submit">Lookup</button>';
    echo '</form>';
    if ($query !== '') {
        if ($error) {
            echo '<div style="color:#b91c1c;background:#fee2e2;padding:12px 16px;border-radius:6px;margin-bottom:16px;font-weight:500;">' . $error . '</div>';
        }
        if ($show_results) {
            $html_active = $tab === 'html' ? 'active' : '';
            $json_active = $tab === 'json' ? 'active' : '';
            $tab_query = htmlspecialchars($query);
            echo '<div class="tabs" style="background:#fff;border-radius:12px 12px 0 0;box-shadow:0 2px 8px rgba(0,0,0,0.08);padding:0 32px;">';
            echo '<a href="?mx=' . urlencode($tab_query) . '&tab=html" class="tab ' . $html_active . '" style="margin-right:24px;">HTML</a>';
            echo '<a href="?mx=' . urlencode($tab_query) . '&tab=json" class="tab ' . $json_active . '">JSON</a>';
            echo '</div>';
            echo '<div style="position:relative;box-shadow:0 2px 8px rgba(0,0,0,0.08);border-radius:0 0 12px 12px;background:#fff;padding:32px 32px 16px 32px;margin-bottom:32px;">';
            echo '<div style="position:absolute;top:24px;right:32px;display:flex;gap:12px;">';
            echo '<span title="Copy" style="opacity:0.4;cursor:not-allowed;display:inline-block;font-size:20px;">📋</span>';
            echo '<span title="Download" style="opacity:0.4;cursor:not-allowed;display:inline-block;font-size:20px;">⬇️</span>';
            echo '</div>';
            echo '<div class="results-header" style="margin-top:0;">Reverse MX results for \'' . $mx_domain . '\'</div>';
            echo '<div class="results-count" style="margin-bottom:24px;">There are ' . count($filtered) . ' domains using this mail server. These are listed below.</div>';
            if ($tab === 'json') {
                $json = [
                    "query" => [
                        "tool" => "reverse-mx-lookup",
                        "mailserver" => $query
                    ],
                    "response" => [
                        "domain_count" => strval(count($filtered)),
                        "total_pages" => "1",
                        "current_page" => "1",
                        "domains" => $filtered
                    ]
                ];
                $json_str = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                echo '<div style="position:relative;">';
                echo '<pre style="background:#f3f4f6;padding:16px 16px 56px 16px;border-radius:8px;overflow:auto;max-height:320px;font-size:1rem;box-shadow:none;margin:0;">';
                echo htmlspecialchars($json_str);
                echo '</pre>';
                echo '</div>';
            } else {
                echo '<table style="margin-top:0;"><tr><th style="background:#f3f4f6;font-size:0.95rem;color:#6b7280;font-weight:500;letter-spacing:0.04em;">DOMAIN NAME</th></tr>';
                foreach ($filtered as $domain) {
                    echo '<tr><td class="domain">' . htmlspecialchars($domain) . '</td></tr>';
                }
                echo '</table>';
            }
            echo '</div>';
        }
    }
    echo '</div>';
}

render_header();
render_main();
render_footer();
?>
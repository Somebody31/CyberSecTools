<?php
// Modern UI for IP Location Finder, inspired by reverse-mx-lookup and the provided screenshot
require_once __DIR__ . '/backend.php';

function render_header() {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>IP Location Finder</title>';
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
        .results-table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        .results-table th, .results-table td { text-align: left; padding: 12px 8px; border-bottom: 1px solid #e5e7eb; }
        .results-table th { background: #f3f4f6; color: #444; font-weight: 600; }
        .results-table tr:last-child td { border-bottom: none; }
        .map-container { width: 100%; height: 320px; border-radius: 8px; overflow: hidden; margin-bottom: 16px; background: #e5e7eb; display: flex; align-items: center; justify-content: center; }
        .icon-btn { opacity: 0.6; cursor: pointer; display: inline-block; font-size: 22px; margin-left: 8px; transition: opacity 0.2s; }
        .icon-btn:hover { opacity: 1; }
    </style>';
    // Optionally, you could add Leaflet CSS/JS here if you want a real map, but for now, use a placeholder div
    echo '</head><body>';
}

function render_footer() {
    echo '</body></html>';
}

function render_main() {
    $query = isset($_GET['ip']) ? trim($_GET['ip']) : '';
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'html';
    // TODO: Call backend logic to get $result
    $result = null;
    echo '<div class="container">';
    echo '<h1>IP Location Finder</h1>';
    echo '<div class="desc">This tool will display geographic information about a supplied IP address including city, country, latitude, longitude and more.</div>';
    echo '<form class="search-box" method="get">';
    echo '<input type="text" name="ip" placeholder="Enter IP address" value="' . htmlspecialchars($query) . '" required />';
    echo '<button type="submit">Find</button>';
    echo '</form>';
    if ($query !== '') {
        $html_active = $tab === 'html' ? 'active' : '';
        $json_active = $tab === 'json' ? 'active' : '';
        $tab_query = htmlspecialchars($query);
        echo '<div class="tabs">';
        echo '<a href="?ip=' . urlencode($tab_query) . '&tab=html" class="tab ' . $html_active . '">HTML</a>';
        echo '<a href="?ip=' . urlencode($tab_query) . '&tab=json" class="tab ' . $json_active . '">JSON</a>';
        echo '</div>';
        echo '<div style="position:relative;box-shadow:0 2px 8px rgba(0,0,0,0.08);border-radius:0 0 12px 12px;background:#fff;padding:32px 32px 16px 32px;margin-bottom:32px;">';
        echo '<div style="position:absolute;top:24px;right:32px;display:flex;gap:12px;">';
        echo '<span title="Copy" class="icon-btn">📋</span>';
        echo '<span title="Download" class="icon-btn">⬇️</span>';
        echo '</div>';
        echo '<div class="results-header" style="margin-top:0;">IP Location Results for ' . htmlspecialchars($query) . '</div>';
        if ($tab === 'json') {
            $json = [
                "query" => ["tool" => "ip-location", "ip" => $query],
                "response" => $result
            ];
            $json_str = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            echo '<pre>' . htmlspecialchars($json_str) . '</pre>';
        } else if ($result) {
            // Map placeholder (replace with real map if needed)
            echo '<div class="map-container">';
            echo '<img src="https://static-maps.yandex.ru/1.x/?lang=en-US&ll=' . $result['longitude'] . ',' . $result['latitude'] . '&z=4&l=map&size=650,320&pt=' . $result['longitude'] . ',' . $result['latitude'] . ',pm2rdm" alt="Map" style="width:100%;height:100%;object-fit:cover;border-radius:8px;" />';
            echo '</div>';
            echo '<table class="results-table">';
            echo '<tr><th>City</th><td>' . htmlspecialchars($result['city']) . '</td></tr>';
            echo '<tr><th>Zip Code</th><td>' . htmlspecialchars($result['zip']) . '</td></tr>';
            echo '<tr><th>Region Code</th><td>' . htmlspecialchars($result['region_code']) . '</td></tr>';
            echo '<tr><th>Region Name</th><td>' . htmlspecialchars($result['region_name']) . '</td></tr>';
            echo '<tr><th>Country Code</th><td>' . htmlspecialchars($result['country_code']) . '</td></tr>';
            echo '<tr><th>Country Name</th><td>' . htmlspecialchars($result['country_name']) . '</td></tr>';
            echo '<tr><th>Latitude</th><td>' . htmlspecialchars($result['latitude']) . '</td></tr>';
            echo '<tr><th>Longitude</th><td>' . htmlspecialchars($result['longitude']) . '</td></tr>';
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

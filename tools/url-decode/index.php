<?php
// Modern UI for URL Decode Tool, inspired by reverse-mx-lookup
require_once __DIR__ . '/backend.php';

function render_header() {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>URL Decode</title>';
    echo '<link rel="icon" type="image/png" href="/favicon.png">';
    echo '<style>
        body { font-family: Segoe UI, Arial, sans-serif; background: #f6f8fa; margin: 0; }
        .container { max-width: 1150px; margin: 30px auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 32px 32px 24px 32px; }
        h1 { font-size: 2rem; font-weight: 600; margin-bottom: 8px; }
        .desc { color: #555; margin-bottom: 24px; }
        .search-box { display: flex; gap: 12px; margin-bottom: 24px; }
        .search-box input, .search-box textarea { flex: 1; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
        .search-box button { background: #7a0000; color: #fff; border: none; border-radius: 6px; padding: 0 28px; font-size: 1rem; font-weight: 600; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.04); transition: background 0.2s; }
        .search-box button:hover { background: #5a0000; }
        .tabs { display: flex; gap: 24px; border-bottom: 1px solid #e5e7eb; margin-bottom: 0; }
        .tab { padding: 12px 0; font-weight: 500; color: #222; cursor: pointer; border-bottom: 2px solid transparent; text-decoration: none; }
        .tab.active { border-bottom: 2px solid #7a0000; color: #7a0000; }
        .results-header { font-size: 1.2rem; font-weight: 500; margin: 24px 0 8px 0; }
        .results-count { color: #666; font-size: 1rem; margin-bottom: 16px; }
        pre { background: #f3f4f6; padding: 16px; border-radius: 8px; overflow: auto; max-height: 320px; font-size: 1rem; box-shadow: none; margin: 0; }
    </style></head><body>';
}

function render_footer() {
    echo '</body></html>';
}

function render_main() {
    $query = isset($_GET['url']) ? trim($_GET['url']) : '';
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'html';
    $decoded = $query !== '' ? urldecode($query) : '';
    echo '<div class="container">';
    echo '<h1>URL Decode</h1>';
    echo '<div class="desc">Decode percent-encoded URLs or strings. Useful for analyzing encoded web data or debugging URL issues.</div>';
    echo '<form class="search-box" method="get">';
    echo '<input type="text" name="url" placeholder="Paste encoded URL or string here" value="' . htmlspecialchars($query) . '" required />';
    echo '<button type="submit">Decode</button>';
    echo '</form>';
    if ($query !== '') {
        $html_active = $tab === 'html' ? 'active' : '';
        $json_active = $tab === 'json' ? 'active' : '';
        $tab_query = htmlspecialchars($query);
        echo '<div class="tabs">';
        echo '<a href="?url=' . urlencode($tab_query) . '&tab=html" class="tab ' . $html_active . '">HTML</a>';
        echo '<a href="?url=' . urlencode($tab_query) . '&tab=json" class="tab ' . $json_active . '">JSON</a>';
        echo '</div>';
        echo '<div style="position:relative;box-shadow:0 2px 8px rgba(0,0,0,0.08);border-radius:0 0 12px 12px;background:#fff;padding:32px 32px 16px 32px;margin-bottom:32px;">';
        echo '<div class="results-header" style="margin-top:0;">Decoded result</div>';
        if ($tab === 'json') {
            $json = [
                "query" => ["tool" => "url-decode", "input" => $query],
                "response" => ["decoded" => $decoded]
            ];
            $json_str = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            echo '<pre>' . htmlspecialchars($json_str) . '</pre>';
        } else {
            echo '<pre>' . htmlspecialchars($decoded) . '</pre>';
        }
        echo '</div>';
    }
    echo '</div>';
}

render_header();
render_main();
render_footer();
?>

<?php
// index.php - IP History Tool (PHP only, no external CSS/JS/HTML)
// UI inspired by reverse-mx-lookup/index.php and the provided screenshot

echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>IP History</title>';
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
</style></head><body>';

echo '<div class="container">';
echo '<h1>IP History</h1>';
echo '<div class="desc">Shows a historical list of IP addresses a given domain name has been hosted on as well as where that IP address is geographically located, and the owner of that IP address.</div>';
echo '<form class="search-box" method="get">';
echo '<input type="text" name="domain" placeholder="Enter domain" value="' . (isset($_GET['domain']) ? htmlspecialchars($_GET['domain']) : '') . '" required />';
echo '<button type="submit">Lookup</button>';
echo '</form>';
echo '</div>';
echo '</body></html>';
?>
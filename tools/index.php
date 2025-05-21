<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cyber Jagrithi - Comprehensive Security & Network Analysis Tools</title>
    <meta name="description" content="Cyber Jagrithi offers a comprehensive suite of security and network analysis tools including IP lookups, DNS analysis, firewall tests, and security assessments for cybersecurity professionals.">
    <meta name="keywords" content="cybersecurity tools, network analysis, IP lookup, DNS report, whois lookup, security assessment, cyber tools, web security">
    <meta name="author" content="Cyber Jagrithi">
    <meta property="og:title" content="Cyber Jagrithi - Security & Network Analysis Tools">
    <meta property="og:description" content="Access professional-grade cybersecurity and network analysis tools for comprehensive security assessments and threat intelligence.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://cyberjagrithi.com/tools/">
    <link rel="canonical" href="https://cyberjagrithi.com/tools/">
    <link rel="icon" type="image/png" href="/favicon.png">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap');
        :root {
            --primary-color: #800000;
            --secondary-color: #5c0000;
            --accent-color: #a52a2a;
            --light-color: #f3f4f6;
            --dark-color: #2b0000;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Roboto', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f9fafb; color: var(--dark-color); line-height: 1.6; }
        header { background-color: var(--primary-color); color: white; padding: 2rem 1rem; text-align: center; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        .tools-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; margin-top: 2rem; }
        .tool-card { background-color: white; border-radius: 8px; padding: 1.5rem; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); transition: all 0.3s ease; border-left: 4px solid var(--accent-color); display: flex; flex-direction: column; height: 100%; }
        .tool-card:hover { transform: translateY(-5px); box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1); border-left: 4px solid var(--secondary-color); }
        .tool-card h3 { color: var(--primary-color); margin-bottom: 0.5rem; }
        .tool-card p { color: #6b7280; font-size: 0.9rem; margin-bottom: 1rem; flex-grow: 1; }
        .tool-btn { background-color: var(--primary-color); color: white; border: none; padding: 0.6rem 1rem; border-radius: 6px; font-weight: 500; cursor: pointer; transition: background-color 0.2s; text-align: center; text-decoration: none; display: inline-block; margin-top: auto; }
        .tool-btn:hover { background-color: var(--secondary-color); }
        .search-bar { margin: 2rem 0; width: 100%; max-width: 600px; margin-left: auto; margin-right: auto; }
        .search-bar input { width: 100%; padding: 0.75rem 1rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem; outline: none; }
        .search-bar input:focus { border-color: var(--accent-color); box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }
        footer { background-color: var(--dark-color); color: white; text-align: center; padding: 1.5rem; margin-top: 3rem; }
        .category-title { margin: 2rem 0 1rem; color: var(--secondary-color); border-bottom: 2px solid var(--accent-color); padding-bottom: 0.5rem; display: inline-block; }
        h2 { color: #800000; margin: 2rem 0 1rem; font-size: 1.8rem; }
        .section-description { color: #4b5563; margin-bottom: 2rem; max-width: 800px; line-height: 1.6; }
        section { margin-bottom: 3rem; }
        .hidden-seo { display: none; }
        @media (max-width: 768px) { .tools-grid { grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); } }
    </style>
</head>
<body>
    <!-- Structured data for SEO -->
    <script type="application/ld+json" class="hidden-seo">
    {
        "@context": "https://schema.org",
        "@type": "ItemList",
        "itemListElement": [
            { "@type": "ListItem", "position": 1, "name": "Reverse IP Lookup", "description": "Find all websites hosted on a specific IP address", "url": "https://cyberjagrithi.com/tools/reverse-ip-lookup" },
            { "@type": "ListItem", "position": 2, "name": "WHOIS Lookup", "description": "Access comprehensive domain registration details", "url": "https://cyberjagrithi.com/tools/whois-lookup" },
            { "@type": "ListItem", "position": 3, "name": "DNS Record Lookup", "description": "Query and analyze all DNS records for any domain", "url": "https://cyberjagrithi.com/tools/dns-lookup" }
        ]
    }
    </script>
    <script type="application/ld+json" class="hidden-seo">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "Cyber Jagrithi",
        "url": "https://cyberjagrithi.com",
        "logo": "https://cyberjagrithi.com/images/logo.png",
        "description": "Providing advanced cybersecurity tools and resources for security professionals and organizations worldwide."
    }
    </script>
    <header>
        <h1>Cyber Jagrithi</h1>
        <p>Comprehensive Security & Network Analysis Tools</p>
        <div style="margin-top: 1rem; max-width: 800px; margin-left: auto; margin-right: auto;">
            <p style="font-size: 0.95rem; opacity: 0.9;">
                Welcome to Cyber Jagrithi's professional suite of cybersecurity tools. Our collection helps security professionals, network administrators, and digital investigators analyze networks, assess vulnerabilities, and enhance online security posture.
            </p>
        </div>
    </header>
    <div class="container">
        <form class="search-bar" method="get" action="">
            <input type="text" name="q" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q'], ENT_QUOTES, 'UTF-8') : ''; ?>" placeholder="Search for tools..." aria-label="Search for security tools" autocomplete="off">
        </form>
        <section>
            <h2 id="tools-heading">Cybersecurity & Network Analysis Tools</h2>
            <p class="section-description">Explore our comprehensive collection of professional-grade tools designed for security assessments, network analysis, and digital investigations.</p>
            <div class="tools-grid" id="tools-container" aria-labelledby="tools-heading">
                <?php
                // Tool data array
                $tools = [
                    ["Reverse IP Lookup", "Discover all websites hosted on a single IP address. Useful for identifying related websites, investigating web hosting infrastructure, and uncovering potentially malicious domain clusters.", "reverse-ip-lookup/index.php"],
                    ["Reverse Whois Lookup", "Identify all domains registered by a specific individual or organization. Essential for brand protection, competitive analysis, and investigating potential phishing campaigns targeting your organization.", "reverse-whois-lookup/index.php"],
                    ["IP History", "Track the historical hosting changes of a domain over time. Helps identify suspicious hosting migrations, investigate compromised websites, and analyze the digital footprint of an organization.", "ip-history/index.php"],
                    ["DNS Report", "Generate a comprehensive analysis of all DNS records for a domain with security recommendations. Identifies misconfiguration issues, security vulnerabilities, and compliance problems in DNS settings.", "dns-report/index.php"],
                    ["Reverse MX Lookup", "Find all domains sharing the same mail server. Useful for identifying related organizations, investigating email infrastructure, and discovering potential email spoofing vulnerabilities.", "reverse-mx-lookup/index.php"],
                    ["Reverse NS Lookup", "Discover all domains using the same nameserver. Helps map related digital assets, investigate DNS infrastructure, and identify potential DNS-based attack vectors.", "reverse-ns-lookup/index.php"],
                    ["Find all sites that use a given nameserver", "Comprehensive tool to list all websites using a specific nameserver. Valuable for infrastructure mapping, competitive analysis, and identifying potential security issues across DNS providers.", "nameserver-sites/index.php"],
                    ["IP Location Finder", "Accurately determine the geographic location of any IP address with detailed regional information. Essential for compliance verification, fraud investigation, and geotargeting configuration.", "ip-location/index.php"],
                    ["Chinese Firewall Test", "Verify if your website is accessible in China by testing against the Great Firewall. Critical for businesses operating in or targeting the Chinese market to ensure service availability.", "chinese-firewall-test/index.php"],
                    ["DNS Propagation Checker", "Monitor the global propagation status of DNS changes across multiple locations worldwide. Essential when migrating services, changing DNS providers, or troubleshooting DNS-related issues.", "dns-propagation/index.php"],
                    ["Is My Site Down", "Verify if your website is experiencing downtime or accessibility issues from multiple global locations. Helps differentiate between local connectivity problems and actual service outages.", "site-down-checker/index.php"],
                    ["Iran Firewall Test", "Test if your website is blocked by Iran's national firewall. Important for organizations with users in Iran to verify service accessibility and compliance with regional regulations.", "iran-firewall-test/index.php"],
                    ["WHOIS Lookup", "Access comprehensive domain registration details including owner information, registration dates, and nameservers. Essential for domain acquisition, legal investigations, and brand protection.", "whois-lookup/index.php"],
                    ["Get HTTP Headers", "Analyze HTTP response headers from any website to evaluate security configurations, caching policies, and server information. Helps identify security misconfigurations and optimization opportunities.", "http-headers/index.php"],
                    ["DNS Record Lookup", "Query and analyze all DNS records for any domain with detailed explanation of each record type. Vital for troubleshooting email delivery, website accessibility, and domain verification issues.", "dns-lookup/index.php"],
                    ["Port Scanner", "Identify open ports and services running on any IP address or domain. Essential for security assessments, vulnerability identification, and network infrastructure auditing.", "port-scanner/index.php"],
                    ["Traceroute", "Map the complete network path between your location and any destination server. Helps diagnose network latency issues, routing problems, and potential network bottlenecks.", "traceroute/index.php"],
                    ["Spam Database Lookup", "Check if an IP address or domain is listed in major spam blacklists and reputation databases. Critical for troubleshooting email delivery issues and monitoring organizational IP reputation.", "spam-database/index.php"],
                    ["Reverse DNS Lookup", "Identify the hostname associated with any IP address. Useful for network troubleshooting, email server configuration validation, and identifying potentially malicious servers.", "reverse-dns/index.php"],
                    ["ASN Lookup", "Retrieve detailed information about Autonomous System Numbers including IP ranges, routing policies, and organizational data. Essential for network mapping and investigating network-level threats.", "asn-lookup/index.php"],
                    ["Global Ping Test", "Measure network latency from multiple international locations to your server. Valuable for evaluating global performance, troubleshooting regional access issues, and CDN optimization.", "global-ping/index.php"],
                    ["DNSSEC Test", "Verify the proper implementation and validity of DNSSEC on any domain. Helps prevent DNS spoofing attacks and ensures the integrity of your domain's DNS infrastructure.", "dnssec-test/index.php"],
                    ["URL Decode", "Convert URL-encoded strings back to their original format. Useful for analyzing suspicious URLs, troubleshooting web application issues, and forensic investigation of web traffic.", "url-decode/index.php"],
                    ["Abuse Lookup", "Find the appropriate abuse contact information for any IP address or domain. Essential for reporting spam, network abuse, or security incidents to the responsible network operators.", "abuse-lookup/index.php"],
                    ["MAC Address Lookup", "Identify the manufacturer of network hardware from its MAC address. Useful for network inventory management, unauthorized device detection, and hardware authentication.", "mac-lookup/index.php"],
                    ["Free Email Test", "Verify if an email address belongs to a free email provider or a corporate domain. Helps with lead qualification, fraud prevention, and implementing appropriate email security policies.", "free-email-test/index.php"]
                ];
                $q = isset($_GET['q']) ? trim($_GET['q']) : '';
                $filtered = [];
                if ($q !== '') {
                    $q_lower = mb_strtolower($q);
                    foreach ($tools as $tool) {
                        if (mb_stripos(mb_strtolower($tool[0]), $q_lower) !== false || mb_stripos(mb_strtolower($tool[1]), $q_lower) !== false) {
                            $filtered[] = $tool;
                        }
                    }
                } else {
                    $filtered = $tools;
                }
                if (count($filtered) === 0) {
                    echo '<p style="grid-column: 1/-1; color: #a52a2a; font-weight: 500;">No tools found matching your search.</p>';
                } else {
                    foreach ($filtered as $tool) {
                        echo '<div class="tool-card">';
                        echo '<h3>' . htmlspecialchars($tool[0]) . '</h3>';
                        echo '<p>' . htmlspecialchars($tool[1]) . '</p>';
                        echo '<a href="' . htmlspecialchars($tool[2]) . '" class="tool-btn" aria-label="Use ' . htmlspecialchars($tool[0]) . ' tool">Use Tool</a>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
        </section>
    </div>
    <footer>
        <div style="max-width: 900px; margin: 0 auto; padding: 0 1rem;">
            <div style="display: flex; flex-wrap: wrap; justify-content: space-between; margin-bottom: 2rem;">
                <div style="flex: 1; min-width: 200px; margin-bottom: 1rem;">
                    <h3 style="margin-bottom: 1rem; font-size: 1.2rem;">Cyber Jagrithi</h3>
                    <p style="margin-bottom: 1rem; opacity: 0.8; font-size: 0.9rem;">Providing advanced cybersecurity tools and resources for security professionals, researchers, and organizations worldwide.</p>
                </div>
                <div style="flex: 1; min-width: 200px; margin-bottom: 1rem;">
                    <h3 style="margin-bottom: 1rem; font-size: 1.2rem;">Quick Links</h3>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 0.5rem;"><a href="/about" style="color: white; text-decoration: none; opacity: 0.8; font-size: 0.9rem;">About Us</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="/contact" style="color: white; text-decoration: none; opacity: 0.8; font-size: 0.9rem;">Contact</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="/blog" style="color: white; text-decoration: none; opacity: 0.8; font-size: 0.9rem;">Security Blog</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="/api" style="color: white; text-decoration: none; opacity: 0.8; font-size: 0.9rem;">API Access</a></li>
                    </ul>
                </div>
                <div style="flex: 1; min-width: 200px; margin-bottom: 1rem;">
                    <h3 style="margin-bottom: 1rem; font-size: 1.2rem;">Tool Categories</h3>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 0.5rem;"><a href="/categories/dns-tools" style="color: white; text-decoration: none; opacity: 0.8; font-size: 0.9rem;">DNS Tools</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="/categories/ip-tools" style="color: white; text-decoration: none; opacity: 0.8; font-size: 0.9rem;">IP Analysis</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="/categories/network-tools" style="color: white; text-decoration: none; opacity: 0.8; font-size: 0.9rem;">Network Testing</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="/categories/security-tools" style="color: white; text-decoration: none; opacity: 0.8; font-size: 0.9rem;">Security Assessment</a></li>
                    </ul>
                </div>
            </div>
            <hr style="border-color: rgba(255, 255, 255, 0.1); margin: 1rem 0;">
            <p style="text-align: center; font-size: 0.9rem;">&copy; 2025 Cyber Jagrithi. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
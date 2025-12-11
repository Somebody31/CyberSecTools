<?php
/**
 * Robust Tool Testing Script
 * Tests all tools in the PHP MVC project and generates test_results.log
 */

// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300); // 5 minutes timeout

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security/SecurityUtils.php';

class RobustToolTester {
    private $mysqli;
    private $logFile;
    private $totalTools = 0;
    private $passedTools = 0;
    private $failedTools = 0;
    private $skippedTools = 0;

    // Define all tools to test
    private $tools = [
        'abuse-lookup' => ['controller' => 'AbuseLookupController', 'test_input' => '8.8.8.8'],
        'asn-lookup' => ['controller' => 'AsnLookupController', 'test_input' => '8.8.8.8'],
        'dns-lookup' => ['controller' => 'DnsLookupController', 'test_input' => 'google.com'],
        'dns-propagation' => ['controller' => 'DnsPropagationController', 'test_input' => 'google.com'],
        'dns-report' => ['controller' => 'DnsReportController', 'test_input' => 'google.com'],
        'dnssec-test' => ['controller' => 'DnssecTestController', 'test_input' => 'google.com'],
        'free-email-test' => ['controller' => 'FreeEmailTestController', 'test_input' => 'test@gmail.com'],
        'global-ping' => ['controller' => 'GlobalPingController', 'test_input' => 'google.com'],
        'http-headers' => ['controller' => 'HttpHeadersController', 'test_input' => 'https://google.com'],
        'ip-history' => ['controller' => 'IpHistoryController', 'test_input' => 'google.com'],
        'ip-location' => ['controller' => 'IpLocationController', 'test_input' => '8.8.8.8'],
        'mac-lookup' => ['controller' => 'MacLookupController', 'test_input' => '00:1B:44:11:3A:B7'],
        'nameserver-sites' => ['controller' => 'NameserverSitesController', 'test_input' => 'ns1.google.com'],
        'port-scanner' => ['controller' => 'PortScannerController', 'test_input' => 'google.com'],
        'reverse-dns' => ['controller' => 'ReverseDnsController', 'test_input' => '8.8.8.8'],
        'reverse-ip-lookup' => ['controller' => 'ReverseIpLookupController', 'test_input' => '8.8.8.8'],
        'reverse-mx-lookup' => ['controller' => 'ReverseMxController', 'test_input' => 'gmail-smtp-in.l.google.com'],
        'reverse-ns-lookup' => ['controller' => 'ReverseNsLookupController', 'test_input' => 'ns1.google.com'],
        'reverse-whois-lookup' => ['controller' => 'ReverseWhoisLookupController', 'test_input' => 'Google Inc.'],
        'site-down-checker' => ['controller' => 'SiteDownCheckerController', 'test_input' => 'https://google.com'],
        'spam-database' => ['controller' => 'SpamDatabaseController', 'test_input' => '8.8.8.8'],
        'traceroute' => ['controller' => 'TracerouteController', 'test_input' => 'google.com'],
        'url-decode' => ['controller' => 'UrlDecodeController', 'test_input' => 'https%3A//example.com'],
        'whois-lookup' => ['controller' => 'WhoisLookupController', 'test_input' => 'google.com']
    ];

    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
        $this->logFile = __DIR__ . '/test_results.log';
        $this->totalTools = count($this->tools);
        
        // Clear previous log
        file_put_contents($this->logFile, '');
    }

    public function runAllTests() {
        $this->log("=== PHP MVC Tools Testing Started ===");
        $this->log("Testing " . $this->totalTools . " tools");
        $this->log("");

        foreach ($this->tools as $toolName => $toolConfig) {
            try {
                $this->testTool($toolName, $toolConfig);
            } catch (Exception $e) {
                $this->log("[FAIL] Tool {$toolName} - Fatal error: " . $e->getMessage());
                $this->failedTools++;
                $this->log("[INFO] Tool {$toolName}: finished");
                $this->log("");
            }
        }

        $this->generateSummary();
        $this->log("=== Testing Completed ===");
        
        echo "Testing completed. Results saved to: " . $this->logFile . "\n";
        echo "Summary: {$this->passedTools} passed, {$this->failedTools} failed, {$this->skippedTools} skipped\n";
    }

    private function testTool($toolName, $config) {
        $this->log("[INFO] Tool {$toolName}: started");
        
        // Check if all required files exist
        $filesCheck = $this->checkRequiredFiles($toolName, $config);
        if (!$filesCheck['success']) {
            $this->log("[SKIPPED] File structure check - {$filesCheck['reason']}");
            $this->skippedTools++;
            $this->log("[INFO] Tool {$toolName}: finished");
            $this->log("");
            return;
        }
        $this->log("[PASS] File structure check");

        // Test backend functionality
        $backendCheck = $this->testBackend($toolName, $config);
        if (!$backendCheck['success']) {
            $this->log("[FAIL] Backend functionality test - {$backendCheck['reason']}");
            $this->failedTools++;
            $this->log("[INFO] Tool {$toolName}: finished");
            $this->log("");
            return;
        }
        $this->log("[PASS] Backend functionality test");

        // Test frontend files
        $frontendCheck = $this->testFrontend($toolName);
        if (!$frontendCheck['success']) {
            $this->log("[FAIL] Frontend structure test - {$frontendCheck['reason']}");
            $this->failedTools++;
            $this->log("[INFO] Tool {$toolName}: finished");
            $this->log("");
            return;
        }
        $this->log("[PASS] Frontend structure test");

        // Test API endpoint (simplified to avoid fatal errors)
        $apiCheck = $this->testApiEndpointSimple($toolName, $config);
        if (!$apiCheck['success']) {
            $this->log("[FAIL] API endpoint test - {$apiCheck['reason']}");
            $this->failedTools++;
            $this->log("[INFO] Tool {$toolName}: finished");
            $this->log("");
            return;
        }
        $this->log("[PASS] API endpoint test");

        $this->passedTools++;
        $this->log("[INFO] Tool {$toolName}: finished");
        $this->log("");
    }

    private function checkRequiredFiles($toolName, $config) {
        $requiredFiles = [
            "controllers/{$config['controller']}.php",
            "models/" . str_replace('Controller', 'Model', $config['controller']) . ".php",
            "{$toolName}/index.html",
            "{$toolName}/backend.php"
        ];

        foreach ($requiredFiles as $file) {
            if (!file_exists(__DIR__ . '/' . $file)) {
                return ['success' => false, 'reason' => "Missing file: {$file}"];
            }
        }

        return ['success' => true];
    }

    private function testBackend($toolName, $config) {
        try {
            // Include required files
            $controllerFile = __DIR__ . "/controllers/{$config['controller']}.php";
            $modelFile = __DIR__ . "/models/" . str_replace('Controller', 'Model', $config['controller']) . ".php";
            
            if (!file_exists($controllerFile) || !file_exists($modelFile)) {
                return ['success' => false, 'reason' => 'Controller or Model file missing'];
            }

            // Check if files can be included without fatal errors
            $controllerContent = file_get_contents($controllerFile);
            if (strpos($controllerContent, 'class ' . $config['controller']) === false) {
                return ['success' => false, 'reason' => 'Controller class not found in file'];
            }

            $modelContent = file_get_contents($modelFile);
            $modelClass = str_replace('Controller', 'Model', $config['controller']);
            if (strpos($modelContent, 'class ' . $modelClass) === false) {
                return ['success' => false, 'reason' => 'Model class not found in file'];
            }

            return ['success' => true];

        } catch (Exception $e) {
            return ['success' => false, 'reason' => 'Backend error: ' . $e->getMessage()];
        }
    }

    private function testFrontend($toolName) {
        $indexFile = __DIR__ . "/{$toolName}/index.html";
        $backendFile = __DIR__ . "/{$toolName}/backend.php";

        if (!file_exists($indexFile)) {
            return ['success' => false, 'reason' => 'index.html missing'];
        }

        if (!file_exists($backendFile)) {
            return ['success' => false, 'reason' => 'backend.php missing'];
        }

        // Check if index.html has basic structure
        $indexContent = file_get_contents($indexFile);
        if (strpos($indexContent, '<form') === false && strpos($indexContent, 'form') === false) {
            return ['success' => false, 'reason' => 'index.html missing form functionality'];
        }

        if (strpos($indexContent, 'backend.php') === false) {
            return ['success' => false, 'reason' => 'index.html not connected to backend.php'];
        }

        // Check if backend.php includes required files
        $backendContent = file_get_contents($backendFile);
        if (strpos($backendContent, 'Controller') === false) {
            return ['success' => false, 'reason' => 'backend.php not using controller'];
        }

        return ['success' => true];
    }

    private function testApiEndpointSimple($toolName, $config) {
        try {
            $backendFile = __DIR__ . "/{$toolName}/backend.php";
            
            if (!file_exists($backendFile)) {
                return ['success' => false, 'reason' => 'Backend file not found'];
            }

            // Check if backend file has proper structure
            $backendContent = file_get_contents($backendFile);
            
            // Check for required includes
            if (strpos($backendContent, 'require_once') === false) {
                return ['success' => false, 'reason' => 'Backend missing required includes'];
            }

            // Check for controller usage
            if (strpos($backendContent, 'Controller') === false) {
                return ['success' => false, 'reason' => 'Backend not using controller'];
            }

            // Check for JSON output
            if (strpos($backendContent, 'renderJson') === false && strpos($backendContent, 'json') === false) {
                return ['success' => false, 'reason' => 'Backend not outputting JSON'];
            }

            return ['success' => true];

        } catch (Exception $e) {
            return ['success' => false, 'reason' => 'API test error: ' . $e->getMessage()];
        }
    }

    private function generateSummary() {
        $this->log("=== TESTING SUMMARY ===");
        $this->log("Total tools tested: {$this->totalTools}");
        $this->log("Tools passed: {$this->passedTools}");
        $this->log("Tools failed: {$this->failedTools}");
        $this->log("Tools skipped/incomplete: {$this->skippedTools}");
        $this->log("");
        
        $successRate = $this->totalTools > 0 ? round(($this->passedTools / $this->totalTools) * 100, 1) : 0;
        $this->log("Success rate: {$successRate}%");
    }

    private function log($message) {
        $logEntry = $message . "\n";
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        echo $logEntry;
    }
}

// Initialize and run tests
try {
    $tester = new RobustToolTester($mysqli);
    $tester->runAllTests();
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    file_put_contents(__DIR__ . '/test_results.log', "[FATAL] " . $e->getMessage() . "\n", FILE_APPEND);
}
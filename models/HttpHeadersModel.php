<?php
require_once __DIR__ . '/../security/SecurityUtils.php';

class HttpHeadersModel {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public function getHeaders($url) {
        if (empty($url)) {
            return ['error' => 'URL is required'];
        }
        
        if (!preg_match('~^(?:f|ht)tps?://~i', $url)) {
            $url = 'https://' . $url;
        }
        
        $sanitizedUrl = SecurityUtils::sanitizeInput($url, 'url');
        if ($sanitizedUrl === false) {
            return ['error' => 'Invalid URL format'];
        }
        
        if (!filter_var($sanitizedUrl, FILTER_VALIDATE_URL)) {
            return ['error' => 'Invalid URL'];
        }
        
        if (!$this->isUrlAccessible($sanitizedUrl)) {
            return ['error' => 'URL is not accessible'];
        }
        
        $headers = $this->fetchHeaders($sanitizedUrl);
        
        if ($headers === false) {
            return ['error' => 'Failed to fetch headers'];
        }
        
        $parsedHeaders = $this->parseHeaders($headers);
        
        return [
            'summary' => [
                'URL' => $sanitizedUrl,
                'Status Code' => $parsedHeaders['status_code'] ?? 'Unknown'
            ],
            'headers' => $parsedHeaders['headers'] ?? []
        ];
    }
    
    private function isUrlAccessible($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'CyberJagrithi Tools/1.0');
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $result !== false && $httpCode >= 200 && $httpCode < 400;
    }
    
    private function fetchHeaders($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'CyberJagrithi Tools/1.0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            error_log("cURL error fetching headers: " . $error);
            return false;
        }
        
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        
        $headers = substr($response, 0, $headerSize);
        return $headers;
    }
    
    private function parseHeaders($headers) {
        $lines = explode("\r\n", $headers);
        $parsed = [
            'status_code' => '',
            'headers' => []
        ];
        
        if (isset($lines[0])) {
            $statusLine = $lines[0];
            if (preg_match('/HTTP\/(?:\d\.\d|2)\s+(\d+)/', $statusLine, $matches)) {
                $parsed['status_code'] = $matches[1];
            }
        }
        
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $key = trim($key);
                $value = trim($value);
                $parsed['headers'][$key] = $value;
            }
        }
        
        return $parsed;
    }
}

<?php
class GlobalPingModel {
    private $mysqli;
    private $locations = [
        'US-East' => '8.8.8.8',
        'US-West' => '1.1.1.1',
        'Europe' => '8.8.4.4',
        'Asia' => '9.9.9.9',
        'Australia' => '208.67.222.222'
    ];

    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }

    public function pingHost($host) {
        if (empty($host)) {
            return ['error' => 'Host parameter is required.'];
        }

        if (!filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) && !filter_var($host, FILTER_VALIDATE_IP)) {
            return ['error' => 'Invalid host format. Please provide a valid IP address or domain name.'];
        }

        $results = [];
        $total_packets = 0;
        $total_lost = 0;
        $total_time = 0;

        foreach ($this->locations as $location => $ping_host) {
            $ping_result = $this->executePing($host);
            
            if ($ping_result['success']) {
                $results[$location] = [
                    'status' => 'success',
                    'packets_sent' => $ping_result['packets_sent'],
                    'packets_received' => $ping_result['packets_received'],
                    'packets_lost' => $ping_result['packets_lost'],
                    'loss_percentage' => $ping_result['loss_percentage'],
                    'min_time' => $ping_result['min_time'],
                    'avg_time' => $ping_result['avg_time'],
                    'max_time' => $ping_result['max_time'],
                    'mdev_time' => $ping_result['mdev_time']
                ];
                
                $total_packets += $ping_result['packets_sent'];
                $total_lost += $ping_result['packets_lost'];
                $total_time += $ping_result['avg_time'];
            } else {
                $results[$location] = [
                    'status' => 'failed',
                    'error' => $ping_result['error']
                ];
            }
        }

        $successful_pings = count(array_filter($results, function($r) { return $r['status'] === 'success'; }));
        
        return [
            'host' => $host,
            'locations' => $results,
            'summary' => [
                'total_locations' => count($this->locations),
                'successful_pings' => $successful_pings,
                'failed_pings' => count($this->locations) - $successful_pings,
                'overall_loss_percentage' => $total_packets > 0 ? round(($total_lost / $total_packets) * 100, 2) : 0,
                'average_response_time' => $successful_pings > 0 ? round($total_time / $successful_pings, 2) : 0
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    private function executePing($host) {
        $command = '';
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        // Check if shell functions are available
        $shellDisabled = !function_exists('shell_exec') || in_array('shell_exec', array_map('trim', explode(',', (string)ini_get('disable_functions'))));

        if (!$shellDisabled) {
            if ($isWindows) {
                $command = "ping -n 3 -w 2000 " . escapeshellarg($host);
            } else {
                $command = "ping -c 3 -W 2 " . escapeshellarg($host);
            }
            $output = shell_exec($command . " 2>&1");
        } else {
            $output = '';
        }

        if (empty($output)) {
            // Fallback: TCP connect "ping" with limited fidelity
            $tcpResult = $this->tcpPingFallback($host);
            if ($tcpResult['success']) {
                return [
                    'success' => true,
                    'packets_sent' => 1,
                    'packets_received' => 1,
                    'packets_lost' => 0,
                    'loss_percentage' => 0,
                    'min_time' => $tcpResult['latency_ms'],
                    'avg_time' => $tcpResult['latency_ms'],
                    'max_time' => $tcpResult['latency_ms'],
                    'mdev_time' => 0
                ];
            }
            return ['success' => false, 'error' => 'Ping unavailable. Using limited TCP fallback failed'];
        }

        $lines = explode("\n", $output);
        $stats_line = '';
        
        foreach ($lines as $line) {
            if (strpos($line, 'packets transmitted') !== false || strpos($line, 'Packets: Sent') !== false) {
                $stats_line = $line;
                break;
            }
        }
        
        if (empty($stats_line)) {
            $ip = gethostbyname($host);
            if ($ip && $ip !== $host) {
                return $this->executePing($ip);
            }
            return ['success' => false, 'error' => 'Could not parse ping output'];
        }

        $packets_sent = 0;
        $packets_received = 0;
        $packets_lost = 0;
        $loss_percentage = 0;
        $min_time = 0;
        $avg_time = 0;
        $max_time = 0;
        $mdev_time = 0;

        if (preg_match('/(\d+)\s+packets?\s+transmitted/', $stats_line, $matches)) {
            $packets_sent = (int)$matches[1];
        }
        
        if (preg_match('/(\d+)\s+received/', $stats_line, $matches)) {
            $packets_received = (int)$matches[1];
        }
        
        if (preg_match('/(\d+)%?\s+packet\s+loss/', $stats_line, $matches)) {
            $loss_percentage = (float)$matches[1];
        }

        $time_line = '';
        foreach ($lines as $line) {
            if (strpos($line, 'min/avg/max') !== false || strpos($line, 'Minimum') !== false) {
                $time_line = $line;
                break;
            }
        }

        if (preg_match('/(\d+\.?\d*)\/(\d+\.?\d*)\/(\d+\.?\d*)\/(\d+\.?\d*)/', $time_line, $matches)) {
            $min_time = (float)$matches[1];
            $avg_time = (float)$matches[2];
            $max_time = (float)$matches[3];
            $mdev_time = (float)$matches[4];
        }

        return [
            'success' => true,
            'packets_sent' => $packets_sent,
            'packets_received' => $packets_received,
            'packets_lost' => $packets_sent - $packets_received,
            'loss_percentage' => $loss_percentage,
            'min_time' => $min_time,
            'avg_time' => $avg_time,
            'max_time' => $max_time,
            'mdev_time' => $mdev_time
        ];
    }

    private function tcpPingFallback($host) {
        $portsToTry = [443, 80];
        foreach ($portsToTry as $port) {
            $start = microtime(true);
            $errno = 0;
            $errstr = '';
            $socket = @fsockopen($host, $port, $errno, $errstr, 3.0);
            $latencyMs = (microtime(true) - $start) * 1000.0;
            if ($socket) {
                fclose($socket);
                return ['success' => true, 'latency_ms' => (float)round($latencyMs, 2)];
            }
        }
        return ['success' => false, 'latency_ms' => 0];
    }
}

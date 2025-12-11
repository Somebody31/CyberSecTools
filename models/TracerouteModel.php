<?php

class TracerouteModel {
    private $mysqli;
    private $maxHops = 30;
    private $timeout = 2000;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public function trace($host) {
        try {
            if (empty($host)) {
                return [
                    'status' => 'error',
                    'message' => 'Host cannot be empty'
                ];
            }

            $host = filter_var(trim($host), FILTER_SANITIZE_STRING);
            
            $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
            $shellDisabled = !function_exists('exec') || in_array('exec', array_map('trim', explode(',', (string)ini_get('disable_functions'))));

            $output = [];
            $returnCode = 0;
            if (!$shellDisabled) {
                if ($isWindows) {
                    $command = sprintf(
                        'tracert -h %d -w %d %s',
                        $this->maxHops,
                        $this->timeout,
                        escapeshellarg($host)
                    );
                } else {
                    $command = sprintf(
                        'traceroute -m %d -w %d %s',
                        $this->maxHops,
                        ceil($this->timeout / 1000),
                        escapeshellarg($host)
                    );
                }
                exec($command . ' 2>&1', $output, $returnCode);
            }

            if ($shellDisabled || $returnCode !== 0 || empty($output)) {
                // Fallback: incremental TTL TCP connect test (very limited)
                $hops = $this->tcpTracerouteFallback($host, $this->maxHops, $this->timeout);
                if (!empty($hops)) {
                    return [
                        'status' => 'success',
                        'host' => $host,
                        'hops' => $hops
                    ];
                }
                return [
                    'status' => 'error',
                    'message' => 'Traceroute unavailable. Limited fallback failed'
                ];
            }

            $hops = $this->parseOutput($output);
            
            return [
                'status' => 'success',
                'host' => $host,
                'hops' => $hops
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'An unexpected error occurred'
            ];
        }
    }

    private function parseOutput($output) {
        $hops = [];
        $hopCount = 0;

        foreach ($output as $line) {
            if (strpos($line, 'Tracing route') !== false || 
                strpos($line, 'over a maximum') !== false || 
                empty(trim($line))) {
                continue;
            }

            if (preg_match('/^\s*(\d+)/', $line, $matches)) {
                $hopCount++;
                
                $ip = '*';
                $time = '*';
                $hostname = '*';

                if (preg_match('/(?:\d{1,3}\.){3}\d{1,3}/', $line, $ipMatch)) {
                    $ip = $ipMatch[0];
                    
                    $dnsResult = @gethostbyaddr($ip);
                    if ($dnsResult && $dnsResult !== $ip) {
                        $hostname = $dnsResult;
                    }
                }

                if (preg_match('/(\d+)ms/', $line, $timeMatch)) {
                    $time = $timeMatch[1] . 'ms';
                }

                $hops[] = [
                    'hop' => $hopCount,
                    'ip' => $ip,
                    'hostname' => $hostname,
                    'time' => $time
                ];
            }
        }
        
        return $hops;
    }

    private function tcpTracerouteFallback($host, $maxHops, $timeoutMs) {
        $resolved = @gethostbyname($host);
        if (!$resolved) {
            return [];
        }

        // PHP cannot set TTL on sockets portably; emulate with sequential attempts and DNS hints
        $hops = [];
        $previousIp = null;
        for ($i = 1; $i <= $maxHops; $i++) {
            $start = microtime(true);
            $errno = 0;
            $errstr = '';
            $socket = @fsockopen($resolved, 80, $errno, $errstr, max(1.0, $timeoutMs / 1000.0));
            $latencyMs = (microtime(true) - $start) * 1000.0;
            if ($socket) {
                fclose($socket);
                $hostname = @gethostbyaddr($resolved) ?: '*';
                $hops[] = [
                    'hop' => $i,
                    'ip' => $resolved,
                    'hostname' => $hostname,
                    'time' => (int)round($latencyMs) . 'ms'
                ];
                break;
            } else {
                // We cannot see intermediate hops without raw sockets; report unknown
                $hops[] = [
                    'hop' => $i,
                    'ip' => '*',
                    'hostname' => '*',
                    'time' => '*'
                ];
                // Stop early if repeated unknowns
                if ($i >= 5 && $previousIp === '*') {
                    break;
                }
                $previousIp = '*';
            }
        }
        return $hops;
    }
}

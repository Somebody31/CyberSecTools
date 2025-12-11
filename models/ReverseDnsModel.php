<?php
class ReverseDnsModel {
    private $mysqli;
    private const DNS_TIMEOUT = 5;

    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }

    public function reverseLookup(string $ip): array {
        try {
            if (empty($ip)) {
                return [
                    'ip' => '',
                    'hostname' => '',
                    'status' => 'error',
                    'message' => 'IP address cannot be empty'
                ];
            }

            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                return [
                    'ip' => $ip,
                    'hostname' => '',
                    'status' => 'error',
                    'message' => 'Invalid IP address format'
                ];
            }

            ini_set('default_socket_timeout', (string)self::DNS_TIMEOUT);
            
            $hostname = gethostbyaddr($ip);
            
            if ($hostname === false) {
                return [
                    'ip' => $ip,
                    'hostname' => '',
                    'status' => 'error',
                    'message' => 'DNS lookup failed'
                ];
            }

            return [
                'ip' => $ip,
                'hostname' => $hostname,
                'status' => 'success'
            ];

        } catch (Exception $e) {
            return [
                'ip' => $ip,
                'hostname' => '',
                'status' => 'error',
                'message' => 'An unexpected error occurred'
            ];
        }
    }
}

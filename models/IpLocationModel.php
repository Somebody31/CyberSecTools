<?php
class IpLocationModel {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public function getLocation($ip) {
        if (empty($ip)) {
            return ['error' => 'IP address is required.'];
        }
        
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return ['error' => 'Invalid IP address format provided.'];
        }
        
        try {
            $location_data = $this->getLocationData($ip);
            
            if (empty($location_data)) {
                return ['error' => 'Could not retrieve location information for this IP address.'];
            }
            
            return [
                'ip' => $ip,
                'location' => $location_data,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            error_log("IP location error: " . $e->getMessage());
            return ['error' => 'An error occurred while retrieving location information.'];
        }
    }
    
    private function getLocationData($ip) {
        $apis = [
            'http://ip-api.com/json/' . $ip,
            'https://ipapi.co/' . $ip . '/json/',
            'https://api.ipgeolocation.io/ipgeo?apiKey=free&ip=' . $ip
        ];
        
        foreach ($apis as $api_url) {
            $data = $this->fetchFromApi($api_url);
            if ($data && !isset($data['error'])) {
                return $this->formatLocationData($data, $api_url);
            }
        }
        
        return null;
    }
    
    private function fetchFromApi($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'CyberJagrithi Tools/1.0');
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false || $http_code !== 200) {
            return null;
        }
        
        $data = json_decode($response, true);
        return $data;
    }
    
    private function formatLocationData($data, $api_url) {
        if (strpos($api_url, 'ip-api.com') !== false) {
            return [
                'country' => $data['country'] ?? 'Unknown',
                'country_code' => $data['countryCode'] ?? 'Unknown',
                'region' => $data['regionName'] ?? 'Unknown',
                'region_code' => $data['region'] ?? 'Unknown',
                'city' => $data['city'] ?? 'Unknown',
                'zip' => $data['zip'] ?? 'Unknown',
                'latitude' => $data['lat'] ?? 'Unknown',
                'longitude' => $data['lon'] ?? 'Unknown',
                'timezone' => $data['timezone'] ?? 'Unknown',
                'isp' => $data['isp'] ?? 'Unknown',
                'organization' => $data['org'] ?? 'Unknown',
                'as' => $data['as'] ?? 'Unknown'
            ];
        } elseif (strpos($api_url, 'ipapi.co') !== false) {
            return [
                'country' => $data['country_name'] ?? 'Unknown',
                'country_code' => $data['country_code'] ?? 'Unknown',
                'region' => $data['region'] ?? 'Unknown',
                'region_code' => $data['region_code'] ?? 'Unknown',
                'city' => $data['city'] ?? 'Unknown',
                'zip' => $data['postal'] ?? 'Unknown',
                'latitude' => $data['latitude'] ?? 'Unknown',
                'longitude' => $data['longitude'] ?? 'Unknown',
                'timezone' => $data['timezone'] ?? 'Unknown',
                'isp' => $data['org'] ?? 'Unknown',
                'organization' => $data['org'] ?? 'Unknown',
                'as' => $data['asn'] ?? 'Unknown'
            ];
        } elseif (strpos($api_url, 'ipgeolocation.io') !== false) {
            return [
                'country' => $data['country_name'] ?? 'Unknown',
                'country_code' => $data['country_code2'] ?? 'Unknown',
                'region' => $data['state_prov'] ?? 'Unknown',
                'region_code' => $data['state_code'] ?? 'Unknown',
                'city' => $data['city'] ?? 'Unknown',
                'zip' => $data['zipcode'] ?? 'Unknown',
                'latitude' => $data['latitude'] ?? 'Unknown',
                'longitude' => $data['longitude'] ?? 'Unknown',
                'timezone' => $data['time_zone']['name'] ?? 'Unknown',
                'isp' => $data['isp'] ?? 'Unknown',
                'organization' => $data['organization'] ?? 'Unknown',
                'as' => $data['asn'] ?? 'Unknown'
            ];
        }
        
        return $data;
    }
}

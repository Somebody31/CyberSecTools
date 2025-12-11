<?php
require_once __DIR__ . '/../security/SecurityUtils.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../models/DnsLookupModel.php';
require_once __DIR__ . '/../models/ReverseDnsModel.php';
require_once __DIR__ . '/../models/GlobalPingModel.php';
require_once __DIR__ . '/../models/TracerouteModel.php';
require_once __DIR__ . '/../models/WhoisLookupModel.php';

SecurityUtils::setSecurityHeaders();
header('Content-Type: application/json; charset=utf-8');

$domain = 'example.com';
$ip = '8.8.8.8';

$results = [];

try { $results['dns_lookup'] = (new DnsLookupModel($mysqli))->lookup($domain, null); } catch (Exception $e) { $results['dns_lookup'] = ['error' => $e->getMessage()]; }
try { $results['reverse_dns'] = (new ReverseDnsModel($mysqli))->reverseLookup($ip); } catch (Exception $e) { $results['reverse_dns'] = ['error' => $e->getMessage()]; }
try { $results['global_ping'] = (new GlobalPingModel($mysqli))->pingHost($domain); } catch (Exception $e) { $results['global_ping'] = ['error' => $e->getMessage()]; }
try { $results['traceroute'] = (new TracerouteModel($mysqli))->trace($domain); } catch (Exception $e) { $results['traceroute'] = ['error' => $e->getMessage()]; }
try { $results['whois'] = (new WhoisLookupModel($mysqli))->lookup($domain); } catch (Exception $e) { $results['whois'] = ['error' => $e->getMessage()]; }

echo json_encode(['status' => 'ok', 'self_test' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>



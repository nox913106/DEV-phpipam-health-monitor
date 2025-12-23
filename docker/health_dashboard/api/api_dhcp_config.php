<?php
/**
 * api_dhcp_config.php - DHCP ä¼ºæ??¨é?ç½®ç®¡??API
 * 
 * ?ä? DHCP ä¼ºæ??¨å?è¡¨ç?å¢žåˆªä¿®æŸ¥?Ÿèƒ½
 * ?ç½®?²å???JSON æª”æ?ä¸­ï??¯å??‹ä¿®??
 * 
 * @author Jason Cheng
 * @created 2025-12-19
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// ?•ç? CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ?ç½®æª”è·¯å¾?
define('CONFIG_FILE', __DIR__ . '/../config/dhcp_servers.json');

/**
 * è®€??DHCP ä¼ºæ??¨é?ç½?
 */
function loadConfig() {
    if (!file_exists(CONFIG_FILE)) {
        // ?è¨­?ç½®
        $default = [
            ['ip' => '172.16.5.196', 'hostname' => 'DHCP-CH-HQ2', 'location' => 'å½°å?ç¸½éƒ¨2', 'enabled' => true],
            ['ip' => '172.23.13.10', 'hostname' => 'DHCP-CH-PGT', 'location' => 'å½°å??”é¹½', 'enabled' => true],
            ['ip' => '172.23.174.5', 'hostname' => 'DHCP-TC-HQ', 'location' => '?°ä¸­ç¸½éƒ¨', 'enabled' => true],
            ['ip' => '172.23.199.150', 'hostname' => 'DHCP-TC-UAIC', 'location' => '?°ä¸­', 'enabled' => true],
            ['ip' => '172.23.110.1', 'hostname' => 'DHCP-TP-XY', 'location' => '?°å?', 'enabled' => true],
            ['ip' => '172.23.94.254', 'hostname' => 'DHCP-TP-BaoYu-CoreSW', 'location' => '?°å?å¯¶è?', 'enabled' => true],
        ];
        saveConfig($default);
        return $default;
    }
    
    $content = file_get_contents(CONFIG_FILE);
    return json_decode($content, true) ?: [];
}

/**
 * ?²å? DHCP ä¼ºæ??¨é?ç½?
 */
function saveConfig($config) {
    $dir = dirname(CONFIG_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return file_put_contents(CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * é©—è? IP ?¼å?
 */
function validateIp($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP) !== false;
}

/**
 * ?–å??€?‰ä¼º?å™¨
 */
function getAll() {
    $servers = loadConfig();
    return ['success' => true, 'data' => $servers, 'count' => count($servers)];
}

/**
 * ?–å??®ä?ä¼ºæ???
 */
function getOne($ip) {
    $servers = loadConfig();
    foreach ($servers as $server) {
        if ($server['ip'] === $ip) {
            return ['success' => true, 'data' => $server];
        }
    }
    return ['success' => false, 'error' => 'Server not found'];
}

/**
 * ?°å?ä¼ºæ???
 */
function addServer($data) {
    if (empty($data['ip'])) {
        return ['success' => false, 'error' => 'IP is required'];
    }
    
    if (!validateIp($data['ip'])) {
        return ['success' => false, 'error' => 'Invalid IP format'];
    }
    
    $servers = loadConfig();
    
    // æª¢æŸ¥?¯å¦å·²å???
    foreach ($servers as $server) {
        if ($server['ip'] === $data['ip']) {
            return ['success' => false, 'error' => 'Server already exists'];
        }
    }
    
    $newServer = [
        'ip' => $data['ip'],
        'hostname' => $data['hostname'] ?? '',
        'location' => $data['location'] ?? '',
        'enabled' => isset($data['enabled']) ? (bool)$data['enabled'] : true
    ];
    
    $servers[] = $newServer;
    saveConfig($servers);
    
    // ?Œæ??´æ–° HistoryCollector ??hostnames
    updateHistoryCollector($servers);
    
    return ['success' => true, 'data' => $newServer, 'message' => 'Server added'];
}

/**
 * ?´æ–°ä¼ºæ???
 */
function updateServer($ip, $data) {
    $servers = loadConfig();
    $found = false;
    
    foreach ($servers as &$server) {
        if ($server['ip'] === $ip) {
            if (isset($data['hostname'])) $server['hostname'] = $data['hostname'];
            if (isset($data['location'])) $server['location'] = $data['location'];
            if (isset($data['enabled'])) $server['enabled'] = (bool)$data['enabled'];
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        return ['success' => false, 'error' => 'Server not found'];
    }
    
    saveConfig($servers);
    updateHistoryCollector($servers);
    
    return ['success' => true, 'message' => 'Server updated'];
}

/**
 * ?ªé™¤ä¼ºæ???
 */
function deleteServer($ip) {
    $servers = loadConfig();
    $newServers = array_filter($servers, function($s) use ($ip) {
        return $s['ip'] !== $ip;
    });
    
    if (count($newServers) === count($servers)) {
        return ['success' => false, 'error' => 'Server not found'];
    }
    
    saveConfig(array_values($newServers));
    updateHistoryCollector(array_values($newServers));
    
    return ['success' => true, 'message' => 'Server deleted'];
}

/**
 * ?Œæ­¥?´æ–° HistoryCollector
 */
function updateHistoryCollector($servers) {
    // ?™å€‹å‡½?¸ç”¨?¼åœ¨å®¹å™¨?§æ›´?°ç?å¼ç¢¼ä¸­ç? hostnames
    // ?±æ–¼?‘å€‘ç¾?¨ä½¿??JSON ?ç½®ï¼ŒHistoryCollector ä¹Ÿæ?è©²å? JSON è®€??
    // ?™è£¡?«æ?ä¸å?ä»»ä?äº‹ï?? ç‚º?‘å€‘é?è¦ä¿®??HistoryCollector ä¾†å? JSON è®€??
}

/**
 * ?–å??Ÿç”¨?„ä¼º?å™¨ IP ?—è¡¨
 */
function getEnabledIps() {
    $servers = loadConfig();
    $ips = [];
    foreach ($servers as $server) {
        if ($server['enabled']) {
            $ips[] = $server['ip'];
        }
    }
    return ['success' => true, 'data' => $ips, 'count' => count($ips)];
}

// ä¸»ç?å¼?
try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    $ip = $_GET['ip'] ?? '';
    
    // ?–å? POST/PUT è³‡æ?
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    
    switch ($method) {
        case 'GET':
            if ($ip) {
                $result = getOne($ip);
            } elseif ($action === 'enabled') {
                $result = getEnabledIps();
            } else {
                $result = getAll();
            }
            break;
            
        case 'POST':
            $result = addServer($input);
            break;
            
        case 'PUT':
            if (!$ip) {
                $result = ['success' => false, 'error' => 'IP is required for update'];
            } else {
                $result = updateServer($ip, $input);
            }
            break;
            
        case 'DELETE':
            if (!$ip) {
                $result = ['success' => false, 'error' => 'IP is required for delete'];
            } else {
                $result = deleteServer($ip);
            }
            break;
            
        default:
            $result = ['success' => false, 'error' => 'Method not allowed'];
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

<?php
/**
 * api_stats.php - çµ±è?è³‡æ? API ç«¯é?
 * 
 * ?ä? JSON ?¼å??„çµ±è¨ˆè??™çµ¦ Dashboard ä½¿ç”¨
 * 
 * @author Jason Cheng
 * @created 2025-12-19
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// è³‡æ?åº«é?ç½?
function getDatabase() {
    $host = getenv('IPAM_DATABASE_HOST') ?: 'phpipam-mariadb';
    $user = getenv('IPAM_DATABASE_USER') ?: 'phpipam';
    $pass = getenv('IPAM_DATABASE_PASS') ?: 'my_secret_phpipam_pass';
    $name = getenv('IPAM_DATABASE_NAME') ?: 'phpipam';
    
    $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
}

// ?–å?ç³»çµ±è³‡æ?æ­·å² (?¨æ–¼?²ç???
function getSystemHistory($db, $hours = 24) {
    $sql = "SELECT 
        DATE_FORMAT(recorded_at, '%Y-%m-%d %H:%i') as time,
        cpu_usage_percent as cpu,
        memory_usage_percent as memory,
        disk_usage_percent as disk
    FROM health_check_system_history 
    WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
    ORDER BY recorded_at ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':hours' => $hours]);
    return $stmt->fetchAll();
}

// ?–å? DHCP å»¶é²æ­·å² (?¨æ–¼?²ç???
function getDhcpHistory($db, $hours = 24) {
    $sql = "SELECT 
        DATE_FORMAT(recorded_at, '%Y-%m-%d %H:%i') as time,
        dhcp_ip as ip,
        dhcp_hostname as hostname,
        latency_ms as latency,
        reachable
    FROM health_check_dhcp_history 
    WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
    ORDER BY recorded_at ASC, dhcp_ip ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':hours' => $hours]);
    return $stmt->fetchAll();
}

// ?–å??€?°ç???
function getLatestStatus($db) {
    // ç³»çµ±è³‡æ?
    $sql1 = "SELECT * FROM health_check_system_history ORDER BY recorded_at DESC LIMIT 1";
    $system = $db->query($sql1)->fetch();
    
    // DHCP ?€?°ç???
    $sql2 = "SELECT dhcp_ip, dhcp_hostname, reachable, latency_ms, recorded_at 
             FROM health_check_dhcp_history h1
             WHERE recorded_at = (SELECT MAX(recorded_at) FROM health_check_dhcp_history h2 WHERE h1.dhcp_ip = h2.dhcp_ip)
             ORDER BY dhcp_ip";
    $dhcp = $db->query($sql2)->fetchAll();
    
    return ['system' => $system, 'dhcp' => $dhcp];
}

// ?–å? 24 å°æ?çµ±è??˜è?
function getStats24h($db) {
    require_once(__DIR__ . '/../includes/StatsCalculator.php');
    return StatsCalculator::getSummary($db);
}

// ä¸»ç?å¼?
try {
    $db = getDatabase();
    
    $action = $_GET['action'] ?? 'latest';
    $hours = (int)($_GET['hours'] ?? 24);
    
    switch ($action) {
        case 'system_history':
            $data = getSystemHistory($db, $hours);
            break;
        case 'dhcp_history':
            $data = getDhcpHistory($db, $hours);
            break;
        case 'stats':
            $data = getStats24h($db);
            break;
        case 'latest':
        default:
            $data = getLatestStatus($db);
            break;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'generated_at' => date('c')
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

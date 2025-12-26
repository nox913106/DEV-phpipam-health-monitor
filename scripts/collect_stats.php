#!/usr/bin/env php
<?php
/**
 * collect_stats.php
 * 
 * 資料收集排程腳本
 * 建議每 5 分鐘執行一次，收集系統資源和 DHCP 伺服器狀態
 * 
 * Cron 設定範例:
 * */5 * * * * php /health_check/scripts/collect_stats.php
 * 
 * @author MG Feng
 * @created 2025-12-18
 * @modified 2025-12-26
 */

// 設定錯誤顯示
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 定義基礎路徑
define('BASE_PATH', dirname(__DIR__));

// 載入依賴
require_once(BASE_PATH . '/includes/HistoryCollector.php');

/**
 * 取得資料庫連線
 */
function getDatabase() {
    // 嘗試多個設定檔路徑
    $config_paths = [
        BASE_PATH . '/config/database.php',
        '/health_check/config/database.php',
        '/phpipam/health_dashboard/config/database.php'
    ];
    
    $config = null;
    foreach ($config_paths as $path) {
        if (file_exists($path)) {
            $config = require($path);
            break;
        }
    }
    
    // 如果沒有設定檔，使用環境變數
    if (!$config) {
        $config = [
            'host' => getenv('IPAM_DATABASE_HOST') ?: 'phpipam-mariadb',
            'database' => getenv('IPAM_DATABASE_NAME') ?: 'phpipam',
            'username' => getenv('IPAM_DATABASE_USER') ?: 'phpipam',
            'password' => getenv('IPAM_DATABASE_PASS') ?: ''
        ];
    }
    
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    return $pdo;
}

/**
 * 主程式
 */
function main() {
    $start_time = microtime(true);
    
    echo "================================\n";
    echo "Health Check Data Collection\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n";
    echo "================================\n\n";
    
    try {
        // 取得資料庫連線
        $db = getDatabase();
        echo "[OK] Database connected\n";
        
        // 收集所有資料
        $results = HistoryCollector::collectAll($db);
        
        // 輸出系統資源結果
        if ($results['system']['success']) {
            echo "[OK] System resources collected\n";
            echo "     CPU: {$results['system']['cpu']}%\n";
            echo "     Memory: {$results['system']['memory']}%\n";
            echo "     Disk: {$results['system']['disk']}%\n";
        } else {
            echo "[FAIL] System resources: {$results['system']['error']}\n";
        }
        
        // 輸出 DHCP 結果
        if ($results['dhcp']['success']) {
            echo "[OK] DHCP stats collected: {$results['dhcp']['count']} servers\n";
            foreach ($results['dhcp']['servers'] as $server) {
                $status = $server['reachable'] ? 'UP' : 'DOWN';
                $latency = $server['latency'] > 0 ? "{$server['latency']}ms" : 'N/A';
                echo "     {$server['hostname']} ({$server['ip']}): {$status} - {$latency}\n";
            }
        } else {
            echo "[FAIL] DHCP stats: {$results['dhcp']['error']}\n";
        }
        
        // 清理舊記錄 (每次執行都檢查，保留 7 天資料)
        $purge_result = HistoryCollector::purgeOldRecords($db, 7);
        if ($purge_result['success']) {
            echo "[OK] Old records purged (keeping 7 days)\n";
        }
        
    } catch (Exception $e) {
        echo "[ERROR] " . $e->getMessage() . "\n";
        exit(1);
    }
    
    $elapsed = round((microtime(true) - $start_time) * 1000, 2);
    echo "\n================================\n";
    echo "Completed in {$elapsed}ms\n";
    echo "================================\n";
}

// 執行主程式
main();

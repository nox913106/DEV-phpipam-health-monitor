#!/usr/bin/env php
<?php
/**
 * collect_stats.php
 * 
 * è³‡æ??¶é??’ç??³æœ¬
 * å»ºè­°æ¯?5 ?†é??·è?ä¸€æ¬¡ï??¶é?ç³»çµ±??DHCP ??Ž§?¸æ?
 * 
 * Cron è¨­å?ç¯„ä?:
 * */5 * * * * php /var/www/phpipam/app/tools/health_check/scripts/collect_stats.php
 * 
 * @author Jason Cheng
 * @created 2025-12-18
 */

// è¨­å??¯èª¤?±å?
error_reporting(E_ALL);
ini_set('display_errors', 1);

// å®šç¾©?ºç?è·¯å?
define('BASE_PATH', dirname(__DIR__));
define('INCLUDES_PATH', BASE_PATH . '/includes');

// å¼•å…¥å¿…è?é¡žåˆ¥
require_once(INCLUDES_PATH . '/HistoryCollector.php');

/**
 * ?–å? phpIPAM è³‡æ?åº«é€??
 * 
 * @return PDO è³‡æ?åº«é€??
 */
function getPhpIpamDatabase() {
    // è¼‰å…¥ phpIPAM ?ç½® (?¨ç½²??phpIPAM ?‚ä½¿??
    $config_file = '/var/www/phpipam/config.php';
    
    if (file_exists($config_file)) {
        // å¾?phpIPAM ?ç½®è®€?–è??™åº«è¨­å?
        require_once($config_file);
        
        $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        return $pdo;
    }
    
    // ?‹ç™¼?°å?ï¼šä½¿?¨ç¨ç«‹é?ç½?
    $dev_config = BASE_PATH . '/config/database.php';
    if (file_exists($dev_config)) {
        $db = require($dev_config);
        
        $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        return $pdo;
    }
    
    throw new Exception("?¡æ??¾åˆ°è³‡æ?åº«é?ç½®æ?æ¡?);
}

/**
 * ä¸»ç?å¼?
 */
function main() {
    $start_time = microtime(true);
    $timestamp = date('Y-m-d H:i:s');
    
    echo "=== Health Check Data Collector ===\n";
    echo "Started at: {$timestamp}\n\n";
    
    try {
        // ?–å?è³‡æ?åº«é€??
        $db = getPhpIpamDatabase();
        echo "[OK] è³‡æ?åº«é€???å?\n";
        
        // ?·è?è³‡æ??¶é?
        $results = HistoryCollector::collectAll($db);
        
        // è¼¸å‡ºçµæ?
        if ($results['system']['success']) {
            $cpu = $results['system']['data']['cpu'];
            $mem = $results['system']['data']['memory'];
            $disk = $results['system']['data']['disk'];
            echo "[OK] ç³»çµ±è³‡æ?: CPU={$cpu}%, Memory={$mem}%, Disk={$disk}%\n";
        } else {
            echo "[ERROR] ç³»çµ±è³‡æ??¶é?å¤±æ?: {$results['system']['error']}\n";
        }
        
        if ($results['dhcp']['success']) {
            $count = $results['dhcp']['count'];
            echo "[OK] DHCP ä¼ºæ??? å·²æª¢??{$count} ?°ä¼º?å™¨\n";
            
            foreach ($results['dhcp']['servers'] as $server) {
                $status = $server['reachable'] ? '??Online' : '??Offline';
                $hostname = $server['hostname'] ?? $server['ip'];
                echo "     - {$hostname} ({$server['ip']}): {$status}\n";
            }
        } else {
            echo "[ERROR] DHCP æª¢æŸ¥å¤±æ?: {$results['dhcp']['error']}\n";
        }
        
        // æ¸…ç??Šè???(æ¯æ¬¡?·è??½æª¢??
        $purge_result = HistoryCollector::purgeOldRecords($db, 7);
        if ($purge_result['success']) {
            $sys_del = $purge_result['deleted']['system_records'];
            $dhcp_del = $purge_result['deleted']['dhcp_records'];
            if ($sys_del > 0 || $dhcp_del > 0) {
                echo "[OK] å·²æ??†è?è³‡æ?: ç³»çµ±={$sys_del}ç­? DHCP={$dhcp_del}ç­†\n";
            }
        }
        
    } catch (Exception $e) {
        echo "[FATAL] ?·è??¯èª¤: " . $e->getMessage() . "\n";
        exit(1);
    }
    
    // å®Œæ?
    $elapsed = round((microtime(true) - $start_time) * 1000, 2);
    echo "\nCompleted in {$elapsed}ms\n";
    echo "================================\n";
}

// ?·è?ä¸»ç?å¼?
main();

<?php
/**
 * HealthCheckController.php
 * 
 * phpIPAM API Controller for Health Check
 * ?´å?ç³»çµ±è³‡è??ç¶²è·¯çµ±è¨ˆã€DHCP æª¢æŸ¥??24 å°æ?æ­·å²çµ±è??Ÿèƒ½
 * 
 * @author Jason Cheng
 * @created 2025-12-02
 * @updated 2025-12-18 - ? å…¥ 24 å°æ?æ­·å²çµ±è??Ÿèƒ½
 */

// å¼•å…¥å¿…è??„é???
require_once(__DIR__ . '/../includes/SystemInfo.php');
require_once(__DIR__ . '/../includes/NetworkStats.php');
require_once(__DIR__ . '/../includes/DhcpChecker.php');
require_once(__DIR__ . '/../includes/StatsCalculator.php');

/**
 * ?¥åº·æª¢æŸ¥ API Controller
 * 
 * æ­?Controller ?‰æ•´?ˆåˆ° phpIPAM ??API ?¶æ?ä¸?
 * è·¯å?: /api/{app_id}/tools/daily_health_check/
 */
class HealthCheckController {
    
    /** @var PDO è³‡æ?åº«é€?? (?¨æ–¼æ­·å²çµ±è?) */
    private static $db = null;
    
    /**
     * è¨­å?è³‡æ?åº«é€??
     * 
     * @param PDO $db è³‡æ?åº«é€??
     */
    public static function setDatabase($db) {
        self::$db = $db;
    }
    
    /**
     * ?–å?è³‡æ?åº«é€??
     * ?—è©¦å¾?phpIPAM ?°å??–å?è³‡æ?åº«é€??
     * 
     * @return PDO|null è³‡æ?åº«é€????null
     */
    private static function getDatabase() {
        // å¦‚æ?å·²è¨­å®šï??´æŽ¥è¿”å?
        if (self::$db !== null) {
            return self::$db;
        }
        
        // ?—è©¦å¾?phpIPAM ?°å??–å?
        global $Database;
        if (isset($Database) && $Database instanceof Database) {
            try {
                // phpIPAM ??Database é¡žåˆ¥
                self::$db = $Database->getConnection();
                return self::$db;
            } catch (Exception $e) {
                // å¿½ç•¥?¯èª¤ï¼Œè???null
            }
        }
        
        // ?—è©¦å¾žé?ç½®æ?å»ºç????
        $config_file = __DIR__ . '/../config/database.php';
        if (file_exists($config_file)) {
            try {
                $db_config = require($config_file);
                $dsn = "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4";
                self::$db = new PDO($dsn, $db_config['user'], $db_config['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                return self::$db;
            } catch (Exception $e) {
                // å¿½ç•¥?¯èª¤
            }
        }
        
        return null;
    }
    
    /**
     * ?·è??¥åº·æª¢æŸ¥
     * 
     * @param array $params GET ?ƒæ•¸
     * @return array API ?žæ?
     */
    public static function execute($params = []) {
        try {
            $start_time = microtime(true);
            
            // è§???ƒæ•¸
            $dhcp_ips = isset($params['dhcp_server_ip']) ? $params['dhcp_server_ip'] : '';
            $include_history = isset($params['include_history']) ? 
                filter_var($params['include_history'], FILTER_VALIDATE_BOOLEAN) : true;
            
            // ?è¨­ DHCP ä¼ºæ??¨å?è¡?
            if (empty($dhcp_ips)) {
                $dhcp_ips = '172.16.5.196,172.23.13.10,172.23.174.5,172.23.199.150,172.23.110.1,172.23.94.254';
            }
            
            // ?–å?è³‡æ?åº«é€?? (?¨æ–¼æ­·å²çµ±è?)
            $db = $include_history ? self::getDatabase() : null;
            
            // ?¶é?ç³»çµ±è³‡è? (?«æ­·?²çµ±è¨?
            if ($db !== null) {
                $system_info = SystemInfo::getAllWithHistory($db);
            } else {
                $system_info = SystemInfo::getAll();
            }
            
            // ?¶é?ç¶²è·¯çµ±è?
            $network_stats = NetworkStats::getStats();
            
            // æª¢æŸ¥ DHCP ä¼ºæ???(?«æ­·?²çµ±è¨?
            if ($db !== null) {
                $dhcp_results = DhcpChecker::checkWithHistory($dhcp_ips, $db);
            } else {
                $dhcp_results = DhcpChecker::check($dhcp_ips);
            }
            
            // è¨ˆç??·è??‚é?
            $execution_time = microtime(true) - $start_time;
            
            // å»ºç??žæ?è³‡æ?
            $result = [
                'report_type' => 'daily_health_check',
                'generated_at' => date('c'),
                'execution_time_ms' => round($execution_time * 1000, 2),
                'host_info' => $system_info['host_info'],
                'system_resources' => $system_info['system_resources'],
                'network_stats' => $network_stats,
                'dhcp_servers' => $dhcp_results,
                'historical_data_available' => ($db !== null)
            ];
            
            return self::successResponse($result, $execution_time);
            
        } catch (Exception $e) {
            return self::errorResponse($e->getMessage());
        }
    }
    
    /**
     * ?…å?å¾?24 å°æ?çµ±è??˜è?
     * 
     * @return array API ?žæ?
     */
    public static function getStatsSummary() {
        try {
            $db = self::getDatabase();
            
            if ($db === null) {
                return self::errorResponse('è³‡æ?åº«é€??ä¸å¯??);
            }
            
            $summary = StatsCalculator::getSummary($db);
            
            return self::successResponse([
                'report_type' => 'stats_summary_24h',
                'summary' => $summary
            ], 0);
            
        } catch (Exception $e) {
            return self::errorResponse($e->getMessage());
        }
    }
    
    /**
     * ?å??žæ??¼å?ï¼ˆç¬¦??phpIPAM API è¦ç?ï¼?
     * 
     * @param array $data è³‡æ?
     * @param float $time ?·è??‚é?
     * @return array ?¼å??–å???
     */
    private static function successResponse($data, $time) {
        return [
            'success' => true,
            'code' => 200,
            'data' => $data,
            'time' => round($time, 3)
        ];
    }
    
    /**
     * ?¯èª¤?žæ??¼å?ï¼ˆç¬¦??phpIPAM API è¦ç?ï¼?
     * 
     * @param string $message ?¯èª¤è¨Šæ¯
     * @return array ?¼å??–å???
     */
    private static function errorResponse($message) {
        return [
            'success' => false,
            'code' => 500,
            'message' => $message,
            'time' => 0
        ];
    }
}

// å¦‚æ??´æŽ¥?·è?æ­¤æ?æ¡ˆï??¨æ–¼æ¸¬è©¦ï¼?
if (php_sapi_name() === 'cli') {
    // CLI æ¨¡å?æ¸¬è©¦
    header('Content-Type: application/json');
    
    // æ¨¡æ“¬ GET ?ƒæ•¸
    $params = [];
    if (isset($argv[1])) {
        parse_str($argv[1], $params);
    }
    
    $result = HealthCheckController::execute($params);
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

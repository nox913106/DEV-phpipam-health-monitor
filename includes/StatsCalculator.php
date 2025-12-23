<?php
/**
 * StatsCalculator.php
 * 
 * çµ±è?è¨ˆç???
 * å¾æ­·?²è??™è?ç®?24 å°æ?çµ±è? (å¹³å??¼ã€æ?å°å€¼ã€æ?å¤§å€?
 * 
 * @author Jason Cheng
 * @created 2025-12-18
 */

class StatsCalculator {
    
    /**
     * ?–å? 24 å°æ?ç³»çµ±è³‡æ?çµ±è?
     * 
     * @param PDO $db è³‡æ?åº«é€??
     * @param int $hours çµ±è??‚é?ç¯„å? (?è¨­ 24 å°æ?)
     * @return array çµ±è?çµæ?
     */
    public static function getSystemStats24h($db, $hours = 24) {
        try {
            $sql = "SELECT 
                -- CPU çµ±è?
                AVG(cpu_usage_percent) as cpu_avg,
                MIN(cpu_usage_percent) as cpu_min,
                MAX(cpu_usage_percent) as cpu_max,
                
                -- è¨˜æ†¶é«”çµ±è¨?
                AVG(memory_usage_percent) as memory_avg,
                MIN(memory_usage_percent) as memory_min,
                MAX(memory_usage_percent) as memory_max,
                
                -- ç£ç?çµ±è?
                AVG(disk_usage_percent) as disk_avg,
                MIN(disk_usage_percent) as disk_min,
                MAX(disk_usage_percent) as disk_max,
                
                -- æ¨?œ¬??
                COUNT(*) as samples
                
            FROM health_check_system_history
            WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([':hours' => $hours]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // å¦‚æ?æ²’æ?è³‡æ?ï¼Œè??ç©ºçµ±è?
            if (!$row || $row['samples'] == 0) {
                return self::emptySystemStats();
            }
            
            return [
                'cpu' => [
                    'avg' => round((float)$row['cpu_avg'], 2),
                    'min' => round((float)$row['cpu_min'], 2),
                    'max' => round((float)$row['cpu_max'], 2),
                    'samples' => (int)$row['samples']
                ],
                'memory' => [
                    'avg' => round((float)$row['memory_avg'], 2),
                    'min' => round((float)$row['memory_min'], 2),
                    'max' => round((float)$row['memory_max'], 2),
                    'samples' => (int)$row['samples']
                ],
                'disk' => [
                    'avg' => round((float)$row['disk_avg'], 2),
                    'min' => round((float)$row['disk_min'], 2),
                    'max' => round((float)$row['disk_max'], 2),
                    'samples' => (int)$row['samples']
                ],
                'period_hours' => $hours,
                'has_data' => true
            ];
            
        } catch (Exception $e) {
            return self::emptySystemStats($e->getMessage());
        }
    }
    
    /**
     * ?–å? 24 å°æ? DHCP çµ±è?
     * 
     * @param PDO $db è³‡æ?åº«é€??
     * @param string $ip DHCP ä¼ºæ???IP (?¯é¸ï¼Œç©º?¼å?è¿”å??€?‰ä¼º?å™¨)
     * @param int $hours çµ±è??‚é?ç¯„å? (?è¨­ 24 å°æ?)
     * @return array çµ±è?çµæ?
     */
    public static function getDhcpStats24h($db, $ip = null, $hours = 24) {
        try {
            $params = [':hours' => $hours];
            
            $sql = "SELECT 
                dhcp_ip,
                dhcp_hostname,
                
                -- å»¶é²çµ±è? (?ªè?ç®—å¯?”ç?è¨˜é?)
                AVG(CASE WHEN reachable = 1 THEN latency_ms ELSE NULL END) as avg_latency,
                MIN(CASE WHEN reachable = 1 THEN latency_ms ELSE NULL END) as min_latency,
                MAX(CASE WHEN reachable = 1 THEN latency_ms ELSE NULL END) as max_latency,
                
                -- å°å??ºå¤±?‡çµ±è¨?
                AVG(packet_loss_percent) as avg_packet_loss,
                
                -- ?¯ç”¨?§çµ±è¨?
                SUM(CASE WHEN reachable = 1 THEN 1 ELSE 0 END) as reachable_count,
                COUNT(*) as total_count,
                (SUM(CASE WHEN reachable = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100 as availability_percent
                
            FROM health_check_dhcp_history
            WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)";
            
            // å¦‚æ??‡å? IPï¼Œå??¥ç¯©?¸æ?ä»?
            if ($ip !== null) {
                $sql .= " AND dhcp_ip = :ip";
                $params[':ip'] = $ip;
            }
            
            $sql .= " GROUP BY dhcp_ip, dhcp_hostname ORDER BY dhcp_ip";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // å¦‚æ??‡å??®ä? IPï¼Œç›´?¥è??ç???
            if ($ip !== null) {
                if (empty($rows)) {
                    return self::emptyDhcpStats($ip);
                }
                return self::formatDhcpStats($rows[0], $hours);
            }
            
            // è¿”å??€?‰ä¼º?å™¨?„çµ±è¨?
            $results = [];
            foreach ($rows as $row) {
                $results[$row['dhcp_ip']] = self::formatDhcpStats($row, $hours);
            }
            
            return $results;
            
        } catch (Exception $e) {
            if ($ip !== null) {
                return self::emptyDhcpStats($ip, $e->getMessage());
            }
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * ?¼å???DHCP çµ±è?çµæ?
     * 
     * @param array $row è³‡æ?åº«æŸ¥è©¢ç???
     * @param int $hours çµ±è??‚é?ç¯„å?
     * @return array ?¼å??–ç?çµ±è?
     */
    private static function formatDhcpStats($row, $hours) {
        return [
            'ip' => $row['dhcp_ip'],
            'hostname' => $row['dhcp_hostname'],
            'avg_latency_ms' => round((float)$row['avg_latency'], 2),
            'min_latency_ms' => round((float)$row['min_latency'], 2),
            'max_latency_ms' => round((float)$row['max_latency'], 2),
            'avg_packet_loss' => round((float)$row['avg_packet_loss'], 2),
            'availability_percent' => round((float)$row['availability_percent'], 2),
            'samples' => (int)$row['total_count'],
            'period_hours' => $hours,
            'has_data' => true
        ];
    }
    
    /**
     * è¿”å?ç©ºç?ç³»çµ±çµ±è?çµæ?
     * 
     * @param string $error ?¯èª¤è¨Šæ¯ (?¯é¸)
     * @return array ç©ºçµ±è¨ˆç?æ§?
     */
    private static function emptySystemStats($error = null) {
        $empty = [
            'avg' => null,
            'min' => null,
            'max' => null,
            'samples' => 0
        ];
        
        $result = [
            'cpu' => $empty,
            'memory' => $empty,
            'disk' => $empty,
            'has_data' => false,
            'note' => 'å°šç„¡æ­·å²è³‡æ?ï¼Œè?ç­‰å??¸æ??¶é?'
        ];
        
        if ($error) {
            $result['error'] = $error;
        }
        
        return $result;
    }
    
    /**
     * è¿”å?ç©ºç? DHCP çµ±è?çµæ?
     * 
     * @param string $ip DHCP ä¼ºæ???IP
     * @param string $error ?¯èª¤è¨Šæ¯ (?¯é¸)
     * @return array ç©ºçµ±è¨ˆç?æ§?
     */
    private static function emptyDhcpStats($ip, $error = null) {
        $result = [
            'ip' => $ip,
            'avg_latency_ms' => null,
            'min_latency_ms' => null,
            'max_latency_ms' => null,
            'avg_packet_loss' => null,
            'availability_percent' => null,
            'samples' => 0,
            'has_data' => false,
            'note' => 'å°šç„¡æ­·å²è³‡æ?ï¼Œè?ç­‰å??¸æ??¶é?'
        ];
        
        if ($error) {
            $result['error'] = $error;
        }
        
        return $result;
    }
    
    /**
     * ?–å?çµ±è??˜è? (?¨æ–¼å¿«é€Ÿç¸½è¦?
     * 
     * @param PDO $db è³‡æ?åº«é€??
     * @return array çµ±è??˜è?
     */
    public static function getSummary($db) {
        $system = self::getSystemStats24h($db);
        $dhcp = self::getDhcpStats24h($db);
        
        // è¨ˆç? DHCP ?´é??¯ç”¨??
        $total_availability = 0;
        $dhcp_count = 0;
        foreach ($dhcp as $ip => $stats) {
            if (isset($stats['availability_percent'])) {
                $total_availability += $stats['availability_percent'];
                $dhcp_count++;
            }
        }
        
        return [
            'system' => [
                'cpu_avg' => $system['cpu']['avg'] ?? null,
                'memory_avg' => $system['memory']['avg'] ?? null,
                'disk_avg' => $system['disk']['avg'] ?? null,
                'samples' => $system['cpu']['samples'] ?? 0
            ],
            'dhcp' => [
                'servers_monitored' => $dhcp_count,
                'overall_availability' => $dhcp_count > 0 ? round($total_availability / $dhcp_count, 2) : null
            ],
            'generated_at' => date('c')
        ];
    }
}

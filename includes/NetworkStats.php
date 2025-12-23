<?php
/**
 * NetworkStats.php
 * 
 * ç¶²è·¯æµé?çµ±è?é¡åˆ¥
 * ?¶é?ç¶²è·¯ä»‹é¢?„æ??è?è¨?
 * 
 * @author Jason Cheng
 * @created 2025-12-02
 */

class NetworkStats {
    
    /**
     * ?–å?ç¶²è·¯çµ±è?è³‡è?
     * 
     * @param string $interface ç¶²è·¯ä»‹é¢?ç¨±ï¼ˆé?è¨­è‡ª?•åµæ¸¬ï?
     * @return array ç¶²è·¯çµ±è?è³‡è?
     */
    public static function getStats($interface = null) {
        if ($interface === null) {
            $interface = self::getPrimaryInterface();
        }
        
        $current_stats = self::getCurrentStats($interface);
        
        return [
            'interface' => $interface,
            'current' => $current_stats,
            'last_24h' => self::calculate24hStats($interface, $current_stats)
        ];
    }
    
    /**
     * ?–å?ä¸»è?ç¶²è·¯ä»‹é¢
     * 
     * @return string ä»‹é¢?ç¨±
     */
    private static function getPrimaryInterface() {
        // ?—è©¦?–å??è¨­è·¯ç”±?„ç¶²è·¯ä???
        $output = shell_exec("ip route show default 2>/dev/null | awk '/default/ {print $5}'");
        if ($output && trim($output)) {
            return trim($output);
        }
        
        // ?™ç”¨ï¼šå?å¾—ç¬¬ä¸€?‹é? lo ä»‹é¢
        $interfaces = self::getAllInterfaces();
        foreach ($interfaces as $if => $stats) {
            if ($if !== 'lo') {
                return $if;
            }
        }
        
        return 'eth0'; // ?è¨­??
    }
    
    /**
     * ?–å??€?‰ç¶²è·¯ä???
     * 
     * @return array ?€?‰ä??¢å??¶çµ±è¨ˆè?è¨?
     */
    private static function getAllInterfaces() {
        $interfaces = [];
        
        if (file_exists('/proc/net/dev')) {
            $lines = file('/proc/net/dev', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            // è·³é??å…©è¡Œæ?é¡?
            for ($i = 2; $i < count($lines); $i++) {
                $line = $lines[$i];
                
                // è§??ä»‹é¢?ç¨±?Œçµ±è¨ˆè?è¨?
                if (preg_match('/^\s*(\w+):\s*(.+)$/', $line, $matches)) {
                    $interface = $matches[1];
                    $stats = preg_split('/\s+/', trim($matches[2]));
                    
                    if (count($stats) >= 8) {
                        $interfaces[$interface] = [
                            'rx_bytes' => (int)$stats[0],
                            'rx_packets' => (int)$stats[1],
                            'tx_bytes' => (int)$stats[8],
                            'tx_packets' => (int)$stats[9]
                        ];
                    }
                }
            }
        }
        
        return $interfaces;
    }
    
    /**
     * ?–å??‡å?ä»‹é¢?„ç•¶?çµ±è¨ˆè?è¨?
     * 
     * @param string $interface ä»‹é¢?ç¨±
     * @return array çµ±è?è³‡è?
     */
    private static function getCurrentStats($interface) {
        $interfaces = self::getAllInterfaces();
        
        if (isset($interfaces[$interface])) {
            return $interfaces[$interface];
        }
        
        return [
            'rx_bytes' => 0,
            'rx_packets' => 0,
            'tx_bytes' => 0,
            'tx_packets' => 0
        ];
    }
    
    /**
     * è¨ˆç? 24 å°æ?æµé?çµ±è?
     * 
     * @param string $interface ä»‹é¢?ç¨±
     * @param array $current_stats ?¶å?çµ±è?è³‡è?
     * @return array 24 å°æ?çµ±è?ï¼ˆç°¡?–ç?ï¼šè??ç•¶?å€¼ï?
     */
    private static function calculate24hStats($interface, $current_stats) {
        // TODO: å¯¦ä??Ÿæ­£??24 å°æ?çµ±è??€è¦è??™åº«?²å?æ­·å²è³‡æ?
        // ?®å??ˆè??ç•¶?ç´¯ç©å€¼ä??ºç¤º??
        
        // ?¯ä»¥å¾è??™åº«è®€??24 å°æ??ç?è³‡æ?ä¸¦è?ç®—å·®??
        // ?™è£¡?ˆè??ç•¶?ç?ç´¯ç???
        return [
            'rx_bytes' => $current_stats['rx_bytes'],
            'tx_bytes' => $current_stats['tx_bytes'],
            'rx_packets' => $current_stats['rx_packets'],
            'tx_packets' => $current_stats['tx_packets'],
            'rx_mb' => round($current_stats['rx_bytes'] / 1024 / 1024, 2),
            'tx_mb' => round($current_stats['tx_bytes'] / 1024 / 1024, 2),
            'note' => 'ç´¯ç?æµé?ï¼ˆé?å¯¦ä?æ­·å²è³‡æ??²å?ä»¥è?ç®—ç?æ­?? 24h å·®ç•°ï¼?
        ];
    }
    
    /**
     * ?²å?æ­·å²çµ±è?è³‡è??°è??™åº«ï¼ˆé?è¦æ•´??phpIPAM è³‡æ?åº«ï?
     * 
     * @param string $interface ä»‹é¢?ç¨±
     * @param array $stats çµ±è?è³‡è?
     * @return bool ?¯å¦?å?
     */
    public static function saveToDatabase($interface, $stats) {
        // TODO: å¯¦ä?è³‡æ?åº«å„²å­˜é?è¼?
        // INSERT INTO health_check_history (timestamp, interface, rx_bytes, tx_bytes, ...)
        // VALUES (NOW(), ?, ?, ?, ...)
        
        return true;
    }
}

<?php
/**
 * DhcpChecker.php
 * 
 * DHCP ä¼ºæ??¨é€??æª¢æŸ¥é¡žåˆ¥
 * ä½¿ç”¨ ping æª¢æŸ¥ DHCP ä¼ºæ??¨ç?????€??
 * 
 * @author Jason Cheng
 * @created 2025-12-02
 */

class DhcpChecker {
    
    /**
     * æª¢æŸ¥?®ä??–å???DHCP ä¼ºæ???
     * 
     * @param string|array $ips IP ä½å?ï¼ˆå?ä¸²æ????ï¼?
     * @param int $count Ping æ¬¡æ•¸
     * @param int $timeout ?¾æ?ç§’æ•¸
     * @return array æª¢æŸ¥çµæ?
     */
    public static function check($ips, $count = 4, $timeout = 2) {
        // æ¨™æ??–è¼¸?¥ç‚º???
        if (is_string($ips)) {
            // ?¯æ´?—è??†é???IP å­—ä¸²
            $ips = array_map('trim', explode(',', $ips));
        }
        
        $results = [];
        foreach ($ips as $ip) {
            $results[] = self::checkSingle($ip, $count, $timeout);
        }
        
        return $results;
    }
    
    /**
     * æª¢æŸ¥ DHCP ä¼ºæ??¨ä¸¦?…å« 24 å°æ?æ­·å²çµ±è?
     * 
     * @param string|array $ips IP ä½å?
     * @param PDO $db è³‡æ?åº«é€?? (?¯é¸)
     * @param int $count Ping æ¬¡æ•¸
     * @param int $timeout ?¾æ?ç§’æ•¸
     * @return array æª¢æŸ¥çµæ? (?«æ­·?²çµ±è¨?
     */
    public static function checkWithHistory($ips, $db = null, $count = 4, $timeout = 2) {
        // ?ˆåŸ·è¡Œå³?‚æª¢??
        $current = self::check($ips, $count, $timeout);
        
        // å¦‚æ?æ²’æ?è³‡æ?åº«é€??ï¼Œç›´?¥è??žå³?‚ç???
        if ($db === null) {
            return $current;
        }
        
        // è¼‰å…¥çµ±è?è¨ˆç???
        require_once(__DIR__ . '/StatsCalculator.php');
        
        // ?ºæ??‹ç??œå???24 å°æ?çµ±è?
        foreach ($current as &$result) {
            $stats = StatsCalculator::getDhcpStats24h($db, $result['ip']);
            $result['stats_24h'] = $stats;
        }
        
        return $current;
    }
    
    /**
     * æª¢æŸ¥?®ä? DHCP ä¼ºæ???
     * 
     * @param string $ip IP ä½å?
     * @param int $count Ping æ¬¡æ•¸
     * @param int $timeout ?¾æ?ç§’æ•¸
     * @return array æª¢æŸ¥çµæ?
     */
    private static function checkSingle($ip, $count, $timeout) {
        // é©—è? IP ?¼å?
        if (!self::validateIp($ip)) {
            return [
                'ip' => $ip,
                'status' => 'error',
                'reachable' => false,
                'error' => 'Invalid IP address format'
            ];
        }
        
        // ?·è? ping æª¢æŸ¥
        $ping_result = self::ping($ip, $count, $timeout);
        
        return array_merge(['ip' => $ip], $ping_result);
    }
    
    /**
     * é©—è? IP ä½å??¼å?
     * 
     * @param string $ip IP ä½å?
     * @return bool ?¯å¦?‰æ?
     */
    private static function validateIp($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
    
    /**
     * ?·è? ping ?‡ä»¤
     * 
     * @param string $ip IP ä½å?
     * @param int $count Ping æ¬¡æ•¸
     * @param int $timeout ?¾æ?ç§’æ•¸
     * @return array Ping çµæ?
     */
    private static function ping($ip, $count, $timeout) {
        // å®‰å…¨é©—è?ï¼šç¢ºä¿?IP ?¼å?æ­?¢ºï¼ˆé˜²æ­¢æ?ä»¤æ³¨?¥ï?
        $ip = escapeshellarg($ip);
        
        // å»ºç? ping ?‡ä»¤
        $command = sprintf(
            "ping -c %d -W %d %s 2>&1",
            (int)$count,
            (int)$timeout,
            $ip
        );
        
        // ?·è??‡ä»¤
        $output = [];
        $return_code = 0;
        exec($command, $output, $return_code);
        
        // è§??çµæ?
        return self::parsePingOutput($output, $return_code, $count);
    }
    
    /**
     * è§?? ping ?‡ä»¤è¼¸å‡º
     * 
     * @param array $output ?‡ä»¤è¼¸å‡º
     * @param int $return_code è¿”å?ç¢?
     * @param int $expected_count ?æ? ping æ¬¡æ•¸
     * @return array è§??å¾Œç?çµæ?
     */
    private static function parsePingOutput($output, $return_code, $expected_count) {
        $output_text = implode("\n", $output);
        
        // Ping å¤±æ?
        if ($return_code !== 0) {
            // ?—è©¦å¾žè¼¸?ºä¸­?å??¯èª¤è¨Šæ¯
            $error = 'Host unreachable';
            if (preg_match('/(Destination Host Unreachable|Network is unreachable)/i', $output_text, $matches)) {
                $error = $matches[1];
            }
            
            return [
                'status' => 'error',
                'reachable' => false,
                'error' => $error
            ];
        }
        
        // è§??çµ±è?è³‡è?
        // ç¯„ä?: "4 packets transmitted, 4 received, 0% packet loss, time 3003ms"
        if (preg_match('/(\d+) packets transmitted, (\d+) received, ([\d.]+)% packet loss/', $output_text, $matches)) {
            $transmitted = (int)$matches[1];
            $received = (int)$matches[2];
            $packet_loss = (float)$matches[3];
        } else {
            // å¦‚æ??¡æ?è§??ï¼Œå?è¨­å…¨?¨æ???
            $transmitted = $expected_count;
            $received = $expected_count;
            $packet_loss = 0;
        }
        
        // è§??å¹³å?å»¶é²
        // ç¯„ä?: "rtt min/avg/max/mdev = 0.123/0.145/0.167/0.015 ms"
        $avg_latency = 0;
        if (preg_match('/rtt min\/avg\/max\/mdev = ([\d.]+)\/([\d.]+)\/([\d.]+)\/([\d.]+) ms/', $output_text, $matches)) {
            $avg_latency = (float)$matches[2];
        }
        
        // ?¤æ–·?¯å¦?¯é€??ï¼ˆè‡³å°‘æ”¶?°ä??‹å??‰ï?
        $reachable = $received > 0;
        
        return [
            'status' => $reachable ? 'online' : 'offline',
            'reachable' => $reachable,
            'response_time_ms' => round($avg_latency, 2),
            'avg_latency_ms' => round($avg_latency, 2),
            'packet_loss_percent' => round($packet_loss, 2),
            'packets_sent' => $transmitted,
            'packets_received' => $received,
            'check_method' => 'ping'
        ];
    }
}

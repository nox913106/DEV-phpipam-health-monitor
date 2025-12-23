<?php
/**
 * SystemInfo.php
 * 
 * ç³»çµ±è³‡è??¶é?é¡åˆ¥
 * ?¶é? phpIPAM ä¸»æ??„ç³»çµ±è?æºä½¿?¨æ?æ³?
 * 
 * @author Jason Cheng
 * @created 2025-12-02
 */

class SystemInfo {
    
    /**
     * ?–å?å®Œæ•´?„ç³»çµ±è?è¨?
     * 
     * @return array ç³»çµ±è³‡è????
     */
    public static function getAll() {
        return [
            'host_info' => self::getHostInfo(),
            'system_resources' => self::getSystemResources()
        ];
    }
    
    /**
     * ?–å?å®Œæ•´?„ç³»çµ±è?è¨?(??24 å°æ?æ­·å²çµ±è?)
     * 
     * @param PDO $db è³‡æ?åº«é€?? (?¯é¸)
     * @return array ç³»çµ±è³‡è???? (?«æ­·?²çµ±è¨?
     */
    public static function getAllWithHistory($db = null) {
        $current = self::getAll();
        
        // å¦‚æ?æ²’æ?è³‡æ?åº«é€??ï¼Œç›´?¥è??å³?‚ç???
        if ($db === null) {
            return $current;
        }
        
        // è¼‰å…¥çµ±è?è¨ˆç???
        require_once(__DIR__ . '/StatsCalculator.php');
        
        // ?–å? 24 å°æ?çµ±è?
        $stats24h = StatsCalculator::getSystemStats24h($db);
        
        // ?´å?çµ±è??°å?è³‡æ?
        $current['system_resources']['cpu']['stats_24h'] = $stats24h['cpu'];
        $current['system_resources']['memory']['stats_24h'] = $stats24h['memory'];
        $current['system_resources']['disk']['stats_24h'] = $stats24h['disk'];
        $current['system_resources']['stats_period_hours'] = $stats24h['period_hours'] ?? 24;
        $current['system_resources']['has_historical_data'] = $stats24h['has_data'] ?? false;
        
        return $current;
    }
    
    /**
     * ?–å?ä¸»æ??ºæœ¬è³‡è?
     * 
     * @return array ä¸»æ?è³‡è?
     */
    public static function getHostInfo() {
        $hostname = gethostname();
        $uptime_seconds = self::getUptime();
        
        return [
            'hostname' => $hostname,
            'os' => self::getOSInfo(),
            'kernel' => php_uname('r'),
            'uptime_seconds' => $uptime_seconds,
            'uptime_formatted' => self::formatUptime($uptime_seconds)
        ];
    }
    
    /**
     * ?–å?ä½œæ¥­ç³»çµ±è³‡è?
     * 
     * @return string OS è³‡è?
     */
    private static function getOSInfo() {
        if (file_exists('/etc/os-release')) {
            $os_release = parse_ini_file('/etc/os-release');
            return $os_release['PRETTY_NAME'] ?? php_uname('s') . ' ' . php_uname('r');
        }
        return php_uname('s') . ' ' . php_uname('r');
    }
    
    /**
     * ?–å?ç³»çµ±?‹è??‚é?ï¼ˆç?ï¼?
     * 
     * @return int ?‹è??‚é?ï¼ˆç?ï¼?
     */
    private static function getUptime() {
        if (file_exists('/proc/uptime')) {
            $uptime_content = file_get_contents('/proc/uptime');
            $uptime_array = explode(' ', $uptime_content);
            return (int)$uptime_array[0];
        }
        return 0;
    }
    
    /**
     * ?¼å??–é?è¡Œæ???
     * 
     * @param int $seconds ç§’æ•¸
     * @return string ?¼å??–ç??‚é?å­—ä¸²
     */
    private static function formatUptime($seconds) {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        return sprintf("%d days %d hours %d minutes", $days, $hours, $minutes);
    }
    
    /**
     * ?–å?ç³»çµ±è³‡æ?ä½¿ç”¨?…æ?
     * 
     * @return array è³‡æ?ä½¿ç”¨?…æ?
     */
    public static function getSystemResources() {
        return [
            'cpu' => self::getCpuUsage(),
            'memory' => self::getMemoryUsage(),
            'disk' => self::getDiskUsage()
        ];
    }
    
    /**
     * ?–å? CPU ä½¿ç”¨??
     * 
     * @return array CPU è³‡è?
     */
    private static function getCpuUsage() {
        $cpu_usage = 0;
        $cores = 1;
        $load_average = [0, 0, 0];
        
        // ?–å? CPU ?¸å???
        if (file_exists('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            preg_match_all('/^processor/m', $cpuinfo, $matches);
            $cores = count($matches[0]);
        }
        
        // ?–å?è² è?å¹³å?
        if (function_exists('sys_getloadavg')) {
            $load_average = sys_getloadavg();
        }
        
        // è¨ˆç? CPU ä½¿ç”¨?‡ï??ºæ–¼ 1 ?†é?è² è?å¹³å?ï¼?
        $cpu_usage = ($load_average[0] / $cores) * 100;
        
        return [
            'usage_percent' => round($cpu_usage, 2),
            'cores' => $cores,
            'load_average' => [
                round($load_average[0], 2),
                round($load_average[1], 2),
                round($load_average[2], 2)
            ]
        ];
    }
    
    /**
     * ?–å?è¨˜æ†¶é«”ä½¿?¨æ?æ³?
     * 
     * @return array è¨˜æ†¶é«”è?è¨?
     */
    private static function getMemoryUsage() {
        $mem_total = 0;
        $mem_free = 0;
        $mem_available = 0;
        
        if (file_exists('/proc/meminfo')) {
            $meminfo = file_get_contents('/proc/meminfo');
            
            // è§?? meminfo
            if (preg_match('/MemTotal:\s+(\d+)/', $meminfo, $matches)) {
                $mem_total = (int)$matches[1]; // KB
            }
            if (preg_match('/MemFree:\s+(\d+)/', $meminfo, $matches)) {
                $mem_free = (int)$matches[1]; // KB
            }
            if (preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $matches)) {
                $mem_available = (int)$matches[1]; // KB
            }
        }
        
        $mem_used = $mem_total - $mem_available;
        $usage_percent = $mem_total > 0 ? ($mem_used / $mem_total) * 100 : 0;
        
        return [
            'total_mb' => round($mem_total / 1024, 2),
            'used_mb' => round($mem_used / 1024, 2),
            'free_mb' => round($mem_available / 1024, 2),
            'usage_percent' => round($usage_percent, 2)
        ];
    }
    
    /**
     * ?–å?ç¡¬ç?ä½¿ç”¨?…æ?
     * 
     * @param string $path è¦æª¢?¥ç?è·¯å?ï¼ˆé?è¨­ç‚º?¹ç›®?„ï?
     * @return array ç¡¬ç?è³‡è?
     */
    private static function getDiskUsage($path = '/') {
        $total = disk_total_space($path);
        $free = disk_free_space($path);
        $used = $total - $free;
        $usage_percent = $total > 0 ? ($used / $total) * 100 : 0;
        
        return [
            'path' => $path,
            'total_gb' => round($total / 1024 / 1024 / 1024, 2),
            'used_gb' => round($used / 1024 / 1024 / 1024, 2),
            'free_gb' => round($free / 1024 / 1024 / 1024, 2),
            'usage_percent' => round($usage_percent, 2)
        ];
    }
}

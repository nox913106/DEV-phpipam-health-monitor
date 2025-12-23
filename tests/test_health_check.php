<?php
/**
 * test_health_check.php
 * 
 * Ê∏¨Ë©¶?•Â∫∑Ê™¢Êü•?üËÉΩ
 * 
 * ?∑Ë??πÂ?: php tests/test_health_check.php
 * 
 * @author Jason Cheng
 * @created 2025-12-02
 */

// Ë®≠Â??ØË™§?±Â?
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ÂºïÂÖ• Controller
require_once(__DIR__ . '/../api/HealthCheckController.php');

echo "========================================\n";
echo "phpIPAM ?•Â∫∑Ê™¢Êü•Ê∏¨Ë©¶\n";
echo "========================================\n\n";

// Ê∏¨Ë©¶ 1: ?°Â??∏Ô?‰ΩøÁî®?êË®≠ DHCP ?óË°®Ôº?
echo "[Ê∏¨Ë©¶ 1] ?∑Ë??•Â∫∑Ê™¢Êü•ÔºàÈ?Ë®≠Â??∏Ô?\n";
echo "----------------------------------------\n";
$result1 = HealthCheckController::execute();
echo json_encode($result1, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

// Ê∏¨Ë©¶ 2: ?áÂ??Æ‰? DHCP ‰º∫Ê???
echo "[Ê∏¨Ë©¶ 2] Ê™¢Êü•?Æ‰? DHCP ‰º∫Ê??®\n";
echo "----------------------------------------\n";
$result2 = HealthCheckController::execute(['dhcp_server_ip' => '172.16.5.196']);
echo json_encode($result2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

// Ê∏¨Ë©¶ 3: ?áÂ?Â§öÂÄ?DHCP ‰º∫Ê???
echo "[Ê∏¨Ë©¶ 3] Ê™¢Êü•Â§öÂÄ?DHCP ‰º∫Ê??®\n";
echo "----------------------------------------\n";
$result3 = HealthCheckController::execute([
    'dhcp_server_ip' => '172.16.5.196,172.23.127.169'
]);
echo json_encode($result3, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

// Ê∏¨Ë©¶ 4: Ê∏¨Ë©¶?ãÂà•È°ûÂà•
echo "[Ê∏¨Ë©¶ 4] Ê∏¨Ë©¶ SystemInfo È°ûÂà•\n";
echo "----------------------------------------\n";
$system_info = SystemInfo::getAll();
echo json_encode($system_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

echo "[Ê∏¨Ë©¶ 5] Ê∏¨Ë©¶ NetworkStats È°ûÂà•\n";
echo "----------------------------------------\n";
$network_stats = NetworkStats::getStats();
echo json_encode($network_stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

echo "[Ê∏¨Ë©¶ 6] Ê∏¨Ë©¶ DhcpChecker È°ûÂà•\n";
echo "----------------------------------------\n";
$dhcp_results = DhcpChecker::check(['8.8.8.8', '1.1.1.1']); // Ê∏¨Ë©¶?¨È? DNS
echo json_encode($dhcp_results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

// ?àËÉΩÊ∏¨Ë©¶
echo "[?àËÉΩÊ∏¨Ë©¶] Ê∏¨Ë©¶?∑Ë??ÇÈ?\n";
echo "----------------------------------------\n";
$iterations = 10;
$times = [];

for ($i = 0; $i < $iterations; $i++) {
    $start = microtime(true);
    HealthCheckController::execute(['dhcp_server_ip' => '172.16.5.196']);
    $times[] = microtime(true) - $start;
}

$avg_time = array_sum($times) / count($times);
$min_time = min($times);
$max_time = max($times);

echo sprintf("?∑Ë?Ê¨°Êï∏: %d\n", $iterations);
echo sprintf("Âπ≥Â??ÇÈ?: %.3f Áßí\n", $avg_time);
echo sprintf("?ÄÂ∞èÊ??? %.3f Áßí\n", $min_time);
echo sprintf("?ÄÂ§ßÊ??? %.3f Áßí\n", $max_time);
echo "\n";

echo "========================================\n";
echo "Ê∏¨Ë©¶ÂÆåÊ?ÔºÅ\n";
echo "========================================\n";

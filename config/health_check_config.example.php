<?php
/**
 * health_check_config.example.php
 * 
 * ?ех║╖цквцЯе?ЯшГ╜?Нч╜оцкФч?ф╛?
 * ф╜┐чФи?Вш?шдЗшг╜??health_check_config.php ф╕жф┐о?╣шинхо?
 * 
 * @author Jason Cheng
 * @created 2025-12-02
 */

return [
    // DHCP ф╝║ц??их?шбия?IP => ф╕╗ц??Нчи▒я╝?
    'default_dhcp_servers' => [
        '172.16.5.196'   => 'DHCP-CH-HQ2',           // х╜░х?ч╕╜щГи2
        '172.23.13.10'   => 'DHCP-CH-PGT',           // х╜░х??Фщ╣╜
        '172.23.174.5'   => 'DHCP-TC-HQ',            // ?░ф╕нч╕╜щГи
        '172.23.199.150' => 'DHCP-TC-UAIC',          // ?░ф╕н
        '172.23.110.1'   => 'DHCP-TP-XY',            // ?░х?
        '172.23.94.254'  => 'DHCP-TP-BaoYu-CoreSW'   // ?░х?хп╢ш?
    ],
    
    // Ping цквцЯе?ГцХ╕
    'ping_count' => 4,           // Ping цмбцХ╕
    'ping_timeout' => 2,         // ?╛ц?чзТцХ╕
    
    // ч╢▓ш╖пф╗ЛщЭвшинх?
    'network_interface' => null, // null = ?кх??╡ц╕мф╕╗ш?ф╗ЛщЭв
    
    // чбмч?цквцЯеш╖пх?
    'disk_check_path' => '/',    // шжБцкв?еч?чбмч?ш╖пх?
    
    // х┐лх?шинх?
    'cache_enabled' => false,    // ?пхРж?ЯчФих┐лх?
    'cache_ttl' => 60,          // х┐лх??Вщ?я╝Ич?я╝?
    
    // ?еш?шинх?
    'log_enabled' => true,       // ?пхРжшиШщ??еш?
    'log_path' => '/var/log/phpipam/health_check.log',
    
    // хоЙхЕи?зшинхо?
    'allowed_app_ids' => ['mcp'], // ?Бши▒хнШх???APP ID ?Чшбия╝Ичй║??? = ?ищГи?Бши▒я╝?
];

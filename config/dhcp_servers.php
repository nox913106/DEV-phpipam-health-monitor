<?php
/**
 * dhcp_servers.php - DHCP ä¼ºæ??¨é?ç½®æ?
 * 
 * æ­¤æ?æ¡ˆå?ç¾©è???§??DHCP ä¼ºæ??¨å?è¡?
 * ?¯éš¨?‚ä¿®?¹ï??¡é??å??å?
 * 
 * @author Jason Cheng
 * @created 2025-12-19
 */

return [
    // å½°å??€??
    [
        'ip' => '172.16.5.196',
        'hostname' => 'DHCP-CH-HQ2',
        'location' => 'å½°å?ç¸½éƒ¨2',
        'enabled' => true
    ],
    [
        'ip' => '172.23.13.10',
        'hostname' => 'DHCP-CH-PGT',
        'location' => 'å½°å??”é¹½',
        'enabled' => true
    ],
    
    // ?°ä¸­?€??
    [
        'ip' => '172.23.174.5',
        'hostname' => 'DHCP-TC-HQ',
        'location' => '?°ä¸­ç¸½éƒ¨',
        'enabled' => true
    ],
    [
        'ip' => '172.23.199.150',
        'hostname' => 'DHCP-TC-UAIC',
        'location' => '?°ä¸­',
        'enabled' => true
    ],
    
    // ?°å??€??
    [
        'ip' => '172.23.110.1',
        'hostname' => 'DHCP-TP-XY',
        'location' => '?°å?',
        'enabled' => true
    ],
    [
        'ip' => '172.23.94.254',
        'hostname' => 'DHCP-TP-BaoYu-CoreSW',
        'location' => '?°å?å¯¶è?',
        'enabled' => true
    ],
    
    // ?°å?ä¼ºæ??¨ç?ä¾?(è¨?enabled=false ?«æ??œç”¨)
    // [
    //     'ip' => '10.1.1.1',
    //     'hostname' => 'DHCP-NEW',
    //     'location' => '?°æ?é»?,
    //     'enabled' => false
    // ],
];

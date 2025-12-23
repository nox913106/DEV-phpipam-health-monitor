# phpIPAM ?¥åº·æª¢æŸ¥?Ÿèƒ½?¨ç½²ç´€??

**?¨ç½²?¥æ?**: 2025-12-19  
**?¨ç½²?®æ?**: Docker phpIPAM ?°å?  
**?Ÿèƒ½**: 24 å°æ?æ­·å²çµ±è? (CPU/è¨˜æ†¶é«?ç£ç?/DHCP)

---

## ?¨ç½²æ­¥é?

### Step 1: ?°å?ç¢ºè?
- [ ] ç¢ºè? phpIPAM Docker å®¹å™¨?€??
- [ ] ç¢ºè? MariaDB å®¹å™¨?€??
- [ ] ç¢ºè??›è??®é?

**?·è??‚é?**: å¾…è??? 
**?·è?çµæ?**: å¾…è???

---

### Step 2: å»ºç?è³‡æ?è¡?
- [ ] ?????MariaDB å®¹å™¨
- [ ] ?·è? health_check_tables.sql

**?·è??‚é?**: å¾…è??? 
**?·è?çµæ?**: å¾…è???

---

### Step 3: ä¸Šå‚³ç¨‹å?æª”æ?
- [ ] è¤‡è£½ includes/ ?®é?
- [ ] è¤‡è£½ scripts/ ?®é?
- [ ] è¤‡è£½ api/ ?®é?
- [ ] è¨­å?æª”æ?æ¬Šé?

**?·è??‚é?**: å¾…è??? 
**?·è?çµæ?**: å¾…è???

---

### Step 4: è¨­å? Cron ?’ç?
- [ ] ?¨å®¹?¨å…§è¨­å? cron job
- [ ] é©—è? cron ?·è?

**?·è??‚é?**: å¾…è??? 
**?·è?çµæ?**: å¾…è???

---

### Step 5: é©—è??¨ç½²
- [ ] æ¸¬è©¦ API ?æ?
- [ ] ç¢ºè?è³‡æ??¶é??Ÿèƒ½

**?·è??‚é?**: å¾…è??? 
**?·è?çµæ?**: å¾…è???

---

## ?¨ç½²ç´€??

### ?°å?è³‡è?
```
ä¼ºæ??? stwphpipam-p
phpIPAM Web å®¹å™¨: phpipam_phpipam-web_1 (phpipam/phpipam-www:v1.7.4)
phpIPAM Cron å®¹å™¨: phpipam_phpipam-cron_1 (phpipam/phpipam-cron:v1.7.4)
MariaDB å®¹å™¨: phpipam_phpipam-mariadb_1 (mariadb:latest, port 3306)
Nginx å®¹å™¨: nginx_nginx_1 (?å?ä»??)
```

### ?·è?ç´€??

#### [08:19] Step 1 - ?°å?ç¢ºè?
```bash
root@stwphpipam-p:/home/chadmin# docker ps | grep -i ipam
5193192bcf13   phpipam/phpipam-cron:v1.7.4   ... Up 2 days   phpipam_phpipam-cron_1
e4d1b2afde17   phpipam/phpipam-www:v1.7.4    ... Up 2 days   phpipam_phpipam-web_1
bb79db9903d1   mariadb:latest                ... Up 2 days   phpipam_phpipam-mariadb_1
```
???€?‰å®¹?¨é?è¡Œæ­£å¸?

**?›è??®é?**:
```
/var/lib/docker/volumes/phpipam_phpipam-ca/_data -> /usr/local/share/ca-certificates
/var/lib/docker/volumes/phpipam_phpipam-logo/_data -> /phpipam/css/images/logo
```

**Docker Compose**: `/opt/phpipam/docker-compose.yml`
**è³‡æ?åº«ä¸»æ©?*: phpipam-mariadb (å®¹å™¨?§éƒ¨ç¶²è·¯)
**è³‡æ?åº«å?ç¢?*: my_secret_phpipam_pass

---

#### [08:28] Step 2 - å»ºç?è³‡æ?è¡?
```bash
# å»ºç? SQL æª”æ?ä¸¦è?è£½åˆ°å®¹å™¨
docker cp /tmp/health_check_tables.sql phpipam_phpipam-mariadb_1:/tmp/
Successfully copied 3.07kB to phpipam_phpipam-mariadb_1:/tmp/

# ?·è? SQL
docker exec -i phpipam_phpipam-mariadb_1 mariadb -u phpipam -pmy_secret_phpipam_pass phpipam < /tmp/health_check_tables.sql

# é©—è?
docker exec -i phpipam_phpipam-mariadb_1 mariadb -u phpipam -pmy_secret_phpipam_pass phpipam -e "SHOW TABLES LIKE 'health_check%';"
Tables_in_phpipam (health_check%)
health_check_dhcp_history
health_check_system_history
```
??è³‡æ?è¡¨å»ºç«‹æ???

---

#### [08:30] Step 3 - ä¸Šå‚³ç¨‹å?æª”æ?
```bash
# ?¨ä¸»æ©Ÿå»ºç«‹æš«å­˜ç›®??
mkdir -p /tmp/health_check/{includes,scripts,api}

# å»ºç? PHP æª”æ?
# - StatsCalculator.php (5175 bytes)
# - HistoryCollector.php (4667 bytes)
# - collect_stats.php (1969 bytes)

# è¤‡è£½?°å®¹??
docker cp /tmp/health_check phpipam_phpipam-cron_1:/
Successfully copied 17.4kB to phpipam_phpipam-cron_1:/
```
??æª”æ?ä¸Šå‚³?å?

---

#### [08:34] Step 4 - æ¸¬è©¦?³æœ¬
```bash
docker exec phpipam_phpipam-cron_1 php /health_check/scripts/collect_stats.php

=== Health Check Collector ===
Time: 2025-12-19 08:34:33
[OK] DB connected
[OK] System: CPU=0.05%, Mem=16.5%, Disk=59.15%
[OK] DHCP: 6 servers checked
     - 172.16.5.196: Online (0.287ms)
     - 172.23.13.10: Online (27.631ms)
     - 172.23.174.5: Online (8.269ms)
     - 172.23.199.150: Online (4.161ms)
     - 172.23.110.1: Online (6.796ms)
     - 172.23.94.254: Online (39.825ms)
Done in 18176.25ms
```
???³æœ¬?·è??å?ï¼Œè??™å·²å¯«å…¥è³‡æ?åº?

---

#### [08:37] Step 5 - è¨­å? Cron Job
```bash
docker exec phpipam_phpipam-cron_1 sh -c 'echo "*/5 * * * * php /health_check/scripts/collect_stats.php >> /var/log/health_check.log 2>&1" >> /etc/crontabs/root'
```
??Cron å·²è¨­å®šï?æ¯?5 ?†é??·è?ä¸€æ¬?

---

#### [11:08] Step 6 - ?¨ç½²??§ Dashboard
```bash
# å»ºç? API ç«¯é???Dashboard HTML
mkdir -p /tmp/health_check/dashboard
# å»ºç? api_stats.php ??index.html

# è¤‡è£½??web å®¹å™¨
docker cp /tmp/health_check phpipam_phpipam-web_1:/
Successfully copied 28.2kB to phpipam_phpipam-web_1:/

# è¤‡è£½??phpIPAM web root
docker exec phpipam_phpipam-web_1 cp -r /health_check/dashboard /phpipam/health_dashboard
docker exec phpipam_phpipam-web_1 cp -r /health_check/api /phpipam/health_dashboard/
docker exec phpipam_phpipam-web_1 cp -r /health_check/includes /phpipam/health_dashboard/
```
??Dashboard ?¨ç½²?å?

**Dashboard URL**: https://ipam-tw.pouchen.com/health_dashboard/

---

## ?¨ç½²çµæ?

| ?…ç›® | ?€??| èªªæ? |
|------|------|------|
| è³‡æ?è¡?| ??| `health_check_system_history`, `health_check_dhcp_history` |
| è³‡æ??¶é? | ??| Cron job æ¯?5 ?†é??·è? |
| API ç«¯é? | ??| `/health_dashboard/api/api_stats.php` |
| Dashboard | ??| ?²ç??–æ­£å¸¸é¡¯ç¤?|

---

**?¨ç½²å®Œæ??‚é?**: 2025-12-19 11:11

---

## v2.1 ?´æ–°ç´€??(2025-12-23)

### ?°å??Ÿèƒ½ï¼šå??§æ?æ®µæŸ¥è©?

#### API ?´æ–°
- ?°å? `start_time` ??`end_time` ?ƒæ•¸?¯æ´?ªè??‚é?ç¯„å??¥è©¢
- ?´æ–° `getSystemHistory()` ??`getDhcpHistory()` ?½æ•¸

**API ç¯„ä?**:
```bash
# ?ºå??‚æ®µ (3 å°æ?)
?action=system_history&hours=3

# ?ªè??‚é?ç¯„å?
?action=system_history&start_time=2025-12-22 00:00&end_time=2025-12-22 05:00
```

#### Dashboard ?´æ–°
- ?°å??‚æ®µ?¸æ???(1h/3h/6h/8h/12h/24h + ?ªè?ç¯„å?)
- ?°å??ªè??‚é?ç¯„å??¸æ???
- ?°å? `changePeriod()` ??`applyCustomRange()` JavaScript ?½æ•¸
- å®Œæ•´å¤šè?ç³»æ”¯??(EN/ç°¡ä¸­/ç¹ä¸­)

#### ?´æ–°æ­¥é?
```bash
# è¤‡è£½?´æ–°?„æ?æ¡ˆåˆ°å®¹å™¨
docker cp api_stats.php phpipam_phpipam-web_1:/phpipam/health_dashboard/api/
docker cp index.html phpipam_phpipam-web_1:/phpipam/health_dashboard/

# é©—è?
curl -sk "https://ipam-tw.pouchen.com/health_dashboard/api/api_stats.php?action=system_history&hours=3"
```

**?´æ–°?‚é?**: 2025-12-23 11:50

---

### phpIPAM Tools ?´å? (2025-12-23 13:20)

#### ?´å?æ­¥é?
1. å»ºç?å·¥å…·?®é? `/phpipam/app/tools/health-monitor/`
2. å»ºç? index.php (iframe åµŒå…¥ Dashboard)
3. æ·»å? `$private_subpages = ["health-monitor"];` ??config.php
4. æ·»å? `"health-monitor"` ??tools-menu-config.php ??$tools_menu_items

#### å­˜å??¹å?
- phpIPAM ?¸å–®ï¼?*Tools ??Custom Tools ??Health-monitor**
- ?´æ¥ URLï¼š`https://ipam-tw.pouchen.com/index.php?page=tools&section=health-monitor`

#### ?ä??–è…³??
```bash
# å®¹å™¨?å?å¾ŒåŸ·è¡Œæ¢å¾©è¨­å®?
./scripts/setup_phpipam_integration.sh
```

**?´å??‚é?**: 2025-12-23 13:20


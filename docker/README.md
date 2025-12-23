# phpIPAM Health Dashboard - Docker ä¸€?µéƒ¨ç½²å?

## ?? æ¦‚è¿°

?™æ˜¯ phpIPAM ?¥åº·æª¢æŸ¥??§ç³»çµ±??Docker Compose ä¸€?µéƒ¨ç½²å?ï¼Œå??«ï?
- phpIPAM ä¸»ç?å¼?(v1.7.4)
- ?¥åº·æª¢æŸ¥ Dashboardï¼ˆå« 24 å°æ?æ­·å²çµ±è?ï¼?
- DHCP ä¼ºæ??¨å??‹ç®¡??UI
- ?ªå?è³‡æ??¶é? Cron Job

## ?? å¿«é€Ÿéƒ¨ç½?

### 1. è¤‡è£½å°ˆæ?
```bash
git clone https://github.com/YOUR_USERNAME/phpipam-health-dashboard.git
cd phpipam-health-dashboard/docker
```

### 2. ä¿®æ”¹?ç½®
```bash
# è¤‡è£½?°å?è®Šæ•¸ç¯„æœ¬
cp .env.example .env

# ç·¨è¼¯?ç½®
vi .env
```

### 3. ?Ÿå??å?
```bash
docker-compose up -d
```

### 4. ?å??–è??™åº«
```bash
# ç­‰å??å??Ÿå?å®Œæ?å¾ŒåŸ·è¡?
docker-compose exec phpipam-web php /phpipam/health_dashboard/scripts/init_database.php
```

### 5. è¨ªå?ç³»çµ±
- phpIPAM: http://YOUR_SERVER/
- Health Dashboard: http://YOUR_SERVER/health_dashboard/

## ?? ?®é?çµæ?

```
docker/
?œâ??€ docker-compose.yml       # Docker Compose ?ç½®
?œâ??€ .env.example             # ?°å?è®Šæ•¸ç¯„æœ¬
?œâ??€ init/
??  ?”â??€ health_check_tables.sql  # è³‡æ?è¡¨å?å§‹å? SQL
?”â??€ health_dashboard/
    ?œâ??€ index.html           # Dashboard ä¸»é?
    ?œâ??€ api/
    ??  ?œâ??€ api_stats.php    # çµ±è? API
    ??  ?”â??€ api_dhcp_config.php  # DHCP ?ç½® API
    ?œâ??€ config/
    ??  ?”â??€ dhcp_servers.json    # DHCP ä¼ºæ??¨é?ç½?
    ?œâ??€ includes/
    ??  ?œâ??€ HistoryCollector.php
    ??  ?”â??€ StatsCalculator.php
    ?”â??€ scripts/
        ?”â??€ collect_stats.php    # è³‡æ??¶é??³æœ¬
```

## ?™ï? ?ç½®èªªæ?

### ?°å?è®Šæ•¸ (.env)

| è®Šæ•¸ | èªªæ? | ?è¨­??|
|------|------|--------|
| MYSQL_ROOT_PASSWORD | MariaDB root å¯†ç¢¼ | - |
| MYSQL_DATABASE | è³‡æ?åº«å?ç¨?| phpipam |
| MYSQL_USER | è³‡æ?åº«ä½¿?¨è€?| phpipam |
| MYSQL_PASSWORD | è³‡æ?åº«å?ç¢?| - |
| TZ | ?‚å? | Asia/Taipei |

### DHCP ä¼ºæ??¨é?ç½?

ç·¨è¼¯ `health_dashboard/config/dhcp_servers.json`:

```json
[
    {"ip": "192.168.1.1", "hostname": "DHCP-01", "location": "ç¸½éƒ¨", "enabled": true},
    {"ip": "192.168.2.1", "hostname": "DHCP-02", "location": "?†éƒ¨", "enabled": true}
]
```

## ?”§ ç¶­è­·?½ä»¤

```bash
# ?¥ç??¥è?
docker-compose logs -f phpipam-cron

# ?‹å??·è?è³‡æ??¶é?
docker-compose exec phpipam-cron php /health_check/scripts/collect_stats.php

# ?Œæ­¥ DHCP ?ç½®
docker-compose exec phpipam-web cat /phpipam/health_dashboard/config/dhcp_servers.json > /tmp/dhcp.json
docker cp /tmp/dhcp.json $(docker-compose ps -q phpipam-cron):/health_check/config/dhcp_servers.json

# ?ªé™¤ DHCP æ­·å²è³‡æ?
docker-compose exec mariadb mysql -u phpipam -p$MYSQL_PASSWORD phpipam \
  -e "DELETE FROM health_check_dhcp_history WHERE dhcp_ip = 'è¦åˆª?¤ç?IP'"
```

## ?? License

MIT License

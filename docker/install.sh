#!/bin/bash
# phpIPAM Health Dashboard - ä¸€?µå?è£è…³??
set -e

echo "=========================================="
echo "phpIPAM Health Dashboard ä¸€?µéƒ¨ç½?
echo "=========================================="

# æª¢æŸ¥ Docker ?¯å¦å®‰è?
if ! command -v docker &> /dev/null; then
    echo "[ERROR] Docker ?ªå?è£ï?è«‹å?å®‰è? Docker"
    exit 1
fi

# æª¢æŸ¥ docker-compose ?¯å¦å®‰è?
if ! command -v docker-compose &> /dev/null; then
    echo "[ERROR] docker-compose ?ªå?è£ï?è«‹å?å®‰è? docker-compose"
    exit 1
fi

# ?‡æ??°è…³?¬ç›®??cd "$(dirname "$0")"

# æª¢æŸ¥ .env æª”æ?
if [ ! -f .env ]; then
    echo "[INFO] å»ºç? .env ?ç½®æª”æ?..."
    cp .env.example .env
    echo "[WARN] è«‹ç·¨è¼?.env æª”æ?è¨­å?å¯†ç¢¼å¾Œé??°åŸ·è¡Œæ­¤?³æœ¬"
    echo "       vi .env"
    exit 1
fi

# æª¢æŸ¥å¯†ç¢¼?¯å¦å·²è¨­å®?source .env
if [ "$MYSQL_ROOT_PASSWORD" = "your_root_password_here" ] || [ "$MYSQL_PASSWORD" = "your_phpipam_password_here" ]; then
    echo "[ERROR] è«‹å?ä¿®æ”¹ .env ä¸­ç?å¯†ç¢¼è¨­å?"
    exit 1
fi

echo "[1/4] ?Ÿå? Docker ?å?..."
docker-compose up -d

echo "[2/4] ç­‰å? MariaDB ?Ÿå? (30ç§?..."
sleep 30

echo "[3/4] æª¢æŸ¥?å??€??.."
docker-compose ps

echo "[4/4] ?å??–å¥åº·æª¢??Cron..."
docker-compose exec phpipam-cron sh -c 'echo "*/5 * * * * php /health_check/scripts/collect_stats.php >> /var/log/health_check.log 2>&1" >> /etc/crontabs/root'

echo ""
echo "=========================================="
echo "???¨ç½²å®Œæ?ï¼?
echo "=========================================="
echo ""
echo "?? phpIPAM:           http://$(hostname -I | awk '{print $1}')/"
echo "?? Health Dashboard:  http://$(hostname -I | awk '{print $1}')/health_dashboard/"
echo ""
echo "? ï?  é¦–æ¬¡ä½¿ç”¨è«‹è¨ª??phpIPAM å®Œæ??å??–è¨­å®?
echo ""

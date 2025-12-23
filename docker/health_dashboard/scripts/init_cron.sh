#!/bin/bash
# phpIPAM Health Dashboard - Cron ?å??–è…³??# æ­¤è…³?¬æ???Cron å®¹å™¨?Ÿå??‚è¢«?¼å«

# è¨­å??¥åº·æª¢æŸ¥ Cron Job (æ¯?5 ?†é??·è?ä¸€æ¬?
echo "*/5 * * * * php /health_check/scripts/collect_stats.php >> /var/log/health_check.log 2>&1" >> /etc/crontabs/root

# å»ºç??¥è?æª”æ?
touch /var/log/health_check.log
chmod 666 /var/log/health_check.log

echo "[Health Check] Cron job initialized"

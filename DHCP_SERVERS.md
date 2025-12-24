# DHCP Server List

This document lists all DHCP servers monitored by the Health Dashboard.

## Server List

| IP | Hostname | Location | Status |
|----------|----------|----------|--------|
| 172.16.5.196 | DHCP-CH-HQ2 | Changhua HQ2 | Enabled |
| 172.23.13.10 | DHCP-CH-PGT | Changhua Puyan | Enabled |
| 172.23.174.5 | DHCP-TC-HQ | Taichung HQ | Enabled |
| 172.23.199.150 | DHCP-TC-UAIC | Taichung | Enabled |
| 172.23.110.1 | DHCP-TP-XY | Taipei | Enabled |
| 172.23.94.254 | DHCP-TP-BaoYu | Taipei Baoyu | Enabled |
| 172.23.127.169 | DHCP-TC-CBD | Taichung CBD | Enabled |

## Configuration File

The DHCP server configuration is stored in `config/dhcp_servers.json`.

## Monitoring Settings

- **Interval**: Every 5 seconds
- **Timeout**: 2 seconds
- **Data Retention**: 7 days

## API Endpoints

```bash
# Get all DHCP servers
curl "https://ipam-tw.pouchen.com/health_dashboard/api/api_dhcp_config.php?action=list"

# Get DHCP history
curl "https://ipam-tw.pouchen.com/api/mcp/tools/daily_health_check/?dhcp_server_ip=172.16.5.196"
```

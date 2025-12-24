# phpIPAM 專案級別規範

## 📋 專案概述

本規範適用於所有 phpIPAM 相關專案開發，記錄生產環境系統架構供開發參考。

## 🏗️ 生產環境系統架構

### 伺服器資訊
- **Hostname**: `stwphpipam-p`
- **phpIPAM 版本**: v1.7.4

### 目錄結構

```
/opt/
├── phpipam/                    # phpIPAM Docker Compose
│   └── docker-compose.yml
├── Nginx/                      # Nginx 反向代理
│   ├── docker-compose.yml
│   ├── default.conf
│   └── ssl/
├── Socat/                      # MariaDB 代理
│   └── docker-compose.yml
├── health_check/               # Health Check 模組
│   ├── config/
│   │   └── dhcp_servers.json
│   └── scripts/
│       ├── collect_stats.php
│       └── dhcp_monitor_daemon.php
├── backup/                     # 備份目錄
├── containerd/                 # Container runtime
├── 2023-PCC-root-uca-crt-key/  # SSL 憑證
├── phpipam_backup.sh           # 備份腳本
├── sqlbackup.sh
└── run.sh
```

### Docker 容器

| Container | Image | Port | 說明 |
|-----------|-------|------|------|
| `phpipam_phpipam-web_1` | phpipam/phpipam-www:v1.7.4 | 80 (internal) | Web 介面 |
| `phpipam_phpipam-cron_1` | phpipam/phpipam-cron:v1.7.4 | - | 排程任務 + DHCP 監控 |
| `phpipam_phpipam-mariadb_1` | mariadb:latest | 3306 | 資料庫 |
| `nginx_nginx_1` | nginx:latest | 80, 443 | 反向代理 |

### 網路架構

```
                    ┌─────────────────┐
                    │   Nginx:443     │
                    │  (SSL Termination)│
                    └────────┬────────┘
                             │
              ┌──────────────┴──────────────┐
              │    my_custom_network        │
              │    192.168.255.0/24         │
              │                             │
    ┌─────────┴─────────┐     ┌─────────────┴─────────────┐
    │   phpipam-web     │     │      phpipam-cron         │
    │   (:80 internal)  │     │  (DHCP Monitor Daemon)    │
    └─────────┬─────────┘     └─────────────┬─────────────┘
              │                             │
              └──────────────┬──────────────┘
                             │
                   ┌─────────┴─────────┐
                   │  phpipam-mariadb  │
                   │    (:3306)        │
                   └───────────────────┘
```

### Volume Mounts

| Volume | Mount Path | 說明 |
|--------|------------|------|
| `phpipam-db-data` | /var/lib/mysql | 資料庫資料 |
| `phpipam-logo` | /phpipam/css/images/logo | Logo 檔案 |
| `phpipam-ca` | /usr/local/share/ca-certificates | CA 憑證 |
| `/opt/health_check` | /health_check | Health Check 模組 |

---

## 🔧 開發部署規範

### Health Check 模組部署
1. 檔案放置於 `/opt/health_check/`
2. 由 `phpipam-cron` 容器執行
3. Volume mount 路徑：`/opt/health_check:/health_check`

### DHCP Monitor Daemon
- 啟動方式：透過 cron 容器的 command 自動啟動
- Log 路徑：`/var/log/dhcp_monitor.log`
- 設定檔：`/health_check/config/dhcp_servers.json`

### 資料庫連線
- Host: `phpipam-mariadb` (容器內) 或 `localhost:3306` (主機)
- Database: `phpipam`
- 密碼環境變數：`IPAM_DATABASE_PASS`

---

## 📁 相關 GitHub 專案

| 專案 | Repository | 說明 |
|------|------------|------|
| phpIPAM Health Monitor | [DEV-phpipam-health-monitor](https://github.com/nox913106/DEV-phpipam-health-monitor) | Dashboard + DHCP 監控 |
| MAC Manager | [DEV-phpipam-mac-manager](https://github.com/nox913106/DEV-phpipam-mac-manager) | MAC 地址管理工具 |
| MCP phpIPAM | (待補充) | AI MCP 工具整合 |

---

*Last Updated: 2024-12-24*

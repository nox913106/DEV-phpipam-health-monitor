# phpIPAM Health Check Deployment Log

## Deployment Info

- **Deploy Date**: 2025-12-19
- **System Version**: Docker phpIPAM v1.7.4
- **Features**: 24h Historical Statistics (CPU/Memory/Disk/DHCP)

---

## Deployment Steps

### Step 1: Prerequisites

- [ ] Confirm phpIPAM Docker container is running
- [ ] Confirm MariaDB container is accessible
- [ ] Confirm network connectivity

**Start Time**: TBD  
**End Time**: TBD

---

### Step 2: Database Setup

- [ ] Access MariaDB container
- [ ] Run health_check_tables.sql

**Start Time**: TBD  
**End Time**: TBD

---

### Step 3: File Deployment

- [ ] Upload health_dashboard folder
- [ ] Upload api files
- [ ] Upload includes files
- [ ] Set permissions

**Start Time**: TBD  
**End Time**: TBD

---

### Step 4: Cron Setup

- [ ] Configure cron job for collect_stats.php (every 5 min)
- [ ] Configure DHCP monitor daemon (every 5 sec)

```bash
# Add to crontab
*/5 * * * * php /health_check/scripts/collect_stats.php >> /var/log/health_check.log 2>&1
```

**Start Time**: TBD  
**End Time**: TBD

---

### Step 5: Verification

- [ ] Access Dashboard URL
- [ ] Verify data collection
- [ ] Check API endpoints

**Dashboard URL**: https://ipam-tw.pouchen.com/health_dashboard/

---

## Change Log

| Date | Version | Changes |
|------|---------|---------|
| 2025-12-24 | v2.2.1 | DHCP monitoring 5min -> 5sec, latency bug fixed |
| 2025-12-23 | v2.1 | Dashboard embedded into phpIPAM |
| 2025-12-19 | v2.0 | Initial deployment |

<?php
/**
 * cron_sync_router.php
 * Background cron job script to sync PPPoE secret statuses from Mikrotik into MySQL customer_tbl.
 * 
 * Can be run via CLI or Cron schedule:
 * e.g. (Every 5 minutes in crontab or aaPanel Cron):
 * * /5 * * * * php /www/wwwroot/your-site/cron_sync_router.php >> /www/wwwroot/your-site/logs/router_sync.log 2>&1
 */

require_once __DIR__ . '/ajax/sync_mikrotik_status.php';


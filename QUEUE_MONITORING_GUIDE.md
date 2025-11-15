# Queue & Shipment Monitoring Guide

## Quick Status Check

Run this command to check queue and shipment status:

```bash
php artisan queue:status
```

For detailed information:

```bash
php artisan queue:status --detailed
```

## 1. Check if Queue Worker is Running

### Windows (PowerShell)
```powershell
# Check if queue worker process is running
Get-Process | Where-Object {$_.ProcessName -like "*php*" -and $_.CommandLine -like "*queue:work*"}

# Or check in Task Manager for "php artisan queue:work"
```

### Linux/Mac
```bash
# Check if queue worker is running
ps aux | grep "queue:work"

# Or use pgrep
pgrep -f "queue:work"
```

## 2. Start Queue Worker

### For Development (Run in Terminal)
```bash
php artisan queue:work
```

### For Production (Run in Background)
```bash
# Windows (PowerShell - run in background)
Start-Process php -ArgumentList "artisan queue:work" -WindowStyle Hidden

# Linux/Mac (using nohup)
nohup php artisan queue:work > storage/logs/queue.log 2>&1 &

# Or use screen/tmux
screen -S queue
php artisan queue:work
# Press Ctrl+A then D to detach
```

### Recommended: Use Supervisor (Linux)

Create `/etc/supervisor/conf.d/ecofreight-queue.conf`:

```ini
[program:ecofreight-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/queue.log
stopwaitsecs=3600
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start ecofreight-queue:*
```

## 3. Check Queue Connection

Check your `.env` file:

```env
QUEUE_CONNECTION=database  # or 'sync', 'redis', 'sqs', etc.
```

- **`sync`**: Jobs run immediately (no queue worker needed, but not recommended for production)
- **`database`**: Jobs stored in database (requires `php artisan queue:work`)
- **`redis`**: Jobs stored in Redis (requires Redis server and queue worker)

## 4. Monitor Shipment Status

### Via Command Line
```bash
# Check queue status
php artisan queue:status

# Check recent shipments
php artisan tinker
>>> \App\Models\Shipment::where('status', 'pending')->count()
>>> \App\Models\Shipment::where('status', 'created')->count()
>>> \App\Models\Shipment::where('status', 'error')->count()
```

### Via Database
```sql
-- Check pending shipments
SELECT COUNT(*) FROM shipments WHERE status = 'pending' AND ecofreight_awb IS NULL;

-- Check created shipments
SELECT COUNT(*) FROM shipments WHERE status = 'created' AND ecofreight_awb IS NOT NULL;

-- Check recent shipments
SELECT id, shopify_order_name, status, ecofreight_awb, created_at, updated_at 
FROM shipments 
ORDER BY created_at DESC 
LIMIT 10;
```

### Via Dashboard
Visit: `https://your-app-url.com/dashboard/orders`

## 5. Check Logs

### View Laravel Logs
```bash
# Windows (PowerShell)
Get-Content storage/logs/laravel.log -Tail 50 -Wait

# Linux/Mac
tail -f storage/logs/laravel.log
```

### Search for Shipment Creation Logs
```bash
# Windows (PowerShell)
Select-String -Path storage/logs/laravel.log -Pattern "CreateShipmentJob"

# Linux/Mac
grep "CreateShipmentJob" storage/logs/laravel.log
```

### Key Log Messages to Look For
- `Creating shipment in EcoFreight` - Job started
- `Shipment created in EcoFreight` - Success
- `EcoFreight shipment creation failed` - Failure
- `CreateShipmentJob completed` - Job finished

## 6. Check Failed Jobs

```bash
# List failed jobs
php artisan queue:failed

# Retry a specific failed job
php artisan queue:retry {job-id}

# Retry all failed jobs
php artisan queue:retry all

# Delete a failed job
php artisan queue:forget {job-id}

# Flush all failed jobs
php artisan queue:flush
```

## 7. Test Queue Manually

### Test if Queue is Working
```bash
php artisan tinker
```

Then:
```php
// Dispatch a test job
\App\Jobs\CreateShipmentJob::dispatch(1, 1, 'test_' . time());

// Check if job was queued (if using database queue)
\DB::table('jobs')->count();
```

### Process Queue Manually (One Job)
```bash
php artisan queue:work --once
```

## 8. Common Issues & Solutions

### Issue: Jobs Not Processing
**Solution:**
1. Check if queue worker is running: `ps aux | grep queue:work`
2. Check queue connection: `php artisan queue:status`
3. Check for errors: `tail -f storage/logs/laravel.log`
4. Restart queue worker

### Issue: Shipments Stuck in "Pending"
**Solution:**
1. Check if queue worker is running
2. Check logs for errors
3. Manually retry: Click "Retry" button in dashboard or run:
   ```bash
   php artisan tinker
   >>> $shipment = \App\Models\Shipment::find(1);
   >>> \App\Jobs\CreateShipmentJob::dispatch($shipment->shop_id, $shipment->id);
   ```

### Issue: "Queue connection not found"
**Solution:**
1. Check `.env` file has `QUEUE_CONNECTION` set
2. If using database queue, run: `php artisan queue:table` and `php artisan migrate`
3. If using Redis, ensure Redis server is running

### Issue: Jobs Failing Immediately
**Solution:**
1. Check logs: `tail -f storage/logs/laravel.log`
2. Check failed jobs: `php artisan queue:failed`
3. Verify EcoFreight credentials are set
4. Verify production token is set: `php artisan ecofreight:set-production-token "YOUR_TOKEN"`

## 9. Monitoring Commands Summary

```bash
# Quick status check
php artisan queue:status

# Detailed status
php artisan queue:status --detailed

# Start queue worker
php artisan queue:work

# Process one job
php artisan queue:work --once

# View failed jobs
php artisan queue:failed

# Retry all failed jobs
php artisan queue:retry all

# View logs
tail -f storage/logs/laravel.log
```

## 10. Production Recommendations

1. **Use Supervisor** to keep queue worker running
2. **Monitor logs** regularly
3. **Set up alerts** for failed jobs
4. **Use database queue** for reliability (or Redis for performance)
5. **Monitor shipment status** via dashboard
6. **Set up cron job** to check queue status periodically

## 11. Automated Monitoring Script

Create a simple monitoring script `check-queue.sh`:

```bash
#!/bin/bash
QUEUE_SIZE=$(php artisan queue:status | grep "Queue Size" | awk '{print $3}')
PENDING=$(php artisan queue:status | grep "Pending Shipments" | awk '{print $4}')

if [ "$QUEUE_SIZE" -gt 100 ] || [ "$PENDING" -gt 10 ]; then
    echo "ALERT: Queue issues detected!"
    # Send email or notification
fi
```

Run via cron:
```bash
*/5 * * * * /path/to/check-queue.sh
```


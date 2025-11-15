# Troubleshooting: Shipments Not Being Sent to EcoFreight

## Quick Diagnosis

Run these commands on your server to diagnose the issue:

```bash
# 1. Check queue status
php artisan queue:status --detailed

# 2. Check logs for errors
tail -100 storage/logs/laravel.log | grep -i "CreateShipmentJob\|error\|failed"

# 3. Manually process pending shipments
php artisan shipments:process --all

# 4. Check if production token is set
php artisan tinker
>>> \App\Models\ShopSetting::first()->ecofreight_bearer_token ? 'Token set' : 'No token'
```

## Common Issues & Solutions

### Issue 1: Jobs Not Running (Queue is "sync" but shipments still pending)

**Symptoms:**
- Queue connection is "sync"
- Shipments stuck in "pending" status
- No errors in logs

**Possible Causes:**
1. Jobs not being dispatched when shipments are created
2. Jobs failing silently
3. Missing shop settings

**Solution:**
```bash
# Manually trigger job for a shipment
php artisan shipments:process --id=2

# Or process all pending
php artisan shipments:process --all

# Check logs immediately after
tail -f storage/logs/laravel.log
```

### Issue 2: "Shop settings not configured"

**Symptoms:**
- Error message: "Shop settings not configured"
- Shipment status changes to "error"

**Solution:**
1. Go to Settings page
2. Configure EcoFreight credentials
3. Fill in "Ship-From Information"
4. Click "Save Settings"
5. Retry the shipment

### Issue 3: "Origin settings invalid"

**Symptoms:**
- Error message: "Origin settings invalid: [list of missing fields]"
- Shipment status changes to "error"

**Solution:**
1. Go to Settings page
2. Fill in ALL required "Ship-From Information" fields:
   - Company Name
   - Contact Name
   - Phone
   - Email
   - Address
   - City
3. Click "Save Settings"
4. Retry the shipment

### Issue 4: "Bearer token not available"

**Symptoms:**
- Error message: "Bearer token not available"
- Shipment status changes to "error"

**Solution:**
```bash
# Set production token
php artisan ecofreight:set-production-token "YOUR_TOKEN_HERE"

# Or test connection to get token
# Go to Settings page and click "Test Connection"
```

### Issue 5: Jobs Failing Silently

**Symptoms:**
- Shipments stay in "pending" but no errors shown
- No log entries

**Solution:**
1. Check if jobs are being dispatched:
   ```bash
   # Add logging to see if job is called
   tail -f storage/logs/laravel.log
   # Then manually trigger: php artisan shipments:process --id=2
   ```

2. Check job execution:
   - Look for "CreateShipmentJob started" in logs
   - Look for "Creating shipment in EcoFreight" in logs
   - Look for any error messages

### Issue 6: API Connection Issues

**Symptoms:**
- Error: "Connection failed" or "API endpoint not found"
- Error: "Authentication failed"

**Solution:**
1. Verify production token is set correctly
2. Check base URL in settings (should be: `https://app.ecofreight.ae`)
3. Test connection from Settings page
4. Check network connectivity to EcoFreight API

## Step-by-Step Debugging

### Step 1: Check Current Status
```bash
php artisan queue:status --detailed
```

### Step 2: Check Logs
```bash
# View recent logs
tail -100 storage/logs/laravel.log

# Watch logs in real-time
tail -f storage/logs/laravel.log

# Search for specific errors
grep -i "error\|failed\|exception" storage/logs/laravel.log | tail -20
```

### Step 3: Check Shipment Details
```bash
php artisan tinker
```

Then:
```php
// Check a specific shipment
$shipment = \App\Models\Shipment::find(2);
echo "Status: " . $shipment->status . "\n";
echo "AWB: " . ($shipment->ecofreight_awb ?? 'N/A') . "\n";
echo "Error: " . ($shipment->error_message ?? 'None') . "\n";
echo "Shop ID: " . $shipment->shop_id . "\n";

// Check shop settings
$shop = $shipment->shop;
echo "Shop: " . $shop->name . "\n";
echo "Settings exist: " . ($shop->settings ? 'Yes' : 'No') . "\n";

if ($shop->settings) {
    echo "Base URL: " . $shop->settings->ecofreight_base_url . "\n";
    echo "Token set: " . ($shop->settings->ecofreight_bearer_token ? 'Yes' : 'No') . "\n";
}
```

### Step 4: Manually Process Shipment
```bash
# Process specific shipment
php artisan shipments:process --id=2

# Process all pending
php artisan shipments:process --all
```

### Step 5: Check Job Execution
After running `shipments:process`, immediately check logs:
```bash
tail -50 storage/logs/laravel.log
```

Look for:
- `CreateShipmentJob started` - Job began
- `Creating shipment in EcoFreight` - API call started
- `Shipment created in EcoFreight` - Success
- Any error messages

## Manual Job Trigger (For Testing)

If you want to manually trigger a job for testing:

```bash
php artisan tinker
```

Then:
```php
$shipment = \App\Models\Shipment::find(2);
\App\Jobs\CreateShipmentJob::dispatch($shipment->shop_id, $shipment->id, 'manual_test');
```

## Check Database Directly

```sql
-- Check pending shipments
SELECT id, shopify_order_name, status, ecofreight_awb, error_message, created_at 
FROM shipments 
WHERE status = 'pending' 
ORDER BY created_at DESC;

-- Check shop settings
SELECT s.id, s.name, ss.ecofreight_base_url, 
       CASE WHEN ss.ecofreight_bearer_token IS NOT NULL THEN 'Yes' ELSE 'No' END as has_token
FROM shops s
LEFT JOIN shop_settings ss ON s.id = ss.shop_id;
```

## Production Token Setup

If production token is not set:

```bash
php artisan ecofreight:set-production-token "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
```

## Enable Verbose Logging

To see more detailed logs, check your `.env`:

```env
LOG_LEVEL=debug
APP_DEBUG=true
```

Then restart your application.

## Still Not Working?

1. **Check all logs:**
   ```bash
   cat storage/logs/laravel.log | grep -i "CreateShipmentJob" | tail -50
   ```

2. **Verify EcoFreight API is accessible:**
   ```bash
   curl -X POST https://app.ecofreight.ae/v2/api/client/order \
     -H "Content-Type: application/json" \
     -H "Authorization: YOUR_TOKEN" \
     -d '[{"order_reference":"TEST"}]'
   ```

3. **Check PHP errors:**
   ```bash
   tail -f storage/logs/laravel.log | grep -i "php\|fatal\|warning"
   ```

4. **Verify database connection:**
   ```bash
   php artisan tinker
   >>> \DB::connection()->getPdo();
   ```

## Quick Fix Commands

```bash
# 1. Set production token
php artisan ecofreight:set-production-token "YOUR_TOKEN"

# 2. Process all pending shipments
php artisan shipments:process --all

# 3. Check status
php artisan queue:status --detailed

# 4. View logs
tail -100 storage/logs/laravel.log
```


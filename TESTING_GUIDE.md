# EcoFreight Shopify App - Testing Guide

This guide provides step-by-step instructions for testing the EcoFreight Shopify app according to the test cases outlined in the requirements.

## Pre-Test Setup

### 1. Shopify Manual Shipping Rates Setup

1. **Go to Shopify Admin** → Settings → Shipping and delivery
2. **Click "Manage rates"** for your shipping profile
3. **Add UAE zone** if not already present
4. **Add two manual rates** under UAE zone:
   - **EcoFreight Standard**: AED 15 (or table by weight)
   - **EcoFreight Express**: AED 25
5. **Optional**: Add free shipping threshold for Standard (e.g., AED 200)

### 2. Test Products Setup

Create the following test products:

#### SKU-LIGHT (0.5 kg)
- **Product Name**: Test Product - Light
- **SKU**: SKU-LIGHT
- **Weight**: 0.5 kg
- **Price**: AED 50
- **Metafields** (optional): Leave empty to test default package rules

#### SKU-HEAVY (12 kg)
- **Product Name**: Test Product - Heavy
- **SKU**: SKU-HEAVY
- **Weight**: 12 kg
- **Price**: AED 100
- **Metafields** (optional): Add dimensions (30x20x10 cm) to test product dimensions

### 3. App Settings Configuration

1. **Install the app** on your development store
2. **Configure EcoFreight connection**:
   - Base URL: `https://app.ecofreight.ae/en`
   - Username: `apitesting`
   - Password: `apitesting`
   - Click "Test Connection" → Should show "Connected"

3. **Configure Ship-from (Origin) settings**:
   - Company: Your company name
   - Contact: Your name
   - Phone: +971501234567
   - Email: your-email@example.com
   - Address: Your address in UAE
   - City/Emirate: Dubai
   - Postcode: 00000
   - Country: UAE

4. **Configure Default Package Rules**:
   - Default Weight: 1.0 kg
   - Default Length: 30 cm
   - Default Width: 20 cm
   - Default Height: 10 cm
   - Packing Rule: "One parcel per order"

5. **Configure Services**:
   - ✅ Use EcoFreight Standard service
   - ✅ Use EcoFreight Express service

6. **Configure COD** (optional):
   - Enable/Disable based on your preference
   - COD Fee: 0 AED (or your preferred amount)

7. **Configure Tracking**:
   - Auto-poll tracking: ✅ ON
   - Poll interval: 2 hours
   - Stop after: 10 days

8. **Configure Alerts**:
   - Error alert emails: your-email@example.com
   - Include AWB in alerts: ✅ ON

## Test Cases Execution

### Test Case 1: Checkout Rate Display (Standard)

**Steps:**
1. Add SKU-LIGHT to cart
2. Go to checkout with Valid Address #1 (Dubai)
3. Choose "EcoFreight Standard" shipping rate
4. Complete payment

**Expected Results:**
- ✅ Only manual rates appear (Standard & Express)
- ✅ Selected rate stored on order as "EcoFreight Standard"
- ✅ No dependency on app for rate display

**Test Address #1 (Dubai):**
```
Name: Test Buyer
Phone: +971501234567
Address1: Sheikh Zayed Rd, Trade Center
City/Emirate: Dubai
Postcode: 00000
Country: UAE
```

### Test Case 2: Checkout Rate Display (Express)

**Steps:**
1. Add SKU-LIGHT to cart
2. Go to checkout with Valid Address #1
3. Choose "EcoFreight Express" shipping rate
4. Complete payment

**Expected Results:**
- ✅ Rate title saved as "EcoFreight Express"

### Test Case 3: Auto Shipment + Label After Payment (Standard)

**Steps:**
1. Use order from Test Case #1
2. Wait for webhook processing (or manually trigger)

**Expected Results:**
- ✅ App receives order paid webhook
- ✅ Creates EcoFreight shipment
- ✅ AWB/tracking number saved
- ✅ Label file fetched and attached to Shopify order
- ✅ Fulfillment created with tracking company = "EcoFreight"
- ✅ Tracking number and URL set

**Verification:**
- Check app shipments page for new shipment
- Check Shopify order timeline for label attachment
- Check fulfillment section for tracking details

### Test Case 4: Auto Shipment + Label (Express)

**Steps:**
1. Use order from Test Case #2
2. Wait for webhook processing

**Expected Results:**
- ✅ Service mapping respects "Express"
- ✅ Same results as Test Case #3

### Test Case 5: Default Package Rules Applied

**Steps:**
1. Create order with SKU-LIGHT (no dimensions metafields)
2. Ensure app settings have default dimensions

**Expected Results:**
- ✅ Shipment created using default package rules
- ✅ Log indicates defaults were used

### Test Case 6: Product Dimensions Honored

**Steps:**
1. Create order with product that has dimension metafields
2. Check shipment creation

**Expected Results:**
- ✅ Shipment uses product dimensions (not defaults)
- ✅ Log notes "product dimensions used"

### Test Case 7: Multi-item Packing Rule

**Steps:**
1. Add SKU-LIGHT + SKU-HEAVY to cart
2. Test with "per order" packing rule
3. Test with "per line item" packing rule

**Expected Results:**
- ✅ Per order → 1 parcel
- ✅ Per line item → 2 parcels
- ✅ Shipment reflects correct parcel count

### Test Case 8: COD Flow (if enabled)

**Steps:**
1. Create paid order with COD enabled
2. Check shipment creation

**Expected Results:**
- ✅ Shipment includes COD amount
- ✅ COD appears in EcoFreight response
- ✅ Shopify fulfillment shows tracking normally

### Test Case 9: Tracking Sync (Polling)

**Steps:**
1. For any shipped order, click "Sync now" manually
2. Wait for scheduled poll (2 hours)

**Expected Results:**
- ✅ Fulfillment timeline shows updates
- ✅ Status transitions: Shipped → In transit → Delivered
- ✅ Polling stops after Delivered or TTL

### Test Case 10: Error - Missing Phone

**Steps:**
1. Place order with no phone in shipping address
2. Check error handling

**Expected Results:**
- ✅ Shipment not created
- ✅ Error logged with clear message
- ✅ Alert email sent with retry action
- ✅ After adding phone, retry succeeds

### Test Case 11: Error - Invalid Address/City

**Steps:**
1. Use invalid address with gibberish city
2. Check error handling

**Expected Results:**
- ✅ EcoFreight validation error surfaced
- ✅ Human-readable error message
- ✅ Merchant can edit address and retry
- ✅ Success on retry generates label + tracking

**Invalid Test Address:**
```
Name: Test Buyer
Phone: +971501234567
Address1: Invalid Street
City/Emirate: Xyzville
Postcode: 00000
Country: UAE
```

### Test Case 12: Network Timeout / EcoFreight Down

**Steps:**
1. Simulate timeout by disconnecting network
2. Check retry mechanism

**Expected Results:**
- ✅ Job retried with backoff (3 attempts)
- ✅ On final failure: alert email + error badge
- ✅ Manual retry later succeeds

### Test Case 13: Label Not Immediately Available

**Steps:**
1. Simulate delayed label (create shipment success, label fetch fails)
2. Check retry mechanism

**Expected Results:**
- ✅ Order shows "Shipment created; label pending"
- ✅ LabelFetchJob retries and attaches label when available
- ✅ Alert email only on repeated failure

### Test Case 14: Void/Cancel Shipment

**Steps:**
1. Void an EcoFreight shipment before pickup
2. Check cancellation handling

**Expected Results:**
- ✅ EcoFreight returns "canceled/void"
- ✅ Shopify fulfillment updated accordingly
- ✅ No duplicate active fulfillments

## Manual Testing Commands

Use the following Artisan commands to test specific scenarios:

```bash
# Test connection
php artisan ecofreight:test your-shop.myshopify.com connection

# Test shipment creation
php artisan ecofreight:test your-shop.myshopify.com create-shipment --order-id=12345

# Test shipment creation with error simulation
php artisan ecofreight:test your-shop.myshopify.com create-shipment --order-id=12345 --simulate-error

# Test tracking
php artisan ecofreight:test your-shop.myshopify.com track --awb=ABC123

# Test cancellation
php artisan ecofreight:test your-shop.myshopify.com cancel --awb=ABC123
```

## Test Data Matrix

| Case | SKU(s) | Rate Chosen | Address | COD | Dims Source | Expected |
|------|--------|-------------|---------|-----|-------------|----------|
| 1 | LIGHT | Standard | Valid #1 | Off | Defaults | Label + tracking |
| 2 | LIGHT | Express | Valid #1 | Off | Defaults | Label + tracking |
| 3 | HEAVY | Standard | Valid #2 | Off | Defaults | Multi-kg ok |
| 4 | LIGHT+HEAVY | Standard | Valid #1 | Off | Per order parcel | 1 parcel |
| 5 | LIGHT+HEAVY | Standard | Valid #1 | Off | Per line-item parcel | 2 parcels |
| 6 | LIGHT(w/dims) | Standard | Valid #2 | Off | Product dims | Product dims used |
| 7 | LIGHT | Standard | Valid #1 | On | Defaults | COD set |
| 8 | LIGHT | Standard | Invalid | Off | Defaults | Error → fix → retry |
| 9 | LIGHT | Standard | Valid #1 | Off | Simulate timeout | Retries + alert |
| 10 | LIGHT | Standard | Missing phone | Off | Defaults | Error → add phone → retry |
| 11 | LIGHT | Standard | Valid #1 | Off | Delayed label | Label later + note |

## Evidence Collection

Capture screenshots of:
1. ✅ Checkout showing both manual rates
2. ✅ Order timeline with label attachment link
3. ✅ Fulfillment section showing tracking number + URL
4. ✅ App logs for successful and failed cases
5. ✅ Alert email samples

## Test Log Template

Keep a test log with the following information:

```
Date: [Date]
Time: [Time]
Order #: [Order Number]
Case ID: [Test Case Number]
Result: [Pass/Fail]
Notes: [Any observations or issues]
```

## Rollback / Cleanup

1. **Disable manual rates** temporarily if needed
2. **Void test shipments** in EcoFreight (if supported)
3. **Mark test orders** with tag "TEST-ECOFREIGHT" in Shopify
4. **Remove sandbox labels/files** from storage after sign-off

## Troubleshooting

### Common Issues:

1. **Webhook not firing**: Check webhook registration in Shopify admin
2. **Connection test fails**: Verify EcoFreight sandbox credentials
3. **Shipment creation fails**: Check ship-from address configuration
4. **Label not generated**: Check EcoFreight API response and retry mechanism
5. **Tracking not syncing**: Verify polling settings and EcoFreight tracking API

### Debug Commands:

```bash
# Check queue worker
php artisan queue:work --verbose

# Check logs
tail -f storage/logs/laravel.log

# Test specific webhook
curl -X POST https://your-app-url.com/webhooks/orders/paid \
  -H "Content-Type: application/json" \
  -d '{"test": "data"}'
```

## Pass/Fail Criteria

- ✅ Checkout reliably shows manual rates (Standard/Express) with configured prices
- ✅ 100% of paid orders (with valid data) create shipments, fetch labels, and create fulfillments with tracking
- ✅ All defined errors are detected, clearly communicated, and recoverable via retry
- ✅ Tracking transitions appear within polling window; manual "Sync now" works
- ✅ PII is redacted in logs; secrets are not displayed

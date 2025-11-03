# 🛍️ Shopify Partner Dashboard Setup Guide

## Configure Your EcoFreight App in Shopify Partner Dashboard

### **Step 1: Access Shopify Partner Dashboard**
1. Go to [https://partners.shopify.com/](https://partners.shopify.com/)
2. Log in with your Shopify Partner account
3. Navigate to **Apps** section

### **Step 2: Create New App or Edit Existing App**
1. Click **Create app** or edit your existing app
2. Choose **Custom app** (not public app for now)

### **Step 3: Configure App Settings**

#### **App Information:**
- **App name**: `EcoFreight Shipping Integration`
- **App URL**: `https://your-domain.com` (replace with your server URL)
- **Allowed redirection URL(s)**: `https://your-domain.com/auth/callback`

#### **App Credentials:**
Use the credentials from your `.env` file:
```
API Key: 7793b6863d3303fe7f295b8f19c6b4c4
API Secret: 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
Admin Token: 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
```

### **Step 4: Configure Webhooks**

Add these webhook endpoints to your app:

#### **Required Webhooks:**
1. **Order Paid**
   - **URL**: `https://your-domain.com/webhooks/orders/paid`
   - **Format**: JSON
   - **API Version**: 2024-01

2. **Order Updated**
   - **URL**: `https://your-domain.com/webhooks/orders/updated`
   - **Format**: JSON
   - **API Version**: 2024-01

3. **Fulfillment Update**
   - **URL**: `https://your-domain.com/webhooks/fulfillments/update`
   - **Format**: JSON
   - **API Version**: 2024-01

### **Step 5: App Permissions**

Ensure your app has these permissions:
- ✅ **read_orders**
- ✅ **write_orders**
- ✅ **read_fulfillments**
- ✅ **write_fulfillments**
- ✅ **read_products**
- ✅ **write_products**
- ✅ **read_shipping**
- ✅ **write_shipping**

### **Step 6: Test Installation**

1. **Generate Install URL**:
   ```
   https://your-domain.com/auth?shop=your-shop.myshopify.com
   ```

2. **Install on Test Store**:
   - Use a development store for testing
   - Complete the OAuth flow
   - Verify webhook registration

### **Step 7: Configure EcoFreight Settings**

Once installed, configure your EcoFreight settings:

1. **Go to your app dashboard**
2. **Navigate to Settings**
3. **Configure EcoFreight credentials**:
   ```
   Base URL: https://app.ecofreight.ae/en
   Username: apitesting
   Password: apitesting
   ```

4. **Set up shipping origin**:
   - Company details
   - Contact information
   - Address and location

5. **Configure shipping rules**:
   - Default package dimensions
   - Packing rules
   - Service types (Standard/Express)

### **Step 8: Test Complete Workflow**

1. **Create test order** in your Shopify store
2. **Verify manual shipping rates** appear at checkout
3. **Complete order payment**
4. **Check automatic shipment creation** in EcoFreight
5. **Verify fulfillment creation** in Shopify
6. **Test tracking synchronization**

### **Production Deployment Checklist:**

- [ ] **SSL Certificate** installed and working
- [ ] **Domain configured** and pointing to your server
- [ ] **Environment variables** set correctly
- [ ] **Database credentials** updated for production
- [ ] **Queue worker** running as service
- [ ] **Webhook URLs** updated to production domain
- [ ] **Error monitoring** configured
- [ ] **Backup strategy** implemented

### **Troubleshooting:**

#### **Common Issues:**
1. **Webhook verification fails** - Check HMAC signature validation
2. **OAuth redirect fails** - Verify redirect URL matches exactly
3. **API calls fail** - Check credentials and permissions
4. **Database errors** - Verify connection and migrations

#### **Debug Commands:**
```bash
# Test app functionality
php test-credentials.php

# Check database connection
php test-db-simple.php

# View logs
tail -f storage/logs/laravel.log

# Test queue processing
php artisan queue:work --verbose
```

### **Support Resources:**

- **Laravel Documentation**: [https://laravel.com/docs](https://laravel.com/docs)
- **Shopify API Documentation**: [https://shopify.dev/api](https://shopify.dev/api)
- **EcoFreight API Documentation**: [https://ecofreight.docs.apiary.io/](https://ecofreight.docs.apiary.io/)

---

**🎉 Your EcoFreight Shopify app is ready for production deployment!**

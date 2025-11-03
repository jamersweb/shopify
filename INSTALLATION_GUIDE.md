# EcoFreight Shopify App - Installation Guide

## Your Shopify Credentials

✅ **API Key**: `7793b6863d3303fe7f295b8f19c6b4c4`
✅ **API Secret**: `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`
✅ **Admin API Token**: `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`

## Quick Setup

### 1. Environment Configuration

Run the setup script to configure your environment:

```bash
chmod +x setup.sh
./setup.sh
```

This will:
- Copy environment template
- Generate application key
- Set up your Shopify credentials
- Generate secure session and encryption keys

### 2. Database Setup

Update your `.env` file with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecofreight_shopify
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 3. Install Dependencies

```bash
composer install
npm install
```

### 4. Run Migrations

```bash
php artisan migrate
```

### 5. Start Queue Worker

```bash
php artisan queue:work
```

### 6. Test Setup

```bash
php test-setup.php
```

## Shopify App Configuration

### 1. Update App URL

In your `.env` file, set your actual app URL:

```env
SHOPIFY_APP_URL=https://your-actual-app-url.com
```

### 2. Shopify Partner Dashboard

1. Go to [Shopify Partners](https://partners.shopify.com)
2. Find your app
3. Update the following settings:
   - **App URL**: `https://your-actual-app-url.com`
   - **Allowed redirection URL**: `https://your-actual-app-url.com/auth/callback`

### 3. App Installation

Install the app on your development store:

```
https://your-shop.myshopify.com/admin/oauth/authorize?client_id=7793b6863d3303fe7f295b8f19c6b4c4&scope=write_fulfillments,read_fulfillments,write_orders,read_orders,read_products,write_shipping&redirect_uri=https://your-actual-app-url.com/auth/callback&state=csrf_token
```

Replace `your-shop.myshopify.com` with your actual shop domain.

## Testing the Installation

### 1. Test Connection

After installation, go to the app settings and click "Test Connection" to verify EcoFreight API connectivity.

### 2. Configure Settings

Set up your EcoFreight connection:
- **Base URL**: `https://app.ecofreight.ae/en`
- **Username**: `apitesting`
- **Password**: `apitesting`

### 3. Test Shipment Creation

Use the test command to verify shipment creation:

```bash
php artisan ecofreight:test your-shop.myshopify.com connection
php artisan ecofreight:test your-shop.myshopify.com create-shipment --order-id=12345
```

## Development with ngrok

For local development, use ngrok to expose your local server:

```bash
# Install ngrok
npm install -g ngrok

# Start Laravel server
php artisan serve

# In another terminal, expose with ngrok
ngrok http 8000
```

Then update your app URL in `.env` and Shopify Partner dashboard with the ngrok URL.

## Troubleshooting

### Common Issues:

1. **App installation fails**: Check that your app URL is accessible and SSL-enabled
2. **Connection test fails**: Verify EcoFreight credentials are correct
3. **Database errors**: Ensure database credentials are correct and database exists
4. **Queue not processing**: Make sure queue worker is running

### Debug Commands:

```bash
# Check logs
tail -f storage/logs/laravel.log

# Test specific functionality
php artisan ecofreight:test your-shop.myshopify.com connection --simulate-error

# Check queue status
php artisan queue:work --verbose
```

## Security Notes

- ✅ Your API secret is properly configured
- ✅ Session secret is auto-generated
- ✅ Encryption key is auto-generated
- ✅ Sensitive data will be encrypted in the database

## Next Steps

1. Complete the installation
2. Configure app settings
3. Set up manual shipping rates in Shopify
4. Create test products
5. Run through the test cases in TESTING_GUIDE.md

Your app is now ready for testing with the EcoFreight sandbox environment!

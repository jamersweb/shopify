# EcoFreight Shopify App

A Laravel-based Shopify app for integrating with EcoFreight shipping services. This app automatically creates shipments, generates labels, and tracks packages when orders are paid.

## Features

- **Automatic Shipment Creation**: Creates EcoFreight shipments when orders are paid
- **Label Generation**: Downloads and attaches shipping labels to Shopify orders
- **Tracking Integration**: Syncs tracking status between EcoFreight and Shopify
- **Settings Management**: Configure EcoFreight connection, ship-from address, and package rules
- **Error Handling**: Email notifications for failed shipments with retry capabilities
- **Embedded App**: Runs seamlessly within the Shopify admin interface

## Requirements

- PHP 8.1 or higher
- Laravel 10.x
- MySQL 5.7 or higher
- Composer
- Node.js (for asset compilation)
- SSL certificate (required for Shopify apps)

## Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd ecofreight-shopify-app
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp env.example .env
   php artisan key:generate
   ```

5. **Configure environment variables**
   Edit `.env` file with your settings:
   ```env
   # Database
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ecofreight_shopify
   DB_USERNAME=your_username
   DB_PASSWORD=your_password

   # Shopify App Configuration
   SHOPIFY_API_KEY=your_shopify_api_key
   SHOPIFY_API_SECRET=your_shopify_api_secret
   SHOPIFY_SCOPES=write_fulfillments,read_fulfillments,write_orders,read_orders,read_products,write_shipping
   SHOPIFY_APP_URL=https://your-app-url.com
   SESSION_SECRET=your_session_secret

   # EcoFreight Configuration
   ECOFREIGHT_BASE_URL=https://app.ecofreight.ae/en
   ECOFREIGHT_SANDBOX_USERNAME=apitesting
   ECOFREIGHT_SANDBOX_PASSWORD=apitesting

   # Encryption Key for sensitive data
   ENCRYPTION_KEY=your_encryption_key_32_chars
   ```

6. **Run database migrations**
   ```bash
   php artisan migrate
   ```

7. **Set up queue worker** (for background jobs)
   ```bash
   php artisan queue:work
   ```

## Shopify App Setup

1. **Create a Shopify Partner account** at https://partners.shopify.com

2. **Create a new app** in your Partner dashboard:
   - App type: Custom app
   - App URL: `https://your-app-url.com`
   - Allowed redirection URL: `https://your-app-url.com/auth/callback`

3. **Configure app settings**:
   - App name: EcoFreight Shipping
   - App URL: Your app's public URL
   - Allowed redirection URL: Your app URL + `/auth/callback`

4. **Get your credentials**:
   - API key
   - API secret key
   - Add these to your `.env` file

## EcoFreight API Setup

1. **Get EcoFreight API credentials**:
   - Sandbox credentials are provided: `apitesting` / `apitesting`
   - Production credentials from EcoFreight

2. **Configure in app settings**:
   - Base URL: `https://app.ecofreight.ae/en`
   - Username and password for API access

## Usage

### Initial Setup

1. **Install the app** on your Shopify store
2. **Configure settings**:
   - EcoFreight connection credentials
   - Ship-from address (origin)
   - Default package rules
   - Services to use (Standard/Express)
   - COD settings (if applicable)
   - Tracking preferences
   - Error notification emails

3. **Test connection** to verify EcoFreight API access

### Automatic Workflow

1. **Order placed and paid** → Webhook triggers
2. **Shipment created** in EcoFreight automatically
3. **Label generated** and attached to Shopify order
4. **Fulfillment created** in Shopify with tracking
5. **Tracking updates** synced automatically

### Manual Operations

- **View shipments**: See all shipments and their status
- **Retry failed shipments**: Manually retry failed operations
- **Regenerate labels**: Download new labels if needed
- **Sync tracking**: Force tracking status updates

## Configuration Options

### Ship-from Settings
- Company name and contact person
- Phone, email, and full address
- City/Emirate and postcode
- Country (UAE)

### Package Rules
- Default weight and dimensions
- Packing rule: per order or per item
- Fallback values when product data is missing

### Services
- Enable/disable Standard service
- Enable/disable Express service
- Service mapping from Shopify shipping rates

### COD Settings
- Enable/disable Cash on Delivery
- COD fee amount
- Automatic COD amount calculation

### Tracking
- Auto-polling interval (1-24 hours)
- Stop polling after delivery or time limit
- Custom tracking URL template

### Alerts
- Error notification email addresses
- Include AWB in alerts
- Automatic retry on failures

## API Endpoints

### Webhooks
- `POST /webhooks/orders/paid` - Order payment webhook
- `POST /webhooks/orders/updated` - Order update webhook
- `POST /webhooks/fulfillments/update` - Fulfillment update webhook

### App Routes
- `GET /app` - Main app dashboard
- `GET /app/settings` - Settings page
- `POST /app/settings` - Update settings
- `POST /app/test-connection` - Test EcoFreight connection
- `GET /app/shipments` - View shipments

## Background Jobs

- **CreateShipmentJob**: Creates shipments in EcoFreight
- **GenerateLabelJob**: Downloads and processes labels
- **TrackSyncJob**: Syncs tracking status (future)

## Error Handling

- **Automatic retries** with exponential backoff
- **Email notifications** for persistent failures
- **Detailed logging** for debugging
- **Manual retry options** in the app interface

## Security

- **Encrypted storage** of sensitive credentials
- **Webhook verification** for all incoming requests
- **PII redaction** in logs and error messages
- **HTTPS required** for all communications

## Development

### Running locally with ngrok

1. **Install ngrok**
   ```bash
   npm install -g ngrok
   ```

2. **Start Laravel development server**
   ```bash
   php artisan serve
   ```

3. **Expose with ngrok**
   ```bash
   ngrok http 8000
   ```

4. **Update app URL** in Shopify Partner dashboard to ngrok URL

### Testing

```bash
# Run tests
php artisan test

# Run specific test
php artisan test --filter=CreateShipmentJob
```

### Debugging

- Check logs in `storage/logs/laravel.log`
- Use Shopify CLI for local development
- Monitor webhook deliveries in Shopify admin

## Deployment

### Production Requirements

- **SSL certificate** (required by Shopify)
- **Queue worker** running continuously
- **Database** with proper indexing
- **File storage** for label files
- **Email service** for notifications

### Environment Variables

Ensure all required environment variables are set:
- Database credentials
- Shopify app credentials
- EcoFreight API credentials
- Encryption key
- Mail configuration

## Support

For issues and questions:
1. Check the logs for error details
2. Verify EcoFreight API connectivity
3. Test with sandbox credentials first
4. Contact support with specific error messages

## License

This project is licensed under the MIT License.

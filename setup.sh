#!/bin/bash

echo "🚀 Setting up EcoFreight Shopify App..."

# Copy environment file
cp env.example .env

# Generate application key
php artisan key:generate

# Generate session secret
SESSION_SECRET=$(openssl rand -hex 32)
sed -i "s/your_session_secret_32_characters_long/$SESSION_SECRET/" .env

# Generate encryption key
ENCRYPTION_KEY=$(openssl rand -hex 16)
sed -i "s/your_encryption_key_32_chars/$ENCRYPTION_KEY/" .env

# Update Shopify credentials
sed -i "s/your_shopify_api_key/7793b6863d3303fe7f295b8f19c6b4c4/" .env
sed -i "s/your_shopify_api_secret/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/" .env

echo "✅ Environment configured with your Shopify credentials!"
echo ""
echo "Next steps:"
echo "1. Update SHOPIFY_APP_URL in .env with your actual app URL"
echo "2. Configure your database credentials in .env"
echo "3. Run: php artisan migrate"
echo "4. Run: php artisan queue:work"
echo ""
echo "Your Shopify App Credentials:"
echo "API Key: 7793b6863d3303fe7f295b8f19c6b4c4"
echo "API Secret: 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"
echo "Admin API Token: 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"

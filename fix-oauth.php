<?php
// Configuration script to help fix Shopify OAuth
// Run this script to update your .env file

echo "🔧 Shopify OAuth Configuration Helper\n";
echo "=====================================\n\n";

echo "Add these lines to your .env file:\n\n";

echo "# Shopify Configuration\n";
echo "SHOPIFY_API_KEY=7793b6863d3303fe7f295b8f19c6b4c4\n";
echo "SHOPIFY_API_SECRET=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\n";
echo "SHOPIFY_REDIRECT_URI=http://localhost:8000/app/shopify/callback\n";
echo "APP_URL=http://localhost:8000\n\n";

echo "📋 Next Steps:\n";
echo "1. Add the above lines to your .env file\n";
echo "2. Go to Shopify Partner Dashboard\n";
echo "3. Update your app settings:\n";
echo "   - App URL: http://localhost:8000\n";
echo "   - Allowed redirection URLs: http://localhost:8000/app/shopify/callback\n";
echo "4. Restart your server\n";
echo "5. Use http://localhost:8000 (not 127.0.0.1:8000)\n\n";

echo "✅ This should fix the OAuth error!\n";

<?php

echo "🔧 Configuring EcoFreight Shopify App Environment\n";
echo "===============================================\n\n";

// Read the .env file
$envFile = '.env';
if (!file_exists($envFile)) {
    echo "❌ .env file not found. Please run 'copy env.example .env' first.\n";
    exit(1);
}

$envContent = file_get_contents($envFile);

// Update Shopify credentials
$envContent = str_replace('SHOPIFY_API_KEY=your_shopify_api_key', 'SHOPIFY_API_KEY=7793b6863d3303fe7f295b8f19c6b4c4', $envContent);
$envContent = str_replace('SHOPIFY_API_SECRET=your_shopify_api_secret', 'SHOPIFY_API_SECRET=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', $envContent);

// Generate session secret
$sessionSecret = bin2hex(random_bytes(32));
$envContent = str_replace('your_session_secret_32_characters_long', $sessionSecret, $envContent);

// Generate encryption key
$encryptionKey = bin2hex(random_bytes(16));
$envContent = str_replace('your_encryption_key_32_chars', $encryptionKey, $envContent);

// Write the updated .env file
file_put_contents($envFile, $envContent);

echo "✅ Environment configured successfully!\n";
echo "\nYour Shopify credentials have been set:\n";
echo "API Key: 7793b6863d3303fe7f295b8f19c6b4c4\n";
echo "API Secret: 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\n";
echo "\nNext steps:\n";
echo "1. Update your database credentials in .env\n";
echo "2. Run: php artisan migrate\n";
echo "3. Run: php artisan queue:work\n";
echo "4. Test your credentials: php test-credentials.php\n";
echo "\n";

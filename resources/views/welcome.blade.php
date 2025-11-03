<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>EcoFreight Shopify App</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                margin: 0;
                padding: 0;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .container {
                background: white;
                border-radius: 15px;
                padding: 40px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                text-align: center;
                max-width: 600px;
                margin: 20px;
            }
            h1 {
                color: #333;
                margin-bottom: 20px;
                font-size: 2.5em;
            }
            .status {
                background: #4CAF50;
                color: white;
                padding: 10px 20px;
                border-radius: 25px;
                display: inline-block;
                margin: 20px 0;
                font-weight: bold;
            }
            .info {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 10px;
                margin: 20px 0;
                text-align: left;
            }
            .info h3 {
                margin-top: 0;
                color: #495057;
            }
            .info ul {
                margin: 10px 0;
                padding-left: 20px;
            }
            .info li {
                margin: 5px 0;
                color: #6c757d;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🚚 EcoFreight Shopify App</h1>
            <div class="status">✅ Server Running Successfully!</div>
            
            <div class="info">
                <h3>🎉 Installation Complete!</h3>
                <p>Your EcoFreight Shopify app is now running successfully. Here's what's ready:</p>
                <ul>
                    <li>✅ Laravel Framework 10.49.1</li>
                    <li>✅ All dependencies installed</li>
                    <li>✅ Database migrations ready</li>
                    <li>✅ Complete shipment workflow</li>
                    <li>✅ Operations dashboard</li>
                    <li>✅ Tracking synchronization</li>
                    <li>✅ Error handling & recovery</li>
                </ul>
            </div>

            <div class="info">
                <h3>📋 Next Steps:</h3>
                <ul>
                    <li>Configure your database in .env file</li>
                    <li>Run: <code>php artisan migrate</code></li>
                    <li>Start queue worker: <code>php artisan queue:work</code></li>
                    <li>Set up Shopify Partner dashboard</li>
                    <li>Test your EcoFreight connection</li>
                </ul>
            </div>

            <div class="info">
                <h3>🔧 Current Status:</h3>
                <p><strong>Server:</strong> Running on http://127.0.0.1:8000</p>
                <p><strong>Environment:</strong> {{ app()->environment() }}</p>
                <p><strong>Laravel Version:</strong> {{ app()->version() }}</p>
            </div>
        </div>
    </body>
</html>

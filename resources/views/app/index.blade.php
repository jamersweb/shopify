<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoFreight - {{ $shopRecord->name }}</title>
    <script src="https://unpkg.com/@shopify/app-bridge@3"></script>
    <script src="https://unpkg.com/@shopify/app-bridge-utils@3"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f6f6f7;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: #008060;
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .nav {
            background: white;
            padding: 0;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
        }
        .nav li {
            flex: 1;
        }
        .nav a {
            display: block;
            padding: 15px 20px;
            text-decoration: none;
            color: #333;
            text-align: center;
            border-right: 1px solid #eee;
            transition: background-color 0.2s;
        }
        .nav li:last-child a {
            border-right: none;
        }
        .nav a:hover,
        .nav a.active {
            background: #f0f0f0;
            color: #008060;
        }
        .content {
            background: white;
            margin-top: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .welcome {
            padding: 40px;
            text-align: center;
        }
        .welcome h2 {
            color: #333;
            margin-bottom: 20px;
        }
        .welcome p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            max-width: 600px;
            margin: 0 auto 30px;
        }
        .btn {
            background: #008060;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin: 0 10px;
        }
        .btn:hover {
            background: #006b4f;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #008060;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #008060;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        .quick-actions {
            padding: 30px;
            border-top: 1px solid #eee;
        }
        .quick-actions h3 {
            margin: 0 0 20px 0;
            color: #333;
        }
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .action-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #e9ecef;
        }
        .action-card h4 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .action-card p {
            color: #666;
            font-size: 14px;
            margin: 0 0 15px 0;
        }
        .setup-required {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 20px;
            border-radius: 8px;
            margin: 20px;
        }
        .setup-required h3 {
            margin: 0 0 10px 0;
        }
        .setup-required p {
            margin: 0 0 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>EcoFreight Shipping</h1>
            <p>{{ $shopRecord->name }}</p>
        </div>
        
        <div class="nav">
            <ul>
                <li><a href="/app?shop={{ $shop }}" class="active">Dashboard</a></li>
                <li><a href="/app/shipments?shop={{ $shop }}">Shipments</a></li>
                <li><a href="/app/settings?shop={{ $shop }}">Settings</a></li>
            </ul>
        </div>
        
        <div class="content">
            @if(!$shopRecord->settings || !$shopRecord->settings->ship_from_company)
                <div class="setup-required">
                    <h3>Setup Required</h3>
                    <p>Please configure your EcoFreight settings before using the app. You'll need to set up your connection credentials and ship-from address.</p>
                    <a href="/app/settings?shop={{ $shop }}" class="btn">Go to Settings</a>
                </div>
            @else
                <div class="welcome">
                    <h2>Welcome to EcoFreight Shipping</h2>
                    <p>Your EcoFreight integration is ready to use. Create shipments, generate labels, and track packages directly from your Shopify orders.</p>
                    
                    <div class="stats">
                        <div class="stat-card">
                            <div class="stat-number">{{ $shopRecord->shipments()->count() }}</div>
                            <div class="stat-label">Total Shipments</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">{{ $shopRecord->shipments()->where('status', 'pending')->count() }}</div>
                            <div class="stat-label">Pending</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">{{ $shopRecord->shipments()->where('status', 'delivered')->count() }}</div>
                            <div class="stat-label">Delivered</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">{{ $shopRecord->shipments()->where('status', 'error')->count() }}</div>
                            <div class="stat-label">Errors</div>
                        </div>
                    </div>
                </div>
                
                <div class="quick-actions">
                    <h3>Quick Actions</h3>
                    <div class="action-grid">
                        <div class="action-card">
                            <h4>View Shipments</h4>
                            <p>See all your shipments and their current status</p>
                            <a href="/app/shipments?shop={{ $shop }}" class="btn">View Shipments</a>
                        </div>
                        <div class="action-card">
                            <h4>Settings</h4>
                            <p>Configure your EcoFreight connection and preferences</p>
                            <a href="/app/settings?shop={{ $shop }}" class="btn">Settings</a>
                        </div>
                        <div class="action-card">
                            <h4>Test Connection</h4>
                            <p>Verify your EcoFreight API connection is working</p>
                            <button onclick="testConnection()" class="btn">Test Connection</button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        // Initialize Shopify App Bridge
        const app = window['app-bridge'].createApp({
            apiKey: '{{ config("shopify.api_key") }}',
            shopOrigin: '{{ $shop }}',
            forceRedirect: true
        });

        // Test connection function
        async function testConnection() {
            try {
                const response = await fetch('/app/test-connection?shop={{ $shop }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Connection test successful!');
                } else {
                    alert('Connection test failed: ' + result.message);
                }
            } catch (error) {
                alert('Connection test failed: ' + error.message);
            }
        }
    </script>
</body>
</html>

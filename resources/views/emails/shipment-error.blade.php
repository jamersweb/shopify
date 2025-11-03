<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shipment Creation Failed</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #dc3545;
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .content {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .error-details {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #dc3545;
        }
        .btn {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚨 Shipment Creation Failed</h1>
        <p>EcoFreight Shopify App</p>
    </div>
    
    <div class="content">
        <h2>Hello,</h2>
        
        <p>A shipment creation has failed for your Shopify store <strong>{{ $shop->name }}</strong>.</p>
        
        <div class="error-details">
            <h3>Error Details</h3>
            <p><strong>Order:</strong> {{ $shipment->shopify_order_name }}</p>
            <p><strong>Order ID:</strong> {{ $shipment->shopify_order_id }}</p>
            @if($includeAwb && $shipment->ecofreight_awb)
                <p><strong>AWB:</strong> {{ $shipment->ecofreight_awb }}</p>
            @endif
            <p><strong>Error:</strong> {{ $error }}</p>
            <p><strong>Time:</strong> {{ now()->format('M j, Y g:i A') }}</p>
        </div>
        
        <div class="info-box">
            <h4>What you can do:</h4>
            <ul>
                <li>Check your EcoFreight API credentials in the app settings</li>
                <li>Verify the shipping address has a valid phone number</li>
                <li>Ensure your ship-from address is properly configured</li>
                <li>Try creating the shipment manually from the app</li>
            </ul>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ config('shopify.app_url') }}/app/shipments?shop={{ $shop->shopify_domain }}" class="btn">
                View Shipments
            </a>
            <a href="{{ config('shopify.app_url') }}/app/settings?shop={{ $shop->shopify_domain }}" class="btn btn-danger">
                Check Settings
            </a>
        </div>
        
        <p>If you continue to experience issues, please contact support or check the EcoFreight API documentation.</p>
        
        <div class="footer">
            <p>This is an automated message from the EcoFreight Shopify App.</p>
            <p>Store: {{ $shop->name }} ({{ $shop->shopify_domain }})</p>
        </div>
    </div>
</body>
</html>

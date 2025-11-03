<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stale Shipment Alert</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .shipment-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .action-button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
        .action-button:hover {
            background-color: #0056b3;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚨 Stale Shipment Alert</h1>
        <p>A shipment has not been updated for more than 48 hours and may require attention.</p>
    </div>

    <div class="alert">
        <strong>⚠️ Attention Required:</strong> This shipment has not received tracking updates for an extended period and may be experiencing issues.
    </div>

    <div class="shipment-details">
        <h3>Shipment Details</h3>
        <p><strong>Order Number:</strong> {{ $shipment->shopify_order_name }}</p>
        <p><strong>AWB:</strong> {{ $shipment->ecofreight_awb }}</p>
        <p><strong>Service:</strong> {{ ucfirst($shipment->service_type) }}</p>
        <p><strong>Current Status:</strong> {{ ucfirst($shipment->status) }}</p>
        <p><strong>Last Checked:</strong> {{ $shipment->last_checked_at ? $shipment->last_checked_at->format('Y-m-d H:i:s') : 'Never' }}</p>
        <p><strong>Created:</strong> {{ $shipment->created_at->format('Y-m-d H:i:s') }}</p>
        @if($shipment->cod_enabled)
        <p><strong>COD Amount:</strong> AED {{ number_format($shipment->cod_amount, 2) }}</p>
        @endif
    </div>

    <div class="actions">
        <h3>Recommended Actions</h3>
        <p>Please check the shipment status and take appropriate action:</p>
        
        <a href="{{ route('app.ops', ['shop' => $shop->shopify_domain]) }}" class="action-button">
            View in Ops Dashboard
        </a>
        
        @if($shipment->can_reship)
        <a href="#" class="action-button" onclick="alert('Use the Ops Dashboard to re-ship this order')">
            Re-ship Order
        </a>
        @endif
    </div>

    <div class="footer">
        <p>This is an automated alert from the EcoFreight Shopify App.</p>
        <p>If you believe this is an error, please check the shipment status in your EcoFreight account.</p>
        <p>To manage alert settings, visit your app settings in Shopify.</p>
    </div>
</body>
</html>

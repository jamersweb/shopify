<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Shipment;
use App\Models\Shop;
use App\Services\EcoFreightService;

class TestPayloadCommand extends Command
{
    protected $signature = 'test:payload {shipment_id}';
    protected $description = 'Test shipment payload validation for a specific shipment';

    public function handle()
    {
        $shipmentId = $this->argument('shipment_id');
        
        $shipment = Shipment::with(['shop.settings'])->find($shipmentId);
        
        if (!$shipment) {
            $this->error("Shipment {$shipmentId} not found");
            return 1;
        }

        $shop = $shipment->shop;
        if (!$shop || !$shop->settings) {
            $this->error("Shop or settings not found for shipment {$shipmentId}");
            return 1;
        }

        $this->info("Testing payload for shipment {$shipmentId} (Order: {$shipment->shopify_order_name})");

        // Get order data from shipment_data
        $orderData = $shipment->shipment_data;
        
        if (!$orderData || !is_array($orderData)) {
            $this->error("No order data found in shipment");
            return 1;
        }

        $this->info("Order data loaded successfully");

        // Test payload building
        try {
            $ecofreightService = new EcoFreightService($shop->settings);
            $payload = $ecofreightService->buildShipmentPayload($orderData, $shop->settings);
            
            $this->info("✅ Payload built successfully!");
            $this->line("Payload structure:");
            $this->line(json_encode($payload, JSON_PRETTY_PRINT));
            
        } catch (\Exception $e) {
            $this->error("❌ Payload validation failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
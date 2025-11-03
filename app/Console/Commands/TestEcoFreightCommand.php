<?php

namespace App\Console\Commands;

use App\Models\Shop;
use App\Models\Shipment;
use App\Services\EcoFreightService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

class TestEcoFreightCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecofreight:test 
                            {shop : Shop domain}
                            {action : Action to test (connection, create-shipment, track, cancel)}
                            {--awb= : AWB for tracking/cancel actions}
                            {--order-id= : Order ID for shipment creation}
                            {--simulate-error : Simulate an error response}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test EcoFreight API integration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $shopDomain = $this->argument('shop');
        $action = $this->argument('action');
        $awb = $this->option('awb');
        $orderId = $this->option('order-id');
        $simulateError = $this->option('simulate-error');

        $shop = Shop::where('shopify_domain', $shopDomain)->first();
        
        if (!$shop) {
            $this->error("Shop not found: {$shopDomain}");
            return 1;
        }

        if (!$shop->settings) {
            $this->error("Shop settings not configured");
            return 1;
        }

        $ecofreightService = new EcoFreightService($shop->settings);

        switch ($action) {
            case 'connection':
                $this->testConnection($ecofreightService);
                break;
                
            case 'create-shipment':
                $this->testCreateShipment($ecofreightService, $shop, $orderId, $simulateError);
                break;
                
            case 'track':
                $this->testTrackShipment($ecofreightService, $awb, $simulateError);
                break;
                
            case 'cancel':
                $this->testCancelShipment($ecofreightService, $awb, $simulateError);
                break;
                
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }

        return 0;
    }

    /**
     * Test connection to EcoFreight API.
     */
    protected function testConnection(EcoFreightService $service)
    {
        $this->info('Testing EcoFreight connection...');
        
        $result = $service->testConnection();
        
        if ($result['success']) {
            $this->info('✅ Connection successful!');
            $this->line('Response: ' . json_encode($result['data'], JSON_PRETTY_PRINT));
        } else {
            $this->error('❌ Connection failed!');
            $this->error('Error: ' . $result['message']);
        }
    }

    /**
     * Test shipment creation.
     */
    protected function testCreateShipment(EcoFreightService $service, Shop $shop, $orderId, $simulateError)
    {
        if (!$orderId) {
            $this->error('Order ID is required for shipment creation test');
            return;
        }

        $this->info("Testing shipment creation for order: {$orderId}");

        // Get order data from Shopify
        $orderData = $this->getOrderData($shop, $orderId);
        
        if (!$orderData) {
            $this->error('Failed to retrieve order data from Shopify');
            return;
        }

        // Build shipment payload
        $shipmentPayload = $service->buildShipmentPayload($orderData, $shop->settings);
        
        if ($simulateError) {
            $this->warn('Simulating error response...');
            $shipmentPayload['consignee']['phone'] = ''; // Invalid phone to trigger error
        }

        $this->line('Shipment payload: ' . json_encode($shipmentPayload, JSON_PRETTY_PRINT));

        // Create shipment
        $result = $service->createShipment($shipmentPayload);
        
        if ($result['success']) {
            $this->info('✅ Shipment created successfully!');
            $this->line('Response: ' . json_encode($result['data'], JSON_PRETTY_PRINT));
        } else {
            $this->error('❌ Shipment creation failed!');
            $this->error('Error: ' . $result['message']);
        }
    }

    /**
     * Test shipment tracking.
     */
    protected function testTrackShipment(EcoFreightService $service, $awb, $simulateError)
    {
        if (!$awb) {
            $this->error('AWB is required for tracking test');
            return;
        }

        $this->info("Testing tracking for AWB: {$awb}");

        if ($simulateError) {
            $this->warn('Simulating error response...');
            $awb = 'INVALID_AWB'; // Invalid AWB to trigger error
        }

        $result = $service->trackShipment($awb);
        
        if ($result['success']) {
            $this->info('✅ Tracking successful!');
            $this->line('Response: ' . json_encode($result['data'], JSON_PRETTY_PRINT));
        } else {
            $this->error('❌ Tracking failed!');
            $this->error('Error: ' . $result['message']);
        }
    }

    /**
     * Test shipment cancellation.
     */
    protected function testCancelShipment(EcoFreightService $service, $awb, $simulateError)
    {
        if (!$awb) {
            $this->error('AWB is required for cancellation test');
            return;
        }

        $this->info("Testing cancellation for AWB: {$awb}");

        if ($simulateError) {
            $this->warn('Simulating error response...');
            $awb = 'INVALID_AWB'; // Invalid AWB to trigger error
        }

        $result = $service->cancelShipment($awb);
        
        if ($result['success']) {
            $this->info('✅ Cancellation successful!');
            $this->line('Response: ' . json_encode($result['data'], JSON_PRETTY_PRINT));
        } else {
            $this->error('❌ Cancellation failed!');
            $this->error('Error: ' . $result['message']);
        }
    }

    /**
     * Get order data from Shopify.
     */
    protected function getOrderData(Shop $shop, $orderId)
    {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get("https://{$shop->shopify_domain}/admin/api/2023-10/orders/{$orderId}.json", [
                'headers' => [
                    'X-Shopify-Access-Token' => $shop->shopify_token,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['order'] ?? null;

        } catch (\Exception $e) {
            $this->error('Failed to get order data: ' . $e->getMessage());
            return null;
        }
    }
}

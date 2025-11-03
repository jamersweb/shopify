<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Shipment;
use App\Services\EcoFreightService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class WebhookController extends Controller
{
    /**
     * Handle orders/paid webhook.
     */
    public function ordersPaid(Request $request)
    {
        $requestId = uniqid('req_', true);
        $startTime = microtime(true);
        
        try {
            // Verify webhook authenticity
            if (!$this->verifyWebhook($request)) {
                Log::warning('Invalid webhook signature', [
                    'request_id' => $requestId,
                    'headers' => $this->redactSensitiveHeaders($request->headers->all()),
                ]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $orderData = $request->all();
            $shopDomain = $request->header('X-Shopify-Shop-Domain');
            
            if (!$shopDomain) {
                Log::error('Missing shop domain in webhook', [
                    'request_id' => $requestId,
                ]);
                return response()->json(['error' => 'Missing shop domain'], 400);
            }

            $shop = Shop::where('shopify_domain', $shopDomain)->first();
            
            if (!$shop) {
                Log::error('Shop not found for webhook', [
                    'request_id' => $requestId,
                    'shop' => $shopDomain,
                ]);
                return response()->json(['error' => 'Shop not found'], 404);
            }

            if (!$shop->settings) {
                Log::warning('Shop settings not configured', [
                    'request_id' => $requestId,
                    'shop' => $shopDomain,
                ]);
                return response()->json(['error' => 'Shop settings not configured'], 400);
            }

            // Check if shipment already exists
            $existingShipment = Shipment::where('shop_id', $shop->id)
                ->where('shopify_order_id', $orderData['id'])
                ->first();

            if ($existingShipment) {
                Log::info('Shipment already exists for order', [
                    'request_id' => $requestId,
                    'shop' => $shopDomain,
                    'order_id' => $orderData['id'],
                    'shipment_id' => $existingShipment->id,
                ]);
                return response()->json(['message' => 'Shipment already exists']);
            }

            // Validate required data
            $validationResult = $this->validateOrderData($orderData);
            if (!$validationResult['valid']) {
                Log::warning('Invalid order data for shipment creation', [
                    'request_id' => $requestId,
                    'shop' => $shopDomain,
                    'order_id' => $orderData['id'],
                    'validation_errors' => $validationResult['errors'],
                ]);
                return response()->json(['error' => 'Invalid order data: ' . implode(', ', $validationResult['errors'])], 400);
            }

            // Create shipment record
            $shipment = Shipment::create([
                'shop_id' => $shop->id,
                'shopify_order_id' => $orderData['id'],
                'shopify_order_name' => $orderData['name'],
                'service_type' => $this->mapServiceType($orderData['shipping_lines'][0]['title'] ?? ''),
                'status' => 'pending',
                'cod_enabled' => $shop->settings->cod_enabled,
                'cod_amount' => $this->calculateCodAmount($orderData, $shop->settings),
            ]);

            // Queue shipment creation job with request ID
            Queue::push(new \App\Jobs\CreateShipmentJob($shop->id, $shipment->id, $requestId));

            $latency = round((microtime(true) - $startTime) * 1000);
            
            Log::info('Shipment creation queued', [
                'request_id' => $requestId,
                'shop' => $shopDomain,
                'order_id' => $orderData['id'],
                'order_name' => $orderData['name'],
                'shipment_id' => $shipment->id,
                'service_type' => $shipment->service_type,
                'cod_enabled' => $shipment->cod_enabled,
                'cod_amount' => $shipment->cod_amount,
                'latency_ms' => $latency,
            ]);

            return response()->json([
                'message' => 'Shipment creation queued',
                'request_id' => $requestId,
                'shipment_id' => $shipment->id,
            ]);

        } catch (\Exception $e) {
            $latency = round((microtime(true) - $startTime) * 1000);
            
            Log::error('Orders paid webhook failed', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'latency_ms' => $latency,
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle orders/updated webhook.
     */
    public function ordersUpdated(Request $request)
    {
        try {
            // Verify webhook authenticity
            if (!$this->verifyWebhook($request)) {
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $orderData = $request->all();
            $shopDomain = $request->header('X-Shopify-Shop-Domain');
            
            $shop = Shop::where('shopify_domain', $shopDomain)->first();
            
            if (!$shop) {
                return response()->json(['error' => 'Shop not found'], 404);
            }

            // Check if there's a pending shipment for this order
            $shipment = Shipment::where('shop_id', $shop->id)
                ->where('shopify_order_id', $orderData['id'])
                ->where('status', 'pending')
                ->first();

            if ($shipment) {
                // Check if shipping address changed
                $shippingAddress = $orderData['shipping_address'] ?? null;
                
                if ($shippingAddress && !$this->validateShippingAddress($shippingAddress)) {
                    $shipment->update([
                        'status' => 'error',
                        'error_message' => 'Invalid shipping address: phone number is required',
                    ]);

                    Log::warning('Shipment marked as error due to invalid address', [
                        'shop' => $shopDomain,
                        'order_id' => $orderData['id'],
                        'shipment_id' => $shipment->id,
                    ]);
                }
            }

            return response()->json(['message' => 'Order update processed']);

        } catch (\Exception $e) {
            Log::error('Orders updated webhook failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle fulfillments/update webhook.
     */
    public function fulfillmentsUpdate(Request $request)
    {
        try {
            // Verify webhook authenticity
            if (!$this->verifyWebhook($request)) {
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $fulfillmentData = $request->all();
            $shopDomain = $request->header('X-Shopify-Shop-Domain');
            
            $shop = Shop::where('shopify_domain', $shopDomain)->first();
            
            if (!$shop) {
                return response()->json(['error' => 'Shop not found'], 404);
            }

            // Find corresponding shipment
            $shipment = Shipment::where('shop_id', $shop->id)
                ->where('shopify_order_id', $fulfillmentData['order_id'])
                ->first();

            if ($shipment) {
                // Update shipment status based on fulfillment status
                $status = $this->mapFulfillmentStatus($fulfillmentData['status']);
                $shipment->update(['status' => $status]);

                Log::info('Shipment status updated from fulfillment webhook', [
                    'shop' => $shopDomain,
                    'order_id' => $fulfillmentData['order_id'],
                    'shipment_id' => $shipment->id,
                    'new_status' => $status,
                ]);
            }

            return response()->json(['message' => 'Fulfillment update processed']);

        } catch (\Exception $e) {
            Log::error('Fulfillments update webhook failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Verify webhook authenticity.
     */
    protected function verifyWebhook(Request $request): bool
    {
        $hmac = $request->header('X-Shopify-Hmac-Sha256');
        $data = $request->getContent();
        $secret = config('shopify.api_secret');

        if (!$hmac || !$secret) {
            return false;
        }

        $calculatedHmac = base64_encode(hash_hmac('sha256', $data, $secret, true));

        return hash_equals($hmac, $calculatedHmac);
    }

    /**
     * Validate order data for shipment creation.
     */
    protected function validateOrderData(array $orderData): array
    {
        $errors = [];

        // Check if order is paid
        if ($orderData['financial_status'] !== 'paid') {
            $errors[] = 'Order is not paid';
        }

        // Check if order has shipping address
        if (!isset($orderData['shipping_address'])) {
            $errors[] = 'No shipping address';
        } else {
            // Validate shipping address
            $addressErrors = $this->validateShippingAddress($orderData['shipping_address']);
            if (!empty($addressErrors)) {
                $errors = array_merge($errors, $addressErrors);
            }
        }

        // Check if order has line items
        if (empty($orderData['line_items'])) {
            $errors[] = 'No line items';
        }

        // Check destination country (blocking if not UAE)
        if (isset($orderData['shipping_address']['country']) && 
            strtoupper($orderData['shipping_address']['country']) !== 'UAE' &&
            strtoupper($orderData['shipping_address']['country']) !== 'UNITED ARAB EMIRATES') {
            $errors[] = 'Destination country must be UAE';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate shipping address.
     */
    protected function validateShippingAddress(array $address): array
    {
        $errors = [];

        // Phone number is required by most couriers (blocking)
        if (empty($address['phone'])) {
            $errors[] = 'Customer phone number is required';
        }

        // Basic address validation (blocking)
        if (empty($address['address1'])) {
            $errors[] = 'Address line 1 is required';
        }
        
        if (empty($address['city'])) {
            $errors[] = 'City is required';
        }
        
        if (empty($address['country'])) {
            $errors[] = 'Country is required';
        }

        return $errors;
    }

    /**
     * Map Shopify shipping rate title to service type.
     */
    protected function mapServiceType(string $rateTitle): string
    {
        $rateTitle = strtolower($rateTitle);
        
        if (str_contains($rateTitle, 'express')) {
            return 'express';
        }
        
        return 'standard';
    }

    /**
     * Map Shopify fulfillment status to shipment status.
     */
    protected function mapFulfillmentStatus(string $fulfillmentStatus): string
    {
        $mapping = [
            'pending' => 'pending',
            'open' => 'pending',
            'success' => 'shipped',
            'cancelled' => 'cancelled',
            'error' => 'error',
            'failure' => 'error',
        ];

        return $mapping[$fulfillmentStatus] ?? 'pending';
    }

    /**
     * Calculate COD amount based on order data and settings.
     */
    protected function calculateCodAmount(array $orderData, $settings): ?float
    {
        if (!$settings->cod_enabled) {
            return null;
        }

        // COD amount = order total + COD fee
        $orderTotal = (float) $orderData['total_price'];
        $codFee = (float) $settings->cod_fee;
        
        return $orderTotal + $codFee;
    }

    /**
     * Redact sensitive headers for logging.
     */
    protected function redactSensitiveHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'x-shopify-hmac-sha256', 'cookie'];
        
        foreach ($headers as $key => $value) {
            if (in_array(strtolower($key), $sensitiveHeaders)) {
                $headers[$key] = ['[REDACTED]'];
            }
        }
        
        return $headers;
    }
}

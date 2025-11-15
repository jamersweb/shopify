<?php

namespace App\Jobs;

use App\Models\Shop;
use App\Models\Shipment;
use App\Services\EcoFreightService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CreateShipmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min

    protected $shopId;
    protected $shipmentId;
    protected $requestId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $shopId, int $shipmentId, string $requestId = null)
    {
        $this->shopId = $shopId;
        $this->shipmentId = $shipmentId;
        $this->requestId = $requestId ?: uniqid('job_', true);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        
        // Log job start
        Log::info('CreateShipmentJob started', [
            'request_id' => $this->requestId,
            'shop_id' => $this->shopId,
            'shipment_id' => $this->shipmentId,
        ]);
        
        try {
            $shop = Shop::find($this->shopId);
            $shipment = Shipment::find($this->shipmentId);

            if (!$shop || !$shipment || !$shop->settings) {
                $errorMsg = 'Shop, shipment, or settings not found';
                Log::error('CreateShipmentJob: ' . $errorMsg, [
                    'request_id' => $this->requestId,
                    'shop_id' => $this->shopId,
                    'shipment_id' => $this->shipmentId,
                    'shop_exists' => $shop ? 'yes' : 'no',
                    'shipment_exists' => $shipment ? 'yes' : 'no',
                    'settings_exists' => ($shop && $shop->settings) ? 'yes' : 'no',
                ]);
                
                if ($shipment) {
                    $shipment->update([
                        'status' => 'error',
                        'error_message' => $errorMsg,
                    ]);
                }
                return;
            }

            // Validate origin settings (blocking)
            $originValidation = $this->validateOriginSettings($shop->settings);
            if (!$originValidation['valid']) {
                $shipment->update([
                    'status' => 'error',
                    'error_message' => 'Origin settings invalid: ' . implode(', ', $originValidation['errors']),
                ]);
                $this->sendErrorNotification($shop, $shipment, 'Origin settings invalid: ' . implode(', ', $originValidation['errors']));
                return;
            }

            // Get order data from Shopify
            $orderData = $this->getOrderData($shop, $shipment->shopify_order_id);
            
            if (!$orderData) {
                $shipment->update([
                    'status' => 'error',
                    'error_message' => 'Failed to retrieve order data from Shopify',
                ]);
                return;
            }
            
            // Preserve original order data in shipment_data if not already set
            if (!$shipment->shipment_data || !isset($shipment->shipment_data['customer'])) {
                $shipment->update(['shipment_data' => $orderData]);
            }

            // Build shipment payload
            $ecofreightService = new EcoFreightService($shop->settings);
            
            try {
                $shipmentPayload = $ecofreightService->buildShipmentPayload($orderData, $shop->settings);
            } catch (\InvalidArgumentException $e) {
                $shipment->update([
                    'status' => 'error',
                    'error_message' => 'Invalid payload: ' . $e->getMessage(),
                ]);
                $this->sendErrorNotification($shop, $shipment, 'Invalid payload: ' . $e->getMessage());
                return;
            }

            Log::info('Creating shipment in EcoFreight', [
                'request_id' => $this->requestId,
                'shop_id' => $this->shopId,
                'shipment_id' => $this->shipmentId,
                'order_id' => $shipment->shopify_order_id,
                'order_name' => $shipment->shopify_order_name,
                'service_type' => $shipment->service_type,
                'cod_enabled' => $shipment->cod_enabled,
                'cod_amount' => $shipment->cod_amount,
                'payload' => $ecofreightService->redactSensitiveData($shipmentPayload),
            ]);

            // Create shipment in EcoFreight
            $result = $ecofreightService->createShipment($shipmentPayload);

            if (!$result['success']) {
                $shipment->update([
                    'status' => 'error',
                    'error_message' => $result['message'],
                    'retry_count' => $this->attempts(),
                ]);

                Log::error('EcoFreight shipment creation failed', [
                    'request_id' => $this->requestId,
                    'shop_id' => $this->shopId,
                    'shipment_id' => $this->shipmentId,
                    'error' => $result['message'],
                    'attempt' => $this->attempts(),
                ]);

                if ($this->attempts() < $this->tries) {
                    $this->release($this->backoff[$this->attempts() - 1]);
                } else {
                    $this->sendErrorNotification($shop, $shipment, $result['message']);
                }
                return;
            }

            // Update shipment with EcoFreight data
            $shipmentData = $result['data'];
            
            // Extract AWB from valid_orders if available (new API format)
            $awb = null;
            $reference = null;
            $trackingUrl = null;
            $orderId = null;
            $labelPrintUrl = null;
            
            if (isset($result['valid_orders']) && !empty($result['valid_orders'])) {
                // Get the first valid order's data
                $validOrderData = $result['valid_orders'][0]['data'] ?? [];
                // API returns tracking_no as the AWB number
                $awb = $validOrderData['tracking_no'] ?? $validOrderData['awb'] ?? $validOrderData['AWB'] ?? null;
                $reference = $validOrderData['order_reference'] ?? $validOrderData['reference'] ?? null;
                $orderId = $validOrderData['order_id'] ?? null;
                $labelPrintUrl = $validOrderData['awb_label_print'] ?? null;
                $trackingUrl = $validOrderData['tracking_url'] ?? $labelPrintUrl ?? null;
            } else {
                // Fallback to old format
                $awb = $shipmentData['tracking_no'] ?? $shipmentData['awb'] ?? $shipmentData['AWB'] ?? null;
                $reference = $shipmentData['order_reference'] ?? $shipmentData['reference'] ?? null;
                $orderId = $shipmentData['order_id'] ?? null;
                $labelPrintUrl = $shipmentData['awb_label_print'] ?? null;
                $trackingUrl = $shipmentData['tracking_url'] ?? $labelPrintUrl ?? null;
            }
            
            // Preserve original order data and merge with EcoFreight response
            $originalOrderData = $shipment->shipment_data ?? $orderData;
            $updatedShipmentData = array_merge($originalOrderData, [
                'ecofreight_response' => $shipmentData,
                'ecofreight_awb' => $awb,
                'ecofreight_reference' => $reference,
                'ecofreight_order_id' => $orderId,
                'awb_label_print' => $labelPrintUrl,
            ]);
            
            $shipment->update([
                'ecofreight_awb' => $awb,
                'ecofreight_reference' => $reference,
                'status' => 'created',
                'shipment_data' => $updatedShipmentData,
                'tracking_url' => $trackingUrl,
            ]);

            Log::info('Shipment created in EcoFreight', [
                'request_id' => $this->requestId,
                'shop_id' => $this->shopId,
                'shipment_id' => $this->shipmentId,
                'awb' => $awb,
                'ecofreight_reference' => $reference,
                'valid_orders_count' => count($result['valid_orders'] ?? []),
                'invalid_orders_count' => count($result['invalid_orders'] ?? []),
            ]);

            // Queue label generation and fulfillment creation
            if ($awb) {
                GenerateLabelJob::dispatch($this->shopId, $this->shipmentId, $this->requestId);
            }

            $latency = round((microtime(true) - $startTime) * 1000);
            
            Log::info('CreateShipmentJob completed successfully', [
                'request_id' => $this->requestId,
                'shop_id' => $this->shopId,
                'shipment_id' => $this->shipmentId,
                'awb' => $awb,
                'latency_ms' => $latency,
            ]);

        } catch (\Exception $e) {
            $latency = round((microtime(true) - $startTime) * 1000);
            
            Log::error('CreateShipmentJob failed', [
                'request_id' => $this->requestId,
                'shop_id' => $this->shopId,
                'shipment_id' => $this->shipmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'latency_ms' => $latency,
            ]);

            $shipment = Shipment::find($this->shipmentId);
            if ($shipment) {
                $shipment->update([
                    'status' => 'error',
                    'error_message' => $e->getMessage(),
                    'retry_count' => $this->attempts(),
                ]);
            }

            if ($this->attempts() >= $this->tries) {
                $this->sendErrorNotification($shop ?? null, $shipment, $e->getMessage());
            }
        }
    }

    /**
     * Validate origin settings (blocking validation).
     */
    protected function validateOriginSettings($settings): array
    {
        $errors = [];

        // Required fields for origin
        if (empty($settings->ship_from_phone)) {
            $errors[] = 'Origin phone is required';
        }
        
        if (empty($settings->ship_from_email)) {
            $errors[] = 'Origin email is required';
        }

        if (empty($settings->ship_from_company)) {
            $errors[] = 'Origin company is required';
        }

        if (empty($settings->ship_from_address1)) {
            $errors[] = 'Origin address is required';
        }

        if (empty($settings->ship_from_city)) {
            $errors[] = 'Origin city is required';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get order data from Shopify.
     */
    protected function getOrderData(Shop $shop, int $orderId): ?array
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
            Log::error('Failed to get order data from Shopify', [
                'request_id' => $this->requestId,
                'shop_id' => $shop->id,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Send error notification email.
     */
    protected function sendErrorNotification(?Shop $shop, ?Shipment $shipment, string $errorMessage): void
    {
        if (!$shop || !$shipment || !$shop->settings) {
            return;
        }

        $emails = $shop->settings->error_alert_emails_array;
        
        if (empty($emails)) {
            return;
        }

        try {
            Mail::send('emails.shipment-error', [
                'shop' => $shop,
                'shipment' => $shipment,
                'error' => $errorMessage,
                'includeAwb' => $shop->settings->include_awb_in_alerts,
            ], function ($message) use ($emails, $shop, $shipment) {
                $message->to($emails)
                    ->subject("Shipment Creation Failed - {$shop->name} - Order {$shipment->shopify_order_name}");
            });

            Log::info('Error notification sent', [
                'shop_id' => $shop->id,
                'shipment_id' => $shipment->id,
                'emails' => $emails,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send error notification', [
                'shop_id' => $shop->id,
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CreateShipmentJob permanently failed', [
            'shop_id' => $this->shopId,
            'shipment_id' => $this->shipmentId,
            'error' => $exception->getMessage(),
        ]);

        $shipment = Shipment::find($this->shipmentId);
        if ($shipment) {
            $shipment->update([
                'status' => 'error',
                'error_message' => 'Job failed permanently: ' . $exception->getMessage(),
            ]);
        }
    }
}

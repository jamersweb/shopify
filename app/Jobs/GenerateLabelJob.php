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
use Illuminate\Support\Facades\Storage;

class GenerateLabelJob implements ShouldQueue
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
        $this->requestId = $requestId ?: uniqid('label_', true);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        
        try {
            $shop = Shop::find($this->shopId);
            $shipment = Shipment::find($this->shipmentId);

            if (!$shop || !$shipment || !$shop->settings || !$shipment->ecofreight_awb) {
                Log::error('GenerateLabelJob: Shop, shipment, settings, or AWB not found', [
                    'request_id' => $this->requestId,
                    'shop_id' => $this->shopId,
                    'shipment_id' => $this->shipmentId,
                ]);
                return;
            }

            Log::info('Starting label generation', [
                'request_id' => $this->requestId,
                'shop_id' => $this->shopId,
                'shipment_id' => $this->shipmentId,
                'awb' => $shipment->ecofreight_awb,
            ]);

            $ecofreightService = new EcoFreightService($shop->settings);
            $result = $ecofreightService->getShipmentLabel($shipment->ecofreight_awb);

            if (!$result['success']) {
                Log::warning('Label generation failed, will retry', [
                    'request_id' => $this->requestId,
                    'shop_id' => $this->shopId,
                    'shipment_id' => $this->shipmentId,
                    'awb' => $shipment->ecofreight_awb,
                    'error' => $result['message'],
                    'attempt' => $this->attempts(),
                ]);

                if ($this->attempts() < $this->tries) {
                    $this->release($this->backoff[$this->attempts() - 1]);
                } else {
                    // Mark as label pending and queue a follow-up job
                    $shipment->update([
                        'status' => 'label_pending',
                        'error_message' => 'Label not immediately available: ' . $result['message'],
                    ]);
                    
                    // Queue a delayed retry
                    GenerateLabelJob::dispatch($this->shopId, $this->shipmentId, $this->requestId)
                        ->delay(now()->addMinutes(30));
                }
                return;
            }

            $labelData = $result['data'];
            
            // Download and store label file
            $labelFile = $this->downloadAndStoreLabel($labelData, $shipment);
            
            if (!$labelFile) {
                Log::error('Failed to download and store label file', [
                    'request_id' => $this->requestId,
                    'shop_id' => $this->shopId,
                    'shipment_id' => $this->shipmentId,
                    'awb' => $shipment->ecofreight_awb,
                ]);
                return;
            }

            // Update shipment with label data
            $shipment->update([
                'status' => 'label_generated',
                'label_data' => $labelFile,
            ]);

            Log::info('Label downloaded and stored', [
                'request_id' => $this->requestId,
                'shop_id' => $this->shopId,
                'shipment_id' => $this->shipmentId,
                'awb' => $shipment->ecofreight_awb,
                'label_file' => $labelFile['file_path'],
            ]);

            // Create fulfillment in Shopify
            $fulfillmentResult = $this->createShopifyFulfillment($shop, $shipment);
            
            if ($fulfillmentResult['success']) {
                // Schedule tracking sync
                TrackSyncJob::dispatch($this->shipmentId, false)
                    ->delay(now()->addMinutes(5)); // Start tracking sync after 5 minutes
            }

            $latency = round((microtime(true) - $startTime) * 1000);
            
            Log::info('Label generation and fulfillment completed', [
                'request_id' => $this->requestId,
                'shop_id' => $this->shopId,
                'shipment_id' => $this->shipmentId,
                'awb' => $shipment->ecofreight_awb,
                'fulfillment_success' => $fulfillmentResult['success'],
                'latency_ms' => $latency,
            ]);

        } catch (\Exception $e) {
            $latency = round((microtime(true) - $startTime) * 1000);
            
            Log::error('GenerateLabelJob failed', [
                'request_id' => $this->requestId,
                'shop_id' => $this->shopId,
                'shipment_id' => $this->shipmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'latency_ms' => $latency,
            ]);

            if ($this->attempts() >= $this->tries) {
                $shipment = Shipment::find($this->shipmentId);
                if ($shipment) {
                    $shipment->update([
                        'status' => 'error',
                        'error_message' => 'Label generation failed: ' . $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Download and store label file.
     */
    protected function downloadAndStoreLabel(array $labelData, Shipment $shipment): ?array
    {
        try {
            $labelUrl = $labelData['label_url'] ?? null;
            $labelType = $labelData['label_type'] ?? 'pdf';
            
            if (!$labelUrl) {
                Log::error('No label URL in EcoFreight response', [
                    'shipment_id' => $shipment->id,
                    'label_data' => $labelData,
                ]);
                return null;
            }

            // Download label file
            $client = new \GuzzleHttp\Client();
            $response = $client->get($labelUrl);
            $labelContent = $response->getBody()->getContents();

            // Generate filename
            $filename = "label_{$shipment->ecofreight_awb}.{$labelType}";
            $filePath = "labels/{$shipment->shop_id}/{$filename}";

            // Store file
            Storage::disk('local')->put($filePath, $labelContent);

            // Get file URL
            $fileUrl = Storage::disk('local')->url($filePath);

            return [
                'file_path' => $filePath,
                'url' => $fileUrl,
                'type' => $labelType,
                'size' => strlen($labelContent),
                'original_url' => $labelUrl,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to download and store label', [
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create fulfillment in Shopify.
     */
    protected function createShopifyFulfillment(Shop $shop, Shipment $shipment): array
    {
        try {
            $client = new \GuzzleHttp\Client();
            
            // Get order line items
            $orderResponse = $client->get("https://{$shop->shopify_domain}/admin/api/2023-10/orders/{$shipment->shopify_order_id}.json", [
                'headers' => [
                    'X-Shopify-Access-Token' => $shop->shopify_token,
                ],
            ]);

            $orderData = json_decode($orderResponse->getBody()->getContents(), true);
            $lineItems = $orderData['order']['line_items'] ?? [];

            // Create fulfillment
            $fulfillmentData = [
                'fulfillment' => [
                    'location_id' => $shop->primary_location_id,
                    'tracking_company' => 'EcoFreight',
                    'tracking_number' => $shipment->ecofreight_awb,
                    'tracking_url' => $shipment->tracking_url,
                    'status' => 'in_transit',
                    'line_items' => array_map(function ($item) {
                        return [
                            'id' => $item['id'],
                            'quantity' => $item['quantity'],
                        ];
                    }, $lineItems),
                ],
            ];

            $response = $client->post("https://{$shop->shopify_domain}/admin/api/2023-10/orders/{$shipment->shopify_order_id}/fulfillments.json", [
                'headers' => [
                    'X-Shopify-Access-Token' => $shop->shopify_token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $fulfillmentData,
            ]);

            $fulfillment = json_decode($response->getBody()->getContents(), true);

            // Attach label file to order
            if ($shipment->label_data && isset($shipment->label_data['file_path'])) {
                $this->attachLabelToOrder($shop, $shipment);
            }

            // Add order note with AWB
            $this->addOrderNote($shop, $shipment);

            Log::info('Shopify fulfillment created', [
                'request_id' => $this->requestId,
                'shop_id' => $shop->id,
                'shipment_id' => $shipment->id,
                'fulfillment_id' => $fulfillment['fulfillment']['id'] ?? null,
            ]);

            return [
                'success' => true,
                'fulfillment_id' => $fulfillment['fulfillment']['id'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to create Shopify fulfillment', [
                'request_id' => $this->requestId,
                'shop_id' => $shop->id,
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Attach label file to Shopify order.
     */
    protected function attachLabelToOrder(Shop $shop, Shipment $shipment): void
    {
        try {
            $labelPath = $shipment->label_data['file_path'];
            $labelContent = Storage::disk('local')->get($labelPath);
            $labelType = $shipment->label_data['type'] ?? 'pdf';

            $client = new \GuzzleHttp\Client();
            
            $response = $client->post("https://{$shop->shopify_domain}/admin/api/2023-10/orders/{$shipment->shopify_order_id}/attachments.json", [
                'headers' => [
                    'X-Shopify-Access-Token' => $shop->shopify_token,
                ],
                'multipart' => [
                    [
                        'name' => 'attachment[filename]',
                        'contents' => "label_{$shipment->ecofreight_awb}.{$labelType}",
                    ],
                    [
                        'name' => 'attachment[content]',
                        'contents' => base64_encode($labelContent),
                    ],
                ],
            ]);

            Log::info('Label attached to Shopify order', [
                'request_id' => $this->requestId,
                'shop_id' => $shop->id,
                'shipment_id' => $shipment->id,
                'order_id' => $shipment->shopify_order_id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to attach label to Shopify order', [
                'request_id' => $this->requestId,
                'shop_id' => $shop->id,
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Add order note with AWB information.
     */
    protected function addOrderNote(Shop $shop, Shipment $shipment): void
    {
        try {
            $client = new \GuzzleHttp\Client();
            
            $note = "EcoFreight Shipment Created\n";
            $note .= "AWB: {$shipment->ecofreight_awb}\n";
            $note .= "Service: " . ucfirst($shipment->service_type) . "\n";
            if ($shipment->cod_enabled) {
                $note .= "COD Amount: AED " . number_format($shipment->cod_amount, 2) . "\n";
            }
            if ($shipment->tracking_url) {
                $note .= "Tracking: {$shipment->tracking_url}\n";
            }
            
            $response = $client->post("https://{$shop->shopify_domain}/admin/api/2023-10/orders/{$shipment->shopify_order_id}.json", [
                'headers' => [
                    'X-Shopify-Access-Token' => $shop->shopify_token,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'order' => [
                        'id' => $shipment->shopify_order_id,
                        'note' => $note,
                    ],
                ],
            ]);

            Log::info('Order note added', [
                'request_id' => $this->requestId,
                'shop_id' => $shop->id,
                'shipment_id' => $shipment->id,
                'awb' => $shipment->ecofreight_awb,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to add order note', [
                'request_id' => $this->requestId,
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
        Log::error('GenerateLabelJob permanently failed', [
            'shop_id' => $this->shopId,
            'shipment_id' => $this->shipmentId,
            'error' => $exception->getMessage(),
        ]);

        $shipment = Shipment::find($this->shipmentId);
        if ($shipment) {
            $shipment->update([
                'status' => 'error',
                'error_message' => 'Label generation failed permanently: ' . $exception->getMessage(),
            ]);
        }
    }
}

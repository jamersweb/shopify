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
use Illuminate\Support\Facades\Http;

class TrackSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min

    protected $shipmentId;
    protected $forceSync;
    protected $requestId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $shipmentId, bool $forceSync = false, string $requestId = null)
    {
        $this->shipmentId = $shipmentId;
        $this->forceSync = $forceSync;
        $this->requestId = $requestId ?: uniqid('track_', true);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        
        try {
            $shipment = Shipment::find($this->shipmentId);
            
            if (!$shipment || !$shipment->ecofreight_awb) {
                Log::warning('TrackSyncJob: Shipment or AWB not found', [
                    'request_id' => $this->requestId,
                    'shipment_id' => $this->shipmentId,
                ]);
                return;
            }

            $shop = $shipment->shop;
            
            if (!$shop || !$shop->settings) {
                Log::error('TrackSyncJob: Shop or settings not found', [
                    'request_id' => $this->requestId,
                    'shipment_id' => $this->shipmentId,
                ]);
                return;
            }

            // Check if we should stop polling
            if (!$this->forceSync && !$this->shouldContinuePolling($shipment)) {
                Log::info('TrackSyncJob: Stopping polling for shipment', [
                    'request_id' => $this->requestId,
                    'shipment_id' => $this->shipmentId,
                    'status' => $shipment->status,
                    'created_at' => $shipment->created_at,
                ]);
                return;
            }

            Log::info('Starting tracking sync', [
                'request_id' => $this->requestId,
                'shipment_id' => $this->shipmentId,
                'awb' => $shipment->ecofreight_awb,
                'force_sync' => $this->forceSync,
            ]);

            $ecofreightService = new EcoFreightService($shop->settings);
            $result = $ecofreightService->trackShipment($shipment->ecofreight_awb);

            if (!$result['success']) {
                Log::warning('TrackSyncJob: Tracking failed', [
                    'request_id' => $this->requestId,
                    'shipment_id' => $this->shipmentId,
                    'awb' => $shipment->ecofreight_awb,
                    'error' => $result['message'],
                    'attempt' => $this->attempts(),
                ]);

                // Update sync attempts
                $shipment->increment('sync_attempts');

                if ($this->attempts() < $this->tries) {
                    $this->release($this->backoff[$this->attempts() - 1]);
                } else {
                    // Mark as stale if we can't sync
                    $shipment->update(['stale_flag' => true]);
                }
                return;
            }

            $trackingData = $result['data'];
            
            // Process tracking data
            $this->processTrackingData($shipment, $trackingData);

            // Update shipment with latest status
            $newStatus = $this->mapTrackingStatus($trackingData);
            $statusChanged = $newStatus !== $shipment->status;
            
            $updateData = [
                'last_status' => $trackingData['status'] ?? null,
                'last_checked_at' => now(),
                'sync_attempts' => 0, // Reset on success
                'stale_flag' => false,
            ];

            if ($statusChanged) {
                $updateData['status'] = $newStatus;
                $updateData['last_tracking_sync'] = now();

                // Handle delivered status
                if ($newStatus === 'delivered') {
                    $updateData['delivered_at'] = now();
                }

                // Handle first scan
                if (!$shipment->first_scan_at && isset($trackingData['checkpoints'])) {
                    foreach ($trackingData['checkpoints'] as $checkpoint) {
                        if (in_array(strtolower($checkpoint['status']), ['picked_up', 'in_transit', 'out_for_delivery'])) {
                            $updateData['first_scan_at'] = now();
                            break;
                        }
                    }
                }
            }

            $shipment->update($updateData);

            // Update Shopify fulfillment if status changed
            if ($statusChanged) {
                $this->updateShopifyFulfillment($shop, $shipment, $trackingData);
            }

            $latency = round((microtime(true) - $startTime) * 1000);

            Log::info('TrackSyncJob completed', [
                'request_id' => $this->requestId,
                'shipment_id' => $this->shipmentId,
                'awb' => $shipment->ecofreight_awb,
                'old_status' => $shipment->getOriginal('status'),
                'new_status' => $newStatus,
                'status_changed' => $statusChanged,
                'latency_ms' => $latency,
            ]);

            // Schedule next sync if needed
            if (!$this->forceSync && $this->shouldContinuePolling($shipment)) {
                $this->scheduleNextSync($shipment);
            }

        } catch (\Exception $e) {
            $latency = round((microtime(true) - $startTime) * 1000);
            
            Log::error('TrackSyncJob failed', [
                'request_id' => $this->requestId,
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
                        'error_message' => 'Tracking sync failed: ' . $e->getMessage(),
                        'stale_flag' => true,
                    ]);
                }
            }
        }
    }

    /**
     * Check if we should continue polling for this shipment.
     */
    protected function shouldContinuePolling(Shipment $shipment): bool
    {
        // Stop if shipment is in terminal state
        if ($shipment->isTerminal()) {
            return false;
        }

        // Stop if too old
        $maxAge = $shipment->shop->settings->stop_after_days;
        if ($shipment->created_at->addDays($maxAge)->isPast()) {
            return false;
        }

        // Stop if we've been polling for too long without updates
        if ($shipment->last_tracking_sync) {
            $maxPollingTime = $shipment->created_at->addDays(10);
            if ($maxPollingTime->isPast()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Process tracking data and store checkpoints.
     */
    protected function processTrackingData(Shipment $shipment, array $trackingData): void
    {
        if (isset($trackingData['checkpoints']) && is_array($trackingData['checkpoints'])) {
            foreach ($trackingData['checkpoints'] as $checkpoint) {
                // Check if this checkpoint already exists
                $exists = $shipment->trackingLogs()
                    ->where('status', $checkpoint['status'])
                    ->where('timestamp', $checkpoint['timestamp'])
                    ->exists();

                if (!$exists) {
                    $shipment->trackingLogs()->create([
                        'status' => $checkpoint['status'],
                        'description' => $checkpoint['description'] ?? null,
                        'location' => $checkpoint['location'] ?? null,
                        'city' => $checkpoint['city'] ?? null,
                        'state' => $checkpoint['state'] ?? null,
                        'country' => $checkpoint['country'] ?? null,
                        'timestamp' => $checkpoint['timestamp'] ?? now(),
                        'raw_data' => $checkpoint,
                    ]);
                }
            }
        }
    }

    /**
     * Map EcoFreight tracking status to shipment status.
     */
    protected function mapTrackingStatus(array $trackingData): string
    {
        $status = strtolower($trackingData['status'] ?? 'pending');
        
        // Authoritative status mapping from Milestone 4 spec
        $mapping = [
            'created' => 'created',
            'label_generated' => 'label_generated',
            'picked_up' => 'shipped',
            'in_transit' => 'shipped',
            'out_for_delivery' => 'shipped',
            'delivered' => 'delivered',
            'exception' => 'error',
            'failed' => 'error',
            'cancelled' => 'cancelled',
            'returned' => 'cancelled',
        ];
        
        return $mapping[$status] ?? 'pending';
    }

    /**
     * Update Shopify fulfillment with new tracking information.
     */
    protected function updateShopifyFulfillment(Shop $shop, Shipment $shipment, array $trackingData): void
    {
        try {
            $client = new \GuzzleHttp\Client();
            
            // Get fulfillments for the order
            $response = $client->get("https://{$shop->shopify_domain}/admin/api/2023-10/orders/{$shipment->shopify_order_id}/fulfillments.json", [
                'headers' => [
                    'X-Shopify-Access-Token' => $shop->shopify_token,
                ],
            ]);

            $fulfillments = json_decode($response->getBody()->getContents(), true);
            $fulfillments = $fulfillments['fulfillments'] ?? [];

            // Find fulfillment with matching tracking number
            $targetFulfillment = null;
            foreach ($fulfillments as $fulfillment) {
                if ($fulfillment['tracking_number'] === $shipment->ecofreight_awb) {
                    $targetFulfillment = $fulfillment;
                    break;
                }
            }

            if (!$targetFulfillment) {
                Log::warning('TrackSyncJob: No matching fulfillment found', [
                    'shipment_id' => $shipment->id,
                    'awb' => $shipment->ecofreight_awb,
                ]);
                return;
            }

            // Update fulfillment status
            $newFulfillmentStatus = $this->mapFulfillmentStatus($shipment->status);
            
            if ($newFulfillmentStatus !== $targetFulfillment['status']) {
                $updateData = [
                    'fulfillment' => [
                        'status' => $newFulfillmentStatus,
                    ],
                ];

                $client->put("https://{$shop->shopify_domain}/admin/api/2023-10/fulfillments/{$targetFulfillment['id']}.json", [
                    'headers' => [
                        'X-Shopify-Access-Token' => $shop->shopify_token,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $updateData,
                ]);

                Log::info('TrackSyncJob: Fulfillment status updated', [
                    'shipment_id' => $shipment->id,
                    'fulfillment_id' => $targetFulfillment['id'],
                    'new_status' => $newFulfillmentStatus,
                ]);
            }

            // Add tracking update to fulfillment timeline
            if (isset($trackingData['checkpoints']) && is_array($trackingData['checkpoints'])) {
                foreach ($trackingData['checkpoints'] as $checkpoint) {
                    $this->addTrackingUpdate($shop, $targetFulfillment['id'], $checkpoint);
                }
            }

        } catch (\Exception $e) {
            Log::error('TrackSyncJob: Failed to update Shopify fulfillment', [
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Map shipment status to Shopify fulfillment status.
     */
    protected function mapFulfillmentStatus(string $shipmentStatus): string
    {
        $mapping = [
            'pending' => 'pending',
            'created' => 'pending',
            'label_generated' => 'pending',
            'shipped' => 'success',
            'delivered' => 'success',
            'cancelled' => 'cancelled',
            'error' => 'failure',
        ];

        return $mapping[$shipmentStatus] ?? 'pending';
    }

    /**
     * Add tracking update to fulfillment timeline.
     */
    protected function addTrackingUpdate(Shop $shop, int $fulfillmentId, array $checkpoint): void
    {
        try {
            $client = new \GuzzleHttp\Client();
            
            $updateData = [
                'fulfillment_event' => [
                    'status' => $checkpoint['status'] ?? 'in_transit',
                    'message' => $checkpoint['description'] ?? 'Tracking update',
                    'happened_at' => $checkpoint['timestamp'] ?? now()->toISOString(),
                    'city' => $checkpoint['location'] ?? null,
                    'province' => $checkpoint['state'] ?? null,
                    'country' => $checkpoint['country'] ?? null,
                ],
            ];

            $client->post("https://{$shop->shopify_domain}/admin/api/2023-10/fulfillments/{$fulfillmentId}/events.json", [
                'headers' => [
                    'X-Shopify-Access-Token' => $shop->shopify_token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $updateData,
            ]);

        } catch (\Exception $e) {
            Log::error('TrackSyncJob: Failed to add tracking update', [
                'fulfillment_id' => $fulfillmentId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Schedule next tracking sync.
     */
    protected function scheduleNextSync(Shipment $shipment): void
    {
        $interval = $shipment->shop->settings->poll_interval_hours;
        $nextSync = now()->addHours($interval);
        
        TrackSyncJob::dispatch($this->shipmentId)->delay($nextSync);
        
        Log::info('TrackSyncJob: Next sync scheduled', [
            'shipment_id' => $this->shipmentId,
            'next_sync' => $nextSync,
        ]);
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('TrackSyncJob permanently failed', [
            'shipment_id' => $this->shipmentId,
            'error' => $exception->getMessage(),
        ]);

        $shipment = Shipment::find($this->shipmentId);
        if ($shipment) {
            $shipment->update([
                'status' => 'error',
                'error_message' => 'Tracking sync failed permanently: ' . $exception->getMessage(),
            ]);
        }
    }
}

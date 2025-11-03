<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Shipment;
use App\Jobs\CreateShipmentJob;
use App\Jobs\GenerateLabelJob;
use App\Jobs\TrackSyncJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShipmentController extends Controller
{
    /**
     * Display a listing of shipments.
     */
    public function index(Request $request)
    {
        $shop = session('shop') ?: $request->query('shop');
        
        if (!$shop) {
            return response()->json(['error' => 'Shop parameter is required'], 400);
        }

        $shopRecord = Shop::where('shopify_domain', $shop)->first();
        
        if (!$shopRecord) {
            return redirect("/auth/install?shop={$shop}");
        }

        $query = $shopRecord->shipments()->with('shop');

        // Filter by status if provided
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by date range if provided
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by order name or AWB if provided
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('shopify_order_name', 'like', "%{$search}%")
                  ->orWhere('ecofreight_awb', 'like', "%{$search}%");
            });
        }

        $shipments = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('app.shipments', compact('shop', 'shopRecord', 'shipments'));
    }

    /**
     * Show the specified shipment.
     */
    public function show(Request $request, $id)
    {
        $shop = session('shop') ?: $request->query('shop');
        
        if (!$shop) {
            return response()->json(['error' => 'Shop parameter is required'], 400);
        }

        $shopRecord = Shop::where('shopify_domain', $shop)->first();
        
        if (!$shopRecord) {
            return redirect("/auth/install?shop={$shop}");
        }

        $shipment = $shopRecord->shipments()->findOrFail($id);

        return view('app.shipment-detail', compact('shop', 'shopRecord', 'shipment'));
    }

    /**
     * Retry a failed shipment.
     */
    public function retry(Request $request, $id)
    {
        $shop = session('shop') ?: $request->query('shop');
        
        if (!$shop) {
            return response()->json(['error' => 'Shop parameter is required'], 400);
        }

        $shopRecord = Shop::where('shopify_domain', $shop)->first();
        
        if (!$shopRecord) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        $shipment = $shopRecord->shipments()->findOrFail($id);

        if (!$shipment->canRetry()) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment cannot be retried',
            ], 400);
        }

        // Reset shipment status
        $shipment->update([
            'status' => 'pending',
            'error_message' => null,
            'retry_count' => 0,
            'next_retry_at' => null,
        ]);

        // Queue the job again
        CreateShipmentJob::dispatch($shopRecord->id, $shipment->id);

        Log::info('Shipment retry initiated', [
            'shop_id' => $shopRecord->id,
            'shipment_id' => $shipment->id,
            'order_name' => $shipment->shopify_order_name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shipment retry initiated',
        ]);
    }

    /**
     * Regenerate label for a shipment.
     */
    public function regenerateLabel(Request $request, $id)
    {
        $shop = session('shop') ?: $request->query('shop');
        
        if (!$shop) {
            return response()->json(['error' => 'Shop parameter is required'], 400);
        }

        $shopRecord = Shop::where('shopify_domain', $shop)->first();
        
        if (!$shopRecord) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        $shipment = $shopRecord->shipments()->findOrFail($id);

        if (!$shipment->ecofreight_awb) {
            return response()->json([
                'success' => false,
                'message' => 'No AWB found for this shipment',
            ], 400);
        }

        // Queue label generation job
        GenerateLabelJob::dispatch($shopRecord->id, $shipment->id);

        Log::info('Label regeneration initiated', [
            'shop_id' => $shopRecord->id,
            'shipment_id' => $shipment->id,
            'awb' => $shipment->ecofreight_awb,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Label regeneration initiated',
        ]);
    }

    /**
     * Manually sync tracking for a shipment.
     */
    public function syncTracking(Request $request, $id)
    {
        $shop = session('shop') ?: $request->query('shop');
        
        if (!$shop) {
            return response()->json(['error' => 'Shop parameter is required'], 400);
        }

        $shopRecord = Shop::where('shopify_domain', $shop)->first();
        
        if (!$shopRecord) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        $shipment = $shopRecord->shipments()->findOrFail($id);

        if (!$shipment->ecofreight_awb) {
            return response()->json([
                'success' => false,
                'message' => 'No AWB found for this shipment',
            ], 400);
        }

        // Queue tracking sync job
        TrackSyncJob::dispatch($shipment->id, true);

        Log::info('Manual tracking sync initiated', [
            'shop_id' => $shopRecord->id,
            'shipment_id' => $shipment->id,
            'awb' => $shipment->ecofreight_awb,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tracking sync initiated',
        ]);
    }

    /**
     * Cancel a shipment.
     */
    public function cancel(Request $request, $id)
    {
        $shop = session('shop') ?: $request->query('shop');
        
        if (!$shop) {
            return response()->json(['error' => 'Shop parameter is required'], 400);
        }

        $shopRecord = Shop::where('shopify_domain', $shop)->first();
        
        if (!$shopRecord) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        $shipment = $shopRecord->shipments()->findOrFail($id);

        if ($shipment->isTerminal()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel shipment in terminal state',
            ], 400);
        }

        if (!$shipment->ecofreight_awb) {
            // Just mark as cancelled if no AWB yet
            $shipment->update([
                'status' => 'cancelled',
                'error_message' => 'Cancelled by user',
            ]);
        } else {
            // Cancel in EcoFreight
            $ecofreightService = new \App\Services\EcoFreightService($shopRecord->settings);
            $result = $ecofreightService->cancelShipment($shipment->ecofreight_awb);

            if ($result['success']) {
                $shipment->update([
                    'status' => 'cancelled',
                    'error_message' => 'Cancelled by user',
                ]);

                // Update Shopify fulfillment
                $this->cancelShopifyFulfillment($shopRecord, $shipment);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to cancel shipment: ' . $result['message'],
                ], 400);
            }
        }

        Log::info('Shipment cancelled', [
            'shop_id' => $shopRecord->id,
            'shipment_id' => $shipment->id,
            'awb' => $shipment->ecofreight_awb,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shipment cancelled successfully',
        ]);
    }

    /**
     * Get shipment statistics.
     */
    public function stats(Request $request)
    {
        $shop = session('shop') ?: $request->query('shop');
        
        if (!$shop) {
            return response()->json(['error' => 'Shop parameter is required'], 400);
        }

        $shopRecord = Shop::where('shopify_domain', $shop)->first();
        
        if (!$shopRecord) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        $stats = [
            'total' => $shopRecord->shipments()->count(),
            'pending' => $shopRecord->shipments()->where('status', 'pending')->count(),
            'created' => $shopRecord->shipments()->where('status', 'created')->count(),
            'label_generated' => $shopRecord->shipments()->where('status', 'label_generated')->count(),
            'shipped' => $shopRecord->shipments()->where('status', 'shipped')->count(),
            'delivered' => $shopRecord->shipments()->where('status', 'delivered')->count(),
            'cancelled' => $shopRecord->shipments()->where('status', 'cancelled')->count(),
            'error' => $shopRecord->shipments()->where('status', 'error')->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Cancel Shopify fulfillment.
     */
    protected function cancelShopifyFulfillment(Shop $shop, Shipment $shipment): void
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
            foreach ($fulfillments as $fulfillment) {
                if ($fulfillment['tracking_number'] === $shipment->ecofreight_awb) {
                    // Cancel the fulfillment
                    $client->post("https://{$shop->shopify_domain}/admin/api/2023-10/fulfillments/{$fulfillment['id']}/cancel.json", [
                        'headers' => [
                            'X-Shopify-Access-Token' => $shop->shopify_token,
                            'Content-Type' => 'application/json',
                        ],
                    ]);

                    Log::info('Shopify fulfillment cancelled', [
                        'shop_id' => $shop->id,
                        'shipment_id' => $shipment->id,
                        'fulfillment_id' => $fulfillment['id'],
                    ]);
                    break;
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to cancel Shopify fulfillment', [
                'shop_id' => $shop->id,
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

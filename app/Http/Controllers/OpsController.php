<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Shipment;
use App\Models\TrackingLog;
use App\Jobs\TrackSyncJob;
use App\Jobs\CreateShipmentJob;
use App\Jobs\GenerateLabelJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OpsController extends Controller
{
    /**
     * Show the ops dashboard.
     */
    public function dashboard(Request $request)
    {
        $shop = session('shop') ?: $request->query('shop');
        
        if (!$shop) {
            return response()->json(['error' => 'misplaced parameter is required'], 400);
        }

        $shopRecord = Shop::where('shopify_domain', $shop)->first();
        
        if (!$shopRecord) {
            return redirect("/auth/install?shop={$shop}");
        }

        // Get health metrics
        $metrics = $this->getHealthMetrics($shopRecord);
        
        // Get recent shipments
        $recentShipments = $shopRecord->shipments()
            ->with('trackingLogs')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('app.ops-dashboard', compact('shop', 'shopRecord', 'metrics', 'recentShipments'));
    }

    /**
     * Search shipments.
     */
    public function search(Request $request)
    {
        $shop = session('shop') ?: $request->query('shop');
        
        if (!$shop) {
            return response()->json(['error' => 'Shop parameter is required'], 400);
        }

        $shopRecord = Shop::where('shopify_domain', $shop)->first();
        
        if (!$shopRecord) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        $query = $shopRecord->shipments()->with('trackingLogs');

        // Apply filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('shopify_order_name', 'like', "%{$search}%")
                  ->orWhere('ecofreight_awb', 'like', "%{$search}%")
                  ->orWhere('shopify_order_id', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->has('stale_only') && $request->boolean('stale_only')) {
            $query->stale();
        }

        // Apply sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $shipments = $query->paginate(20);

        return response()->json([
            'shipments' => $shipments->items(),
            'pagination' => [
                'current_page' => $shipments->currentPage(),
                'last_page' => $shipments->lastPage(),
                'per_page' => $shipments->perPage(),
                'total' => $shipments->total(),
            ],
        ]);
    }

    /**
     * Get shipment details.
     */
    public function details(Request $request, $id)
    {
        $shop = session('shop') ?: $request->query('shop');
        
        if (!$shop) {
            return response()->json(['error' => 'Shop parameter is required'], 400);
        }

        $shopRecord = Shop::where('shopify_domain', $shop)->first();
        
        if (!$shopRecord) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        $shipment = $shopRecord->shipments()
            ->with('trackingLogs')
            ->findOrFail($id);

        return response()->json([
            'shipment' => $shipment,
            'tracking_logs' => $shipment->trackingLogs()->orderBy('timestamp', 'desc')->get(),
        ]);
    }

    /**
     * Sync tracking for a shipment.
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

        // Queue immediate tracking sync
        TrackSyncJob::dispatch($shipment->id, true);

        Log::info('Manual tracking sync initiated from ops dashboard', [
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
     * Void a shipment.
     */
    public function voidShipment(Request $request, $id)
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

        if (!$shipment->canVoid()) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment cannot be voided',
            ], 400);
        }

        // Void in EcoFreight
        $ecofreightService = new \App\Services\EcoFreightService($shopRecord->settings);
        $result = $ecofreightService->cancelShipment($shipment->ecofreight_awb);

        if ($result['success']) {
            $shipment->update([
                'status' => 'cancelled',
                'error_message' => 'Voided from ops dashboard',
            ]);

            // Update Shopify fulfillment
            $this->cancelShopifyFulfillment($shopRecord, $shipment);

            Log::info('Shipment voided from ops dashboard', [
                'shop_id' => $shopRecord->id,
                'shipment_id' => $shipment->id,
                'awb' => $shipment->ecofreight_awb,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Shipment voided successfully',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to void shipment: ' . $result['message'],
            ], 400);
        }
    }

    /**
     * Re-ship a shipment.
     */
    public function reship(Request $request, $id)
    {
        $shop = session('shop') ?: $request->query('shop');
        
        if (!$shop) {
            return response()->json(['error' => 'Shop parameter is required'], 400);
        }

        $shopRecord = Shop::where('shopify_domain', $shop)->first();
        
        if (!$shopRecord) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        $originalShipment = $shopRecord->shipments()->findOrFail($id);

        if (!$originalShipment->canReship()) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment cannot be re-shipped',
            ], 400);
        }

        // Create new shipment record
        $newShipment = Shipment::create([
            'shop_id' => $originalShipment->shop_id,
            'shopify_order_id' => $originalShipment->shopify_order_id,
            'shopify_order_name' => $originalShipment->shopify_order_name,
            'service_type' => $originalShipment->service_type,
            'status' => 'pending',
            'cod_enabled' => $originalShipment->cod_enabled,
            'cod_amount' => $originalShipment->cod_amount,
        ]);

        // Queue new shipment creation
        CreateShipmentJob::dispatch($shopRecord->id, $newShipment->id);

        // Cancel original shipment if possible
        if ($originalShipment->canVoid()) {
            $this->voidShipment($request, $originalShipment->id);
        }

        Log::info('Shipment re-ship initiated from ops dashboard', [
            'shop_id' => $shopRecord->id,
            'original_shipment_id' => $originalShipment->id,
            'new_shipment_id' => $newShipment->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Re-ship initiated successfully',
            'new_shipment_id' => $newShipment->id,
        ]);
    }

    /**
     * Get health metrics.
     */
    protected function getHealthMetrics(Shop $shop)
    {
        $metrics = [];

        // Active shipments (polling)
        $metrics['active_shipments'] = $shop->shipments()->active()->count();

        // Delivered last 24h
        $metrics['delivered_last_24h'] = $shop->shipments()
            ->delivered()
            ->where('delivered_at', '>=', now()->subDay())
            ->count();

        // Exceptions
        $metrics['exceptions'] = $shop->shipments()->exception()->count();

        // Stale >48h
        $metrics['stale_48h'] = $shop->shipments()->stale(48)->count();

        // Success rate (last 7 days)
        $totalShipments = $shop->shipments()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        
        $deliveredShipments = $shop->shipments()
            ->delivered()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $metrics['success_rate'] = $totalShipments > 0 ? round(($deliveredShipments / $totalShipments) * 100, 2) : 0;

        // Average delivery time
        $deliveredShipments = $shop->shipments()
            ->delivered()
            ->whereNotNull('delivered_at')
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        $totalDays = $deliveredShipments->sum(function ($shipment) {
            return $shipment->created_at->diffInDays($shipment->delivered_at);
        });

        $metrics['avg_delivery_days'] = $deliveredShipments->count() > 0 
            ? round($totalDays / $deliveredShipments->count(), 1) 
            : 0;

        return $metrics;
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

                    Log::info('Shopify fulfillment cancelled from ops dashboard', [
                        'shop_id' => $shop->id,
                        'shipment_id' => $shipment->id,
                        'fulfillment_id' => $fulfillment['id'],
                    ]);
                    break;
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to cancel Shopify fulfillment from ops dashboard', [
                'shop_id' => $shop->id,
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

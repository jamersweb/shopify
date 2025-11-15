<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client;

class DashboardController extends Controller
{
    /**
     * Show dashboard
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get user's shops
        $shops = $user->shops()->with('settings')->get();
        
        // Get recent shipments
        $recentShipments = Shipment::with('shop')
            ->whereIn('shop_id', $shops->pluck('id'))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Get shipment statistics
        $stats = [
            'total_shipments' => Shipment::whereIn('shop_id', $shops->pluck('id'))->count(),
            'pending_shipments' => Shipment::whereIn('shop_id', $shops->pluck('id'))->where('status', 'pending')->count(),
            'delivered_shipments' => Shipment::whereIn('shop_id', $shops->pluck('id'))->where('status', 'delivered')->count(),
            'error_shipments' => Shipment::whereIn('shop_id', $shops->pluck('id'))->where('status', 'error')->count(),
        ];
        
        return view('dashboard.index', compact('shops', 'recentShipments', 'stats'));
    }

    /**
     * Show orders page
     */
    public function orders(Request $request)
    {
        $user = Auth::user();
        $shops = $user->shops()->with('settings')->get();
        
        $query = Shipment::with('shop')
            ->whereIn('shop_id', $shops->pluck('id'));
        
        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }
        
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('shopify_order_name', 'like', '%' . $request->search . '%')
                  ->orWhere('ecofreight_awb', 'like', '%' . $request->search . '%');
            });
        }
        
        $shipments = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return view('dashboard.orders', compact('shipments', 'shops'));
    }

    /**
     * Fetch orders from Shopify
     */
    public function fetchOrders(Request $request)
    {
        $user = Auth::user();
        $shopId = $request->input('shop_id');
        
        $shop = $user->shops()->findOrFail($shopId);
        
        if (!$shop->settings) {
            return response()->json([
                'success' => false,
                'message' => 'Shop settings not configured'
            ]);
        }
        
        try {
            $client = new Client();
            
            // Fetch recent orders from Shopify
            $response = $client->get("https://{$shop->shopify_domain}/admin/api/2024-01/orders.json", [
                'headers' => [
                    'X-Shopify-Access-Token' => $shop->shopify_token,
                    'Content-Type' => 'application/json',
                ],
                'query' => [
                    'status' => 'any',
                    'limit' => 50,
                    'created_at_min' => now()->subDays(30)->toISOString(),
                ]
            ]);
            
            $data = json_decode($response->getBody(), true);
            $orders = $data['orders'] ?? [];
            
            // Process orders and create shipments if needed
            $processedCount = 0;
            foreach ($orders as $order) {
                $existingShipment = Shipment::where('shopify_order_id', $order['id'])
                    ->where('shop_id', $shop->id)
                    ->first();
                
                if (!$existingShipment && $order['financial_status'] === 'paid') {
                    // Create shipment record
                    $shipment = Shipment::create([
                        'shop_id' => $shop->id,
                        'shopify_order_id' => $order['id'],
                        'shopify_order_name' => $order['name'],
                        'status' => 'pending',
                        'shipment_data' => $order,
                        'service_type' => $this->mapServiceType($order['shipping_lines'][0]['title'] ?? 'standard'),
                        'cod_enabled' => $shop->settings->cod_enabled ?? false,
                        'cod_amount' => $this->calculateCodAmount($order, $shop->settings),
                    ]);
                    
                    // Automatically queue shipment creation job
                    // Note: Queue worker must be running for jobs to process
                    // Run: php artisan queue:work
                    \App\Jobs\CreateShipmentJob::dispatch($shop->id, $shipment->id, uniqid('fetch_', true));
                    
                    $processedCount++;
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Fetched {$processedCount} new orders",
                'orders_count' => count($orders),
                'processed_count' => $processedCount
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Show shipment details
     */
    public function shipmentDetails($id)
    {
        $user = Auth::user();
        $shops = $user->shops()->pluck('id');
        
        $shipment = Shipment::with(['shop', 'trackingLogs'])
            ->whereIn('shop_id', $shops)
            ->findOrFail($id);
        
        // If shipment_data doesn't have order data, fetch it from Shopify
        $orderData = $shipment->shipment_data;
        if (!$orderData || !isset($orderData['customer']) || !isset($orderData['line_items'])) {
            try {
                $client = new Client();
                $response = $client->get("https://{$shipment->shop->shopify_domain}/admin/api/2024-01/orders/{$shipment->shopify_order_id}.json", [
                    'headers' => [
                        'X-Shopify-Access-Token' => $shipment->shop->shopify_token,
                    ],
                ]);
                
                $data = json_decode($response->getBody(), true);
                $orderData = $data['order'] ?? $orderData;
                
                // Update shipment_data with full order data
                if ($orderData) {
                    $shipment->update(['shipment_data' => $orderData]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to fetch order data for shipment details', [
                    'shipment_id' => $shipment->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return view('dashboard.shipment-details', compact('shipment', 'orderData'));
    }

    /**
     * Retry creating a shipment
     */
    public function retryShipment(Request $request, $id)
    {
        $user = Auth::user();
        $shops = $user->shops()->pluck('id');
        
        $shipment = Shipment::whereIn('shop_id', $shops)->findOrFail($id);
        
        // Reset shipment status and clear error
        $shipment->update([
            'status' => 'pending',
            'error_message' => null,
            'retry_count' => ($shipment->retry_count ?? 0) + 1
        ]);
        
        // Queue the shipment creation job with correct parameters (shopId, shipmentId, requestId)
        \App\Jobs\CreateShipmentJob::dispatch($shipment->shop_id, $shipment->id, uniqid('retry_', true));
        
        return response()->json([
            'success' => true,
            'message' => 'Shipment retry initiated'
        ]);
    }

    /**
     * Send shipment to EcoFreight
     */
    public function sendShipment(Request $request, $id)
    {
        $user = Auth::user();
        $shops = $user->shops()->pluck('id');
        
        $shipment = Shipment::whereIn('shop_id', $shops)->findOrFail($id);
        
        // Check if shipment is already sent
        if ($shipment->ecofreight_awb) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment already sent to EcoFreight. AWB: ' . $shipment->ecofreight_awb
            ]);
        }
        
        // Check if shop settings are configured
        if (!$shipment->shop->settings) {
            return response()->json([
                'success' => false,
                'message' => 'Shop settings not configured. Please configure EcoFreight credentials first.'
            ]);
        }
        
        // Update status to pending if it's not already
        if ($shipment->status !== 'pending') {
            $shipment->update([
                'status' => 'pending',
                'error_message' => null
            ]);
        }
        
        // Queue the shipment creation job
        \App\Jobs\CreateShipmentJob::dispatch($shipment->shop_id, $shipment->id, uniqid('send_', true));
        
        return response()->json([
            'success' => true,
            'message' => 'Shipment queued for EcoFreight. Processing...'
        ]);
    }

    /**
     * Map Shopify shipping rate title to service type
     */
    protected function mapServiceType(string $rateTitle): string
    {
        $rateTitle = strtolower($rateTitle);
        return str_contains($rateTitle, 'express') ? 'express' : 'standard';
    }

    /**
     * Calculate COD amount from order data
     */
    protected function calculateCodAmount(array $orderData, $settings): float
    {
        if (!$settings->cod_enabled) {
            return 0;
        }
        
        $orderTotal = floatval($orderData['total_price'] ?? 0);
        $codFee = floatval($settings->cod_fee ?? 0);
        
        return $orderTotal + $codFee;
    }
}

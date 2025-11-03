<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppController extends Controller
{
    /**
     * Show the main app interface.
     */
    public function index(Request $request)
    {
        $shop = $request->query('shop');
        
        if (!$shop) {
            return response()->json(['error' => 'Shop parameter is required'], 400);
        }

        $shopRecord = Shop::where('shopify_domain', $shop)->first();
        
        if (!$shopRecord) {
            return redirect("/auth/install?shop={$shop}");
        }

        // Set shop in session for embedded app
        session(['shop' => $shop]);

        return view('app.index', compact('shop', 'shopRecord'));
    }

    /**
     * Show the settings page.
     */
    public function settings(Request $request)
    {
        $shop = session('shop') ?: $request->query('shop');
        
        if (!$shop) {
            return response()->json(['error' => 'Shop parameter is required'], 400);
        }

        $shopRecord = Shop::where('shopify_domain', $shop)->first();
        
        if (!$shopRecord) {
            return redirect("/auth/install?shop={$shop}");
        }

        $settings = $shopRecord->settings;

        return view('app.settings', compact('shop', 'shopRecord', 'settings'));
    }

    /**
     * Update shop settings.
     */
    public function updateSettings(Request $request)
    {
        $shop = session('shop') ?: $request->query('shop');
        
        if (!$shop) {
            return response()->json(['error' => 'Shop parameter is required'], 400);
        }

        $shopRecord = Shop::where('shopify_domain', $shop)->first();
        
        if (!$shopRecord) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        $request->validate([
            'ecofreight_username' => 'required|string',
            'ecofreight_password' => 'required|string',
            'ship_from_company' => 'required|string',
            'ship_from_contact' => 'required|string',
            'ship_from_phone' => 'required|string',
            'ship_from_email' => 'required|email',
            'ship_from_address1' => 'required|string',
            'ship_from_city' => 'required|string',
            'ship_from_country' => 'required|string',
            'default_weight' => 'required|numeric|min:0.1',
            'default_length' => 'required|numeric|min:1',
            'default_width' => 'required|numeric|min:1',
            'default_height' => 'required|numeric|min:1',
            'packing_rule' => 'required|in:per_order,per_item',
            'use_standard_service' => 'boolean',
            'use_express_service' => 'boolean',
            'cod_enabled' => 'boolean',
            'cod_fee' => 'nullable|numeric|min:0',
            'markup_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'tracking_url_template' => 'nullable|string',
            'auto_poll_tracking' => 'boolean',
            'poll_interval_hours' => 'required|integer|min:1|max:24',
            'stop_after_days' => 'required|integer|min:1|max:30',
            'error_alert_emails' => 'nullable|string',
            'include_awb_in_alerts' => 'boolean',
        ]);

        $settings = $shopRecord->settings;
        
        if (!$settings) {
            $settings = $shopRecord->settings()->create([]);
        }

        $settings->update([
            'ecofreight_username' => $request->ecofreight_username,
            'ecofreight_password' => $request->ecofreight_password,
            'ship_from_company' => $request->ship_from_company,
            'ship_from_contact' => $request->ship_from_contact,
            'ship_from_phone' => $request->ship_from_phone,
            'ship_from_email' => $request->ship_from_email,
            'ship_from_address1' => $request->ship_from_address1,
            'ship_from_address2' => $request->ship_from_address2,
            'ship_from_city' => $request->ship_from_city,
            'ship_from_postcode' => $request->ship_from_postcode,
            'ship_from_country' => $request->ship_from_country,
            'default_weight' => $request->default_weight,
            'default_length' => $request->default_length,
            'default_width' => $request->default_width,
            'default_height' => $request->default_height,
            'packing_rule' => $request->packing_rule,
            'use_standard_service' => $request->boolean('use_standard_service'),
            'use_express_service' => $request->boolean('use_express_service'),
            'cod_enabled' => $request->boolean('cod_enabled'),
            'cod_fee' => $request->cod_fee,
            'markup_percentage' => $request->markup_percentage,
            'discount_percentage' => $request->discount_percentage,
            'tracking_url_template' => $request->tracking_url_template,
            'auto_poll_tracking' => $request->boolean('auto_poll_tracking'),
            'poll_interval_hours' => $request->poll_interval_hours,
            'stop_after_days' => $request->stop_after_days,
            'error_alert_emails' => $request->error_alert_emails,
            'include_awb_in_alerts' => $request->boolean('include_awb_in_alerts'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
        ]);
    }

    /**
     * Test EcoFreight connection.
     */
    public function testConnection(Request $request)
    {
        $shop = session('shop') ?: $request->query('shop');
        
        if (!$shop) {
            return response()->json(['error' => 'Shop parameter is required'], 400);
        }

        $shopRecord = Shop::where('shopify_domain', $shop)->first();
        
        if (!$shopRecord || !$shopRecord->settings) {
            return response()->json(['error' => 'Shop settings not found'], 404);
        }

        $ecofreightService = new \App\Services\EcoFreightService($shopRecord->settings);
        $result = $ecofreightService->testConnection();

        // Update connection status and timestamp
        $shopRecord->settings->update([
            'connection_status' => $result['success'],
            'last_connection_test' => now(),
        ]);

        return response()->json($result);
    }

    /**
     * Show shipments page.
     */
    public function shipments(Request $request)
    {
        $shop = session('shop') ?: $request->query('shop');
        
        if (!$shop) {
            return response()->json(['error' => 'Shop parameter is required'], 400);
        }

        $shopRecord = Shop::where('shopify_domain', $shop)->first();
        
        if (!$shopRecord) {
            return redirect("/auth/install?shop={$shop}");
        }

        $shipments = $shopRecord->shipments()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('app.shipments', compact('shop', 'shopRecord', 'shipments'));
    }

    /**
     * Show dashboard.
     */
    public function dashboard(Request $request)
    {
        $shop = session('shop') ?: $request->query('shop');
        
        if (!$shop) {
            return response()->json(['error' => 'Shop parameter is required'], 400);
        }

        $shopRecord = Shop::where('shopify_domain', $shop)->first();
        
        if (!$shopRecord) {
            return redirect("/auth/install?shop={$shop}");
        }

        $stats = [
            'total_shipments' => $shopRecord->shipments()->count(),
            'pending_shipments' => $shopRecord->shipments()->where('status', 'pending')->count(),
            'delivered_shipments' => $shopRecord->shipments()->where('status', 'delivered')->count(),
            'error_shipments' => $shopRecord->shipments()->where('status', 'error')->count(),
        ];

        $recentShipments = $shopRecord->shipments()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('app.dashboard', compact('shop', 'shopRecord', 'stats', 'recentShipments'));
    }
}

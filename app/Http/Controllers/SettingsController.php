<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\ShopSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * Show settings page
     */
    public function index()
    {
        $user = Auth::user();
        $shops = $user->shops()->with('settings')->get();
        
        return view('settings.index', compact('shops'));
    }

    /**
     * Show shop settings form
     */
    public function shopSettings($shopId)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($shopId);
        
        // Ensure settings always exists (even if empty)
        $settings = $shop->settings ?? new \App\Models\ShopSetting();
        
        return view('settings.shop', compact('shop', 'settings'));
    }

    /**
     * Update shop settings
     */
    public function updateShopSettings(Request $request, $shopId)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($shopId);
        
        $validator = Validator::make($request->all(), [
            'ecofreight_username' => 'required|string',
            'ecofreight_password' => 'required|string',
            'ecofreight_base_url' => 'required|url',
            'company_name' => 'required|string',
            'contact_name' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email',
            'address1' => 'required|string',
            'city' => 'required|string',
            'postcode' => 'required|string',
            'country' => 'required|string',
            'default_weight' => 'required|numeric|min:0.1',
            'default_length' => 'required|numeric|min:1',
            'default_width' => 'required|numeric|min:1',
            'default_height' => 'required|numeric|min:1',
            'packing_rule' => 'required|in:per_order,per_item',
            'express_enabled' => 'boolean',
            'standard_enabled' => 'boolean',
            'cod_enabled' => 'boolean',
            'cod_fee' => 'nullable|numeric|min:0',
            'tracking_poll_interval' => 'required|integer|min:1|max:24',
            'alert_emails' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $settingsData = $request->only([
            'ecofreight_username',
            'ecofreight_password',
            'ecofreight_base_url',
            'company_name',
            'contact_name',
            'phone',
            'email',
            'address1',
            'address2',
            'city',
            'postcode',
            'country',
            'default_weight',
            'default_length',
            'default_width',
            'default_height',
            'packing_rule',
            'express_enabled',
            'standard_enabled',
            'cod_enabled',
            'cod_fee',
            'tracking_poll_interval',
            'alert_emails',
        ]);

        // Encrypt sensitive data
        $settingsData['ecofreight_password'] = encrypt($settingsData['ecofreight_password']);

        if ($shop->settings) {
            $shop->settings->update($settingsData);
        } else {
            $shop->settings()->create($settingsData);
        }

        return redirect()->back()->with('success', 'Settings updated successfully!');
    }

    /**
     * Test EcoFreight connection
     */
    public function testConnection(Request $request, $shopId)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($shopId);
        
        if (!$shop->settings) {
            return response()->json([
                'success' => false,
                'message' => 'Please configure settings first'
            ]);
        }

        try {
            $ecofreightService = new \App\Services\EcoFreightService($shop->settings);
            $result = $ecofreightService->testConnection();
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Disconnect shop
     */
    public function disconnectShop($shopId)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($shopId);
        
        // Delete shop settings
        if ($shop->settings) {
            $shop->settings->delete();
        }
        
        // Delete shop
        $shop->delete();
        
        return redirect()->route('settings')->with('success', 'Shop disconnected successfully!');
    }
}

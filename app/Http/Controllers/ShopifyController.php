<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ShopifyController extends Controller
{
    /**
     * Initiate Shopify OAuth
     */
    public function install(Request $request)
    {
        $shop = $request->get('shop');
        
        if (!$shop) {
            return response()->json(['error' => 'Shop parameter is required'], 400);
        }
        
        // Clean shop domain
        $shop = str_replace(['https://', 'http://'], '', $shop);
        $shop = str_replace('.myshopify.com', '', $shop);
        $shop = $shop . '.myshopify.com';
        
        // Generate state for security
        $state = Str::random(40);
        session(['shopify_oauth_state' => $state]);
        
        // Build OAuth URL
        $scopes = 'read_orders,write_orders,read_products,write_products,read_shipping,write_shipping';
        
        // Use production URL for deployed app
        $redirectUri = env('APP_URL', 'https://shopify.acusync.net') . '/app/shopify/callback';
        
        $authUrl = "https://{$shop}/admin/oauth/authorize?" . http_build_query([
            'client_id' => env('SHOPIFY_API_KEY'),
            'scope' => $scopes,
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);
        
        return redirect($authUrl);
    }
    
    /**
     * Handle Shopify OAuth callback
     */
    public function callback(Request $request)
    {
        $code = $request->get('code');
        $state = $request->get('state');
        $shop = $request->get('shop');
        
        // Verify state
        if ($state !== session('shopify_oauth_state')) {
            return redirect('/app/settings')->with('error', 'Invalid state parameter');
        }
        
        if (!$code || !$shop) {
            return redirect('/app/settings')->with('error', 'Missing authorization code or shop');
        }
        
        try {
            // Exchange code for access token
            $response = Http::post("https://{$shop}/admin/oauth/access_token", [
                'client_id' => env('SHOPIFY_API_KEY'),
                'client_secret' => env('SHOPIFY_API_SECRET'),
                'code' => $code,
            ]);
            
            if (!$response->successful()) {
                return redirect('/app/settings')->with('error', 'Failed to get access token');
            }
            
            $data = $response->json();
            $accessToken = $data['access_token'];
            
            // Get shop information
            $shopResponse = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
            ])->get("https://{$shop}/admin/api/2024-01/shop.json");
            
            if (!$shopResponse->successful()) {
                return redirect('/app/settings')->with('error', 'Failed to get shop information');
            }
            
            $shopData = $shopResponse->json()['shop'];
            
            // Create or update shop
            // Try to get user from session or create a default user
            $user = Auth::user();
            
            // If no user logged in, create or get the first admin user
            if (!$user) {
                $user = \App\Models\User::where('role', 'admin')->first();
                if (!$user) {
                    return redirect('/login')->with('error', 'Please login first to connect your Shopify store');
                }
            }
            
            $shop = Shop::updateOrCreate(
                ['shopify_domain' => $shopData['myshopify_domain']],
                [
                    'user_id' => $user->id,
                    'shopify_token' => $accessToken,
                    'access_token' => $accessToken,
                    'name' => $shopData['name'] ?? '',
                    'email' => $shopData['email'] ?? null,
                    'domain' => $shopData['domain'] ?? null,
                    'province' => $shopData['province'] ?? null,
                    'country' => $shopData['country'] ?? null,
                    'address1' => $shopData['address1'] ?? null,
                    'zip' => $shopData['zip'] ?? null,
                    'city' => $shopData['city'] ?? null,
                    'source' => $shopData['source'] ?? null,
                    'phone' => $shopData['phone'] ?? null,
                    'shopify_updated_at' => isset($shopData['updated_at']) ? $shopData['updated_at'] : null,
                    'shopify_created_at' => isset($shopData['created_at']) ? $shopData['created_at'] : null,
                    'country_code' => $shopData['country_code'] ?? null,
                    'country_name' => $shopData['country_name'] ?? null,
                    'currency' => $shopData['currency'] ?? null,
                    'customer_email' => $shopData['customer_email'] ?? null,
                    'timezone' => $shopData['timezone'] ?? null,
                    'iana_timezone' => $shopData['iana_timezone'] ?? null,
                    'shopify_plan_name' => $shopData['plan_name'] ?? null,
                    'has_discounts' => $shopData['has_discounts'] ?? false,
                    'has_gift_cards' => $shopData['has_gift_cards'] ?? false,
                    'force_ssl' => $shopData['force_ssl'] ?? false,
                    'checkout_api_supported' => $shopData['checkout_api_supported'] ?? false,
                    'multi_location_enabled' => $shopData['multi_location_enabled'] ?? false,
                    'has_storefront' => $shopData['has_storefront'] ?? false,
                    'eligible_for_payments' => $shopData['eligible_for_payments'] ?? false,
                    'eligible_for_card_reader_giveaway' => $shopData['eligible_for_card_reader_giveaway'] ?? false,
                    'finances' => $shopData['finances'] ?? false,
                    'primary_location_id' => $shopData['primary_location_id'] ?? null,
                    'cookie_consent_level' => $shopData['cookie_consent_level'] ?? null,
                    'visitor_tracking_consent_preference' => $shopData['visitor_tracking_consent_preference'] ?? null,
                ]
            );
            
            // Clear OAuth state
            session()->forget('shopify_oauth_state');
            
            return redirect('/app/settings')->with('success', 'Shop connected successfully!');
            
        } catch (\Exception $e) {
            return redirect('/app/settings')->with('error', 'Failed to connect shop: ' . $e->getMessage());
        }
    }
}

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
                    'name' => $shopData['name'],
                    'email' => $shopData['email'],
                    'domain' => $shopData['domain'],
                    'province' => $shopData['province'],
                    'country' => $shopData['country'],
                    'address1' => $shopData['address1'],
                    'zip' => $shopData['zip'],
                    'city' => $shopData['city'],
                    'source' => $shopData['source'],
                    'phone' => $shopData['phone'],
                    'shopify_updated_at' => $shopData['updated_at'],
                    'shopify_created_at' => $shopData['created_at'],
                    'country_code' => $shopData['country_code'],
                    'country_name' => $shopData['country_name'],
                    'currency' => $shopData['currency'],
                    'customer_email' => $shopData['customer_email'],
                    'timezone' => $shopData['timezone'],
                    'iana_timezone' => $shopData['iana_timezone'],
                    'shopify_plan_name' => $shopData['plan_name'],
                    'has_discounts' => $shopData['has_discounts'],
                    'has_gift_cards' => $shopData['has_gift_cards'],
                    'force_ssl' => $shopData['force_ssl'],
                    'checkout_api_supported' => $shopData['checkout_api_supported'],
                    'multi_location_enabled' => $shopData['multi_location_enabled'],
                    'has_storefront' => $shopData['has_storefront'],
                    'eligible_for_payments' => $shopData['eligible_for_payments'],
                    'eligible_for_card_reader_giveaway' => $shopData['eligible_for_card_reader_giveaway'],
                    'finances' => $shopData['finances'],
                    'primary_location_id' => $shopData['primary_location_id'],
                    'cookie_consent_level' => $shopData['cookie_consent_level'],
                    'visitor_tracking_consent_preference' => $shopData['visitor_tracking_consent_preference'],
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

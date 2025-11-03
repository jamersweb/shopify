<?php

namespace App\Http\Middleware;

use App\Models\Shop;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShopifyAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $shop = $request->query('shop') ?: session('shop');
        
        if (!$shop) {
            return response()->json(['error' => 'Shop parameter is required'], 400);
        }

        $shopRecord = Shop::where('shopify_domain', $shop)->first();
        
        if (!$shopRecord) {
            return redirect("/auth/install?shop={$shop}");
        }

        // Set shop in session for embedded app
        session(['shop' => $shop]);
        
        // Add shop to request for easy access in controllers
        $request->merge(['shop_record' => $shopRecord]);

        return $next($request);
    }
}

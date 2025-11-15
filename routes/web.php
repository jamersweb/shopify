<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShopifyController;

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Shopify OAuth callback (must be public, no auth required)
Route::get('/app/shopify/callback', [ShopifyController::class, 'callback'])->name('shopify.callback');

// Protected dashboard routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/orders', [DashboardController::class, 'orders'])->name('dashboard.orders');
    Route::post('/dashboard/fetch-orders', [DashboardController::class, 'fetchOrders'])->name('dashboard.fetch-orders');
    Route::get('/dashboard/shipment/{id}', [DashboardController::class, 'shipmentDetails'])->name('dashboard.shipment');
    Route::post('/dashboard/shipment/{id}/send', [DashboardController::class, 'sendShipment'])->name('dashboard.shipment.send');
    Route::post('/dashboard/shipment/{id}/retry', [DashboardController::class, 'retryShipment'])->name('dashboard.shipment.retry');
    
    // Settings routes
    Route::get('/app/settings', [SettingsController::class, 'index'])->name('settings');
    Route::get('/app/settings/shop/{shopId}', [SettingsController::class, 'shopSettings'])->name('settings.shop');
    Route::post('/app/settings/shop/{shopId}', [SettingsController::class, 'updateShopSettings'])->name('settings.shop.update');
    Route::post('/app/settings/shop/{shopId}/test-connection', [SettingsController::class, 'testConnection'])->name('settings.test-connection');
    Route::post('/app/settings/shop/{shopId}/disconnect', [SettingsController::class, 'disconnectShop'])->name('settings.disconnect');
    
    // Shopify OAuth install (requires auth to initiate)
    Route::get('/app/shopify/install', [ShopifyController::class, 'install'])->name('shopify.install');
});

// Root route - redirect to login or dashboard based on auth
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// Test route
Route::get('/test', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'EcoFreight Shopify App is working!',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => 'Laravel 10.49.1'
    ]);
});

// Home route - redirect to dashboard if authenticated
Route::get('/home', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// Admin route - redirect to dashboard (no separate admin for now)
Route::get('/admin', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// Ops Dashboard routes (existing)
Route::get('/app/ops', [\App\Http\Controllers\OpsController::class, 'dashboard'])->name('app.ops');
Route::get('/app/ops/search', [\App\Http\Controllers\OpsController::class, 'search'])->name('ops.search');
Route::get('/app/ops/details/{id}', [\App\Http\Controllers\OpsController::class, 'details'])->name('ops.details');
Route::post('/app/ops/sync/{id}', [\App\Http\Controllers\OpsController::class, 'syncTracking'])->name('ops.sync');
Route::post('/app/ops/void/{id}', [\App\Http\Controllers\OpsController::class, 'voidShipment'])->name('ops.void');
Route::post('/app/ops/reship/{id}', [\App\Http\Controllers\OpsController::class, 'reship'])->name('ops.reship');
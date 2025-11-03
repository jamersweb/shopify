@extends('layouts.app')

@section('title', 'Dashboard - EcoFreight Shopify App')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-4">
                <i class="fas fa-tachometer-alt text-primary mr-2"></i>
                Dashboard Overview
            </h1>
            <p class="text-gray-600">Welcome back! Here's what's happening with your shipments.</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-box text-blue-500 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Shipments</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['total_shipments'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-clock text-yellow-500 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Pending</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['pending_shipments'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Delivered</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['delivered_shipments'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Errors</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['error_shipments'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Shops Section -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                <i class="fas fa-store text-primary mr-2"></i>
                Your Shops
            </h3>
            
            @if($shops->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($shops as $shop)
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900">{{ $shop->name }}</h4>
                                    <p class="text-sm text-gray-500">{{ $shop->domain }}</p>
                                </div>
                                <div class="flex space-x-2">
                                    <button onclick="fetchOrders({{ $shop->id }})" 
                                            id="fetch-orders-btn"
                                            class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded text-white bg-primary hover:bg-blue-700">
                                        <i class="fas fa-download mr-1"></i>
                                        Fetch Orders
                                    </button>
                                </div>
                            </div>
                            <div class="mt-2 text-xs text-gray-500">
                                Shipments: {{ $shop->shipments->count() }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <i class="fas fa-store text-gray-400 text-4xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No shops connected</h3>
                    <p class="text-gray-500 mb-4">Connect your Shopify store to start managing shipments.</p>
                    <a href="/app/settings" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>
                        Connect Shop
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Recent Shipments -->
    @if($recentShipments->count() > 0)
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                <i class="fas fa-history text-primary mr-2"></i>
                Recent Shipments
            </h3>
            
            <div class="overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shop</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">AWB</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($recentShipments as $shipment)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $shipment->shopify_order_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $shipment->shop->name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $shipment->ecofreight_awb ?: 'Pending' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    @if($shipment->status === 'delivered') bg-green-100 text-green-800
                                    @elseif($shipment->status === 'error') bg-red-100 text-red-800
                                    @elseif($shipment->status === 'pending') bg-yellow-100 text-yellow-800
                                    @else bg-blue-100 text-blue-800 @endif">
                                    {{ ucfirst($shipment->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $shipment->created_at->format('M j, Y g:i A') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4 text-center">
                <a href="/dashboard/orders" class="text-primary hover:text-blue-700 font-medium">
                    View all shipments →
                </a>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    // Get CSRF token with fallback
    function getCSRFToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '{{ csrf_token() }}';
    }
    
    function fetchOrders(shopId) {
        const btn = document.getElementById('fetch-orders-btn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Fetching...';
        btn.disabled = true;
        
        fetch('/dashboard/fetch-orders', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCSRFToken()
            },
            body: JSON.stringify({ shop_id: shopId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Orders fetched successfully! ' + (data.processed_count || 0) + ' new orders.');
                location.reload();
            } else {
                alert('Error: ' + data.message);
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    }
</script>
@endpush
@endsection

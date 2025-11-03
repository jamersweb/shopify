@extends('layouts.app')

@section('title', 'Settings - EcoFreight Shopify App')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-cog text-primary mr-2"></i>
                Settings
            </h1>
            <p class="text-gray-600 mt-1">Manage your Shopify stores and EcoFreight configuration</p>
        </div>
    </div>

    <!-- Connect New Shop -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                <i class="fas fa-store text-primary mr-2"></i>
                Connect New Shopify Store
            </h3>
            
            <form method="GET" action="/app/shopify/install" class="space-y-4">
                <div>
                    <label for="shop" class="block text-sm font-medium text-gray-700">Shop Domain</label>
                    <div class="mt-1 flex rounded-md shadow-sm">
                        <input type="text" name="shop" id="shop" 
                               placeholder="your-store" 
                               class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-l-md border border-gray-300 focus:ring-primary focus:border-primary sm:text-sm">
                        <span class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                            .myshopify.com
                        </span>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        Enter your Shopify store domain (e.g., "my-store" for my-store.myshopify.com)
                    </p>
                </div>
                
                <div>
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-blue-700">
                        <i class="fas fa-link mr-2"></i>
                        Connect Store
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Connected Shops -->
    @if($shops->count() > 0)
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                Connected Stores
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($shops as $shop)
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">{{ $shop->name }}</h4>
                            <p class="text-sm text-gray-500">{{ $shop->domain }}</p>
                        </div>
                        <div class="flex space-x-2">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                Connected
                            </span>
                        </div>
                    </div>
                    
                    <div class="space-y-2 text-xs text-gray-500">
                        <div><strong>Plan:</strong> {{ $shop->shopify_plan_name }}</div>
                        <div><strong>Country:</strong> {{ $shop->country_name }}</div>
                        <div><strong>Currency:</strong> {{ $shop->currency }}</div>
                        <div><strong>Settings:</strong> 
                            @if($shop->settings)
                                <span class="text-green-600">Configured</span>
                            @else
                                <span class="text-yellow-600">Not configured</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="mt-4 flex space-x-2">
                        @if($shop->settings)
                            <a href="/app/settings/shop/{{ $shop->id }}" 
                               class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-edit mr-1"></i>
                                Edit Settings
                            </a>
                        @else
                            <a href="/app/settings/shop/{{ $shop->id }}" 
                               class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded text-white bg-primary hover:bg-blue-700">
                                <i class="fas fa-cog mr-1"></i>
                                Configure
                            </a>
                        @endif
                        
                        <button onclick="disconnectShop({{ $shop->id }})" 
                                class="inline-flex items-center px-3 py-1 border border-red-300 shadow-sm text-xs font-medium rounded text-red-700 bg-white hover:bg-red-50">
                            <i class="fas fa-unlink mr-1"></i>
                            Disconnect
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @else
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="text-center py-8">
                <i class="fas fa-store text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No stores connected</h3>
                <p class="text-gray-500 mb-4">Connect your Shopify store to start managing shipments.</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Environment Variables Notice -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">
                    Environment Configuration Required
                </h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>Make sure to set these environment variables in your <code>.env</code> file:</p>
                    <ul class="list-disc list-inside mt-2 space-y-1">
                        <li><code>SHOPIFY_API_KEY</code> - Your Shopify app's API key</li>
                        <li><code>SHOPIFY_API_SECRET</code> - Your Shopify app's API secret</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function disconnectShop(shopId) {
        if (confirm('Are you sure you want to disconnect this store? This will remove all settings and data.')) {
            fetch(`/app/settings/shop/${shopId}/disconnect`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        }
    }
</script>
@endpush
@endsection

@extends('layouts.app')

@section('title', 'Shipment Details - EcoFreight Shopify App')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        <i class="fas fa-box text-primary mr-2"></i>
                        Shipment Details
                    </h1>
                    <p class="text-gray-600 mt-1">Order: {{ $shipment->shopify_order_name }}</p>
                </div>
                <div class="flex space-x-3">
                    <a href="/dashboard/orders" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Orders
                    </a>
                    @if($shipment->status === 'error')
                        <button onclick="retryShipment({{ $shipment->id }})" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                            <i class="fas fa-redo mr-2"></i>
                            Retry Shipment
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Shipment Overview -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Basic Information -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    <i class="fas fa-info-circle text-primary mr-2"></i>
                    Basic Information
                </h3>
                
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Order Number</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $shipment->shopify_order_name }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Shop</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $shipment->shop->name }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">AWB/Tracking Number</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if($shipment->ecofreight_awb)
                                <span class="font-mono">{{ $shipment->ecofreight_awb }}</span>
                                @if($shipment->tracking_url)
                                    <a href="{{ $shipment->tracking_url }}" target="_blank" 
                                       class="ml-2 text-primary hover:text-blue-700">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                @endif
                            @else
                                <span class="text-gray-400">Pending</span>
                            @endif
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                @if($shipment->status === 'delivered') bg-green-100 text-green-800
                                @elseif($shipment->status === 'error') bg-red-100 text-red-800
                                @elseif($shipment->status === 'pending') bg-yellow-100 text-yellow-800
                                @else bg-blue-100 text-blue-800 @endif">
                                {{ ucfirst($shipment->status) }}
                            </span>
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Service</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($shipment->service ?? 'Standard') }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Created</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $shipment->created_at->format('M j, Y g:i A') }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Customer Information -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    <i class="fas fa-user text-primary mr-2"></i>
                    Customer Information
                </h3>
                
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Name</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $shipment->customer_name ?? 'N/A' }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Phone</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $shipment->customer_phone ?? 'N/A' }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $shipment->customer_email ?? 'N/A' }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Address</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if($shipment->customer_address)
                                {{ $shipment->customer_address }}<br>
                                {{ $shipment->customer_city }}, {{ $shipment->customer_postcode }}<br>
                                {{ $shipment->customer_country }}
                            @else
                                N/A
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <!-- Package Information -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                <i class="fas fa-cube text-primary mr-2"></i>
                Package Information
            </h3>
            
            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Weight</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $shipment->weight ?? 'N/A' }} kg</dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500">Dimensions</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        @if($shipment->length && $shipment->width && $shipment->height)
                            {{ $shipment->length }} × {{ $shipment->width }} × {{ $shipment->height }} cm
                        @else
                            N/A
                        @endif
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500">COD Amount</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        @if($shipment->cod_amount)
                            AED {{ number_format($shipment->cod_amount, 2) }}
                        @else
                            No COD
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Tracking Timeline -->
    @if($shipment->trackingLogs && $shipment->trackingLogs->count() > 0)
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                <i class="fas fa-route text-primary mr-2"></i>
                Tracking Timeline
            </h3>
            
            <div class="flow-root">
                <ul class="-mb-8">
                    @foreach($shipment->trackingLogs->sortByDesc('timestamp') as $index => $log)
                    <li>
                        <div class="relative pb-8">
                            @if(!$loop->last)
                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                            @endif
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white">
                                        <i class="fas fa-check text-white text-xs"></i>
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                    <div>
                                        <p class="text-sm text-gray-500">{{ $log->description }}</p>
                                        @if($log->location)
                                            <p class="text-xs text-gray-400">{{ $log->location }}</p>
                                        @endif
                                    </div>
                                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                        {{ $log->timestamp ? \Carbon\Carbon::parse($log->timestamp)->format('M j, g:i A') : 'N/A' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <!-- Error Information -->
    @if($shipment->status === 'error' && $shipment->error_message)
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                Error Information
            </h3>
            
            <div class="bg-red-50 border border-red-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Shipment Error</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p>{{ $shipment->error_message }}</p>
                        </div>
                        <div class="mt-4">
                            <button onclick="retryShipment({{ $shipment->id }})" 
                                    class="bg-red-100 px-3 py-2 rounded-md text-sm font-medium text-red-800 hover:bg-red-200">
                                Retry Shipment
                            </button>
                        </div>
                    </div>
                </div>
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
    
    function retryShipment(shipmentId) {
        if (confirm('Retry creating shipment for this order?')) {
            fetch(`/dashboard/shipment/${shipmentId}/retry`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken()
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Shipment retry initiated');
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


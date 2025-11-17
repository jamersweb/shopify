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
                    @if(!$shipment->ecofreight_awb && $shipment->status !== 'cancelled')
                        <button onclick="editShipment({{ $shipment->id }})" 
                                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-edit mr-2"></i>
                            Edit Order
                        </button>
                    @endif
                    @if($shipment->status === 'error')
                        <button onclick="retryShipment({{ $shipment->id }})" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                            <i class="fas fa-redo mr-2"></i>
                            Retry Shipment
                        </button>
                    @endif
                    @if(!$shipment->ecofreight_awb && $shipment->status !== 'cancelled' && $shipment->status !== 'delivered')
                        <button onclick="disableShipment({{ $shipment->id }}, this)" 
                                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-ban mr-2"></i>
                            Disable Order
                        </button>
                        <button onclick="cancelShipment({{ $shipment->id }}, this)" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                            <i class="fas fa-times-circle mr-2"></i>
                            Cancel Order
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
                
                @php
                    $orderData = $orderData ?? $shipment->shipment_data;
                    $shippingAddress = $orderData['shipping_address'] ?? null;
                    $customer = $orderData['customer'] ?? null;
                @endphp
                
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Name</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if($shippingAddress)
                                {{ $shippingAddress['name'] ?? ($shippingAddress['first_name'] ?? '') . ' ' . ($shippingAddress['last_name'] ?? '') }}
                            @elseif($customer)
                                {{ $customer['first_name'] ?? '' }} {{ $customer['last_name'] ?? '' }}
                            @else
                                N/A
                            @endif
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Phone</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $shippingAddress['phone'] ?? $customer['phone'] ?? 'N/A' }}
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $shippingAddress['email'] ?? $customer['email'] ?? $orderData['email'] ?? 'N/A' }}
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Address</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if($shippingAddress)
                                {{ $shippingAddress['address1'] ?? '' }}
                                @if(!empty($shippingAddress['address2']))
                                    <br>{{ $shippingAddress['address2'] }}
                                @endif
                                <br>{{ $shippingAddress['city'] ?? '' }}, {{ $shippingAddress['province'] ?? '' }}
                                @if(!empty($shippingAddress['zip']))
                                    <br>{{ $shippingAddress['zip'] }}
                                @endif
                                <br>{{ $shippingAddress['country'] ?? '' }}
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
            
            @php
                $orderData = $orderData ?? $shipment->shipment_data;
                $lineItems = $orderData['line_items'] ?? [];
                
                // Calculate total weight and dimensions
                $totalWeight = 0;
                $totalQuantity = 0;
                foreach ($lineItems as $item) {
                    $weight = ($item['grams'] ?? 0) / 1000; // Convert to kg
                    $quantity = $item['quantity'] ?? 1;
                    $totalWeight += $weight * $quantity;
                    $totalQuantity += $quantity;
                }
            @endphp
            
            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Weight</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        @if($totalWeight > 0)
                            {{ number_format($totalWeight, 2) }} kg
                        @else
                            N/A
                        @endif
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500">Items</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $totalQuantity }} item(s)
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500">COD Amount</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        @if($shipment->cod_enabled && $shipment->cod_amount)
                            AED {{ number_format($shipment->cod_amount, 2) }}
                        @else
                            No COD
                        @endif
                    </dd>
                </div>
            </dl>
            
            <!-- Product Details -->
            @if(!empty($lineItems))
            <div class="mt-6">
                <h4 class="text-sm font-medium text-gray-900 mb-3">Products</h4>
                <div class="overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Weight</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($lineItems as $item)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $item['title'] ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $item['sku'] ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ $item['quantity'] ?? 1 }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 text-right">
                                    @if(isset($item['grams']))
                                        {{ number_format($item['grams'] / 1000, 2) }} kg
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">
                                    {{ $orderData['currency'] ?? 'AED' }} {{ number_format($item['price'] ?? 0, 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
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
    
    function editShipment(shipmentId) {
        fetch(`/dashboard/shipment/${shipmentId}/edit`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': getCSRFToken(),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showEditModal(data.shipment);
            } else {
                alert('Error loading shipment: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
    }
    
    function showEditModal(shipment) {
        const orderData = shipment.shipment_data || {};
        const shippingAddress = orderData.shipping_address || {};
        
        const modal = document.createElement('div');
        modal.id = 'editShipmentModal';
        modal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50';
        modal.innerHTML = `
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900">
                        <i class="fas fa-edit text-primary mr-2"></i>
                        Edit Order: ${shipment.shopify_order_name}
                    </h3>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form id="editShipmentForm" onsubmit="updateShipment(event, ${shipment.id})">
                    <div class="space-y-6">
                        <div class="border-b pb-4">
                            <h4 class="text-md font-semibold text-gray-700 mb-3">Customer Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Name</label>
                                    <input type="text" name="customer_name" 
                                           value="${(shippingAddress.first_name || '') + ' ' + (shippingAddress.last_name || '')}"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                                    <input type="text" name="customer_phone" 
                                           value="${shippingAddress.phone || ''}"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Email</label>
                                    <input type="email" name="customer_email" 
                                           value="${shippingAddress.email || orderData.email || ''}"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                                </div>
                            </div>
                        </div>
                        
                        <div class="border-b pb-4">
                            <h4 class="text-md font-semibold text-gray-700 mb-3">Shipping Address</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Address Line 1</label>
                                    <input type="text" name="address1" 
                                           value="${shippingAddress.address1 || ''}"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Address Line 2</label>
                                    <input type="text" name="address2" 
                                           value="${shippingAddress.address2 || ''}"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">City</label>
                                    <input type="text" name="city" 
                                           value="${shippingAddress.city || ''}"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Postal Code</label>
                                    <input type="text" name="postal_code" 
                                           value="${shippingAddress.zip || shippingAddress.postal_code || ''}"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Province/State</label>
                                    <input type="text" name="province" 
                                           value="${shippingAddress.province || ''}"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Country</label>
                                    <input type="text" name="country" 
                                           value="${shippingAddress.country || 'United Arab Emirates'}"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Package Information -->
                        <div class="border-b pb-4">
                            <h4 class="text-md font-semibold text-gray-700 mb-3">
                                <i class="fas fa-cube text-primary mr-2"></i>
                                Package Information
                            </h4>
                            <div id="packageItemsContainer" class="space-y-4">
                                ${(orderData.line_items || []).map((item, index) => {
                                    const weightKg = item.grams ? (item.grams / 1000).toFixed(2) : '0.40';
                                    const dimensions = item.dimensions || {};
                                    const length = dimensions.length || 10;
                                    const width = dimensions.width || 10;
                                    const height = dimensions.height || 10;
                                    return `
                                        <div class="border rounded-lg p-4 bg-gray-50">
                                            <div class="flex justify-between items-center mb-3">
                                                <h5 class="font-medium text-gray-900">${item.title || 'Item ' + (index + 1)}</h5>
                                                <span class="text-xs text-gray-500">SKU: ${item.sku || 'N/A'}</span>
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">Quantity</label>
                                                    <input type="number" min="1" 
                                                           name="line_items[${index}][quantity]" 
                                                           value="${item.quantity || 1}"
                                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">Weight (kg)</label>
                                                    <input type="number" step="0.01" min="0.01" 
                                                           name="line_items[${index}][weight_kg]" 
                                                           value="${weightKg}"
                                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">Length (cm)</label>
                                                    <input type="number" step="0.1" min="1" 
                                                           name="line_items[${index}][length]" 
                                                           value="${length}"
                                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">Width (cm)</label>
                                                    <input type="number" step="0.1" min="1" 
                                                           name="line_items[${index}][width]" 
                                                           value="${width}"
                                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">Height (cm)</label>
                                                    <input type="number" step="0.1" min="1" 
                                                           name="line_items[${index}][height]" 
                                                           value="${height}"
                                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                                                </div>
                                            </div>
                                            <input type="hidden" name="line_items[${index}][id]" value="${item.id || ''}">
                                            <input type="hidden" name="line_items[${index}][variant_id]" value="${item.variant_id || ''}">
                                            <input type="hidden" name="line_items[${index}][title]" value="${item.title || ''}">
                                            <input type="hidden" name="line_items[${index}][sku]" value="${item.sku || ''}">
                                        </div>
                                    `;
                                }).join('')}
                            </div>
                            ${(!orderData.line_items || orderData.line_items.length === 0) ? '<p class="text-sm text-gray-500 italic">No items found in this order.</p>' : ''}
                        </div>
                        
                        <div class="border-b pb-4">
                            <h4 class="text-md font-semibold text-gray-700 mb-3">Package & Payment</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Service Type</label>
                                    <select name="service_type" 
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                                        <option value="standard" ${shipment.service_type === 'standard' ? 'selected' : ''}>Standard</option>
                                        <option value="express" ${shipment.service_type === 'express' ? 'selected' : ''}>Express</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">COD Enabled</label>
                                    <select name="cod_enabled" 
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                                        <option value="0" ${!shipment.cod_enabled ? 'selected' : ''}>No</option>
                                        <option value="1" ${shipment.cod_enabled ? 'selected' : ''}>Yes</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">COD Amount (AED)</label>
                                    <input type="number" step="0.01" name="cod_amount" 
                                           value="${shipment.cod_amount || 0}"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="closeEditModal()" 
                                class="px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-blue-700">
                            <i class="fas fa-save mr-2"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        `;
        
        document.body.appendChild(modal);
    }
    
    function closeEditModal() {
        const modal = document.getElementById('editShipmentModal');
        if (modal) {
            modal.remove();
        }
    }
    
    function updateShipment(event, shipmentId) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        
        const submitButton = form.querySelector('button[type="submit"]');
        const originalHTML = submitButton.innerHTML;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
        submitButton.disabled = true;
        
        fetch(`/dashboard/shipment/${shipmentId}/update`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCSRFToken()
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const notification = document.createElement('div');
                notification.className = 'fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg bg-green-50 border border-green-200';
                notification.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                        <div>
                            <p class="font-semibold text-green-800">Order Updated</p>
                            <p class="text-sm text-green-600">${data.message}</p>
                        </div>
                    </div>
                `;
                document.body.appendChild(notification);
                
                closeEditModal();
                // Force reload to show updated data
                setTimeout(() => {
                    notification.remove();
                    // Use location.reload(true) to force reload from server (bypass cache)
                    location.reload(true);
                }, 1500);
            } else {
                alert('Error: ' + data.message);
                submitButton.innerHTML = originalHTML;
                submitButton.disabled = false;
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
            submitButton.innerHTML = originalHTML;
            submitButton.disabled = false;
        });
    }
    
    function disableShipment(shipmentId, buttonElement) {
        if (!confirm('Disable this order? It will not be processed automatically.')) {
            return;
        }
        
        const button = buttonElement || event.target.closest('button');
        const originalHTML = button.innerHTML;
        
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Disabling...';
        button.disabled = true;
        
        fetch(`/dashboard/shipment/${shipmentId}/disable`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCSRFToken()
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const notification = document.createElement('div');
                notification.className = 'fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg bg-orange-50 border border-orange-200';
                notification.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-orange-500 text-xl mr-3"></i>
                        <div>
                            <p class="font-semibold text-orange-800">Order Disabled</p>
                            <p class="text-sm text-orange-600">${data.message}</p>
                        </div>
                    </div>
                `;
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.remove();
                    location.reload();
                }, 3000);
            } else {
                alert('Error: ' + data.message);
                button.innerHTML = originalHTML;
                button.disabled = false;
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
            button.innerHTML = originalHTML;
            button.disabled = false;
        });
    }
    
    function cancelShipment(shipmentId, buttonElement) {
        if (!confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
            return;
        }
        
        const button = buttonElement || event.target.closest('button');
        const originalHTML = button.innerHTML;
        
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Cancelling...';
        button.disabled = true;
        
        fetch(`/dashboard/shipment/${shipmentId}/cancel`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCSRFToken()
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const notification = document.createElement('div');
                notification.className = 'fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg bg-yellow-50 border border-yellow-200';
                notification.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-yellow-500 text-xl mr-3"></i>
                        <div>
                            <p class="font-semibold text-yellow-800">Order Cancelled</p>
                            <p class="text-sm text-yellow-600">${data.message}</p>
                        </div>
                    </div>
                `;
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.remove();
                    location.reload();
                }, 3000);
            } else {
                alert('Error: ' + data.message);
                button.innerHTML = originalHTML;
                button.disabled = false;
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
            button.innerHTML = originalHTML;
            button.disabled = false;
        });
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


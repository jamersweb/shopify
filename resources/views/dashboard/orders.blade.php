@extends('layouts.app')

@section('title', 'Orders - EcoFreight Shopify App')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        <i class="fas fa-box text-primary mr-2"></i>
                        Orders & Shipments
                    </h1>
                    <p class="text-gray-600 mt-1">Manage your Shopify orders and EcoFreight shipments</p>
                </div>
                <div class="flex space-x-3">
                    <button onclick="fetchAllOrders()" 
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-blue-700">
                        <i class="fas fa-download mr-2"></i>
                        Fetch Orders
                    </button>
                    <button onclick="refreshOrders()" 
                            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-sync-alt mr-2"></i>
                        Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <form method="GET" action="/dashboard/orders" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                    <input type="text" name="search" id="search" 
                           value="{{ request('search') }}"
                           placeholder="Order # or AWB"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                </div>
                
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" id="status" 
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="created" {{ request('status') === 'created' ? 'selected' : '' }}>Created</option>
                        <option value="shipped" {{ request('status') === 'shipped' ? 'selected' : '' }}>Shipped</option>
                        <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                        <option value="error" {{ request('status') === 'error' ? 'selected' : '' }}>Error</option>
                    </select>
                </div>
                
                <div>
                    <label for="shop_id" class="block text-sm font-medium text-gray-700">Shop</label>
                    <select name="shop_id" id="shop_id" 
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                        <option value="">All Shops</option>
                        @foreach($shops as $shop)
                            <option value="{{ $shop->id }}" {{ request('shop_id') == $shop->id ? 'selected' : '' }}>
                                {{ $shop->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" 
                            class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-blue-700">
                        <i class="fas fa-search mr-2"></i>
                        Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            @if($shipments->count() > 0)
                <div class="overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shop</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">AWB</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($shipments as $shipment)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $shipment->shopify_order_name }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        #{{ $shipment->shopify_order_id }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($shipment->shipment_data && isset($shipment->shipment_data['customer']))
                                        <div class="text-sm text-gray-900">
                                            {{ $shipment->shipment_data['customer']['first_name'] }} {{ $shipment->shipment_data['customer']['last_name'] }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $shipment->shipment_data['customer']['email'] }}
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-500">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $shipment->shop->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($shipment->ecofreight_awb)
                                        <span class="font-mono">{{ $shipment->ecofreight_awb }}</span>
                                    @else
                                        <span class="text-gray-400">Pending</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $shipment->service_type ?: 'Standard' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                        @if($shipment->status === 'delivered') bg-green-100 text-green-800
                                        @elseif($shipment->status === 'error') bg-red-100 text-red-800
                                        @elseif($shipment->status === 'pending') bg-yellow-100 text-yellow-800
                                        @else bg-blue-100 text-blue-800 @endif">
                                        {{ ucfirst($shipment->status) }}
                                    </span>
                                    @if($shipment->error_message)
                                        <div class="text-xs text-red-600 mt-1" title="{{ $shipment->error_message }}">
                                            <i class="fas fa-exclamation-circle"></i> Error
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $shipment->created_at->format('M j, Y g:i A') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="/dashboard/shipment/{{ $shipment->id }}" 
                                           class="text-primary hover:text-blue-700" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if(!$shipment->ecofreight_awb && $shipment->status !== 'cancelled')
                                            <button onclick="editShipment({{ $shipment->id }})" 
                                                    class="text-blue-600 hover:text-blue-700" 
                                                    title="Edit Order">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endif
                                        @if($shipment->status === 'pending' && !$shipment->ecofreight_awb)
                                            <button onclick="sendShipment({{ $shipment->id }}, this)" 
                                                    class="text-green-600 hover:text-green-700" 
                                                    title="Send to EcoFreight">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        @endif
                                        @if($shipment->status === 'error')
                                            <button onclick="retryShipment({{ $shipment->id }}, this)" 
                                                    class="text-yellow-600 hover:text-yellow-700"
                                                    title="Retry">
                                                <i class="fas fa-redo"></i>
                                            </button>
                                        @endif
                                        @if(!$shipment->ecofreight_awb && $shipment->status !== 'cancelled' && $shipment->status !== 'delivered')
                                            <button onclick="disableShipment({{ $shipment->id }}, this)" 
                                                    class="text-orange-600 hover:text-orange-700"
                                                    title="Disable Order">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                            <button onclick="cancelShipment({{ $shipment->id }}, this)" 
                                                    class="text-red-600 hover:text-red-700"
                                                    title="Cancel Order">
                                                <i class="fas fa-times-circle"></i>
                                            </button>
                                        @endif
                                        @if($shipment->ecofreight_awb)
                                            <a href="{{ $shipment->tracking_url ?? '#' }}" target="_blank" 
                                               class="text-green-600 hover:text-green-700"
                                               title="Track Shipment">
                                                <i class="fas fa-truck"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="mt-6">
                    {{ $shipments->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-box text-gray-400 text-6xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No shipments found</h3>
                    <p class="text-gray-500 mb-4">
                        @if(request()->hasAny(['search', 'status', 'shop_id']))
                            Try adjusting your filters or 
                            <a href="/dashboard/orders" class="text-primary hover:text-blue-700">clear all filters</a>
                        @else
                            Start by fetching orders from your connected shops.
                        @endif
                    </p>
                    @if($shops->count() > 0)
                        <button onclick="fetchAllOrders()" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-blue-700">
                            <i class="fas fa-download mr-2"></i>
                            Fetch Orders
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Get CSRF token with fallback
    function getCSRFToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '{{ csrf_token() }}';
    }
    
    function refreshOrders() {
        const button = document.querySelector('button[onclick="refreshOrders()"]');
        const originalText = button.innerHTML;
        
        // Show loading state
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Refreshing...';
        button.disabled = true;
        
        // Show a brief message
        const message = document.createElement('div');
        message.className = 'fixed top-4 right-4 z-50 p-3 rounded-lg shadow-lg bg-blue-50 border border-blue-200';
        message.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                <span class="text-sm text-blue-800">Refreshing orders...</span>
            </div>
        `;
        document.body.appendChild(message);
        
        // Reload after a brief delay to show the message
        setTimeout(() => {
            location.reload();
        }, 500);
    }
    
    function fetchAllOrders() {
        const button = document.querySelector('button[onclick="fetchAllOrders()"]');
        const originalHTML = button.innerHTML;
        
        // Show loading state
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Fetching...';
        button.disabled = true;
        
        const shops = @json($shops->pluck('id'));
        
        if (shops.length === 0) {
            alert('No shops connected. Please connect a shop first.');
            button.innerHTML = originalHTML;
            button.disabled = false;
            return;
        }
        
        if (!confirm(`Fetch orders from ${shops.length} connected shop(s)?`)) {
            button.innerHTML = originalHTML;
            button.disabled = false;
            return;
        }
        
        let completed = 0;
        let failed = 0;
        let totalProcessed = 0;
        const errors = [];
        
        // Show progress notification
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg bg-blue-50 border border-blue-200';
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-spinner fa-spin text-blue-500 text-xl mr-3"></i>
                <div>
                    <p class="font-semibold text-blue-800">Fetching Orders</p>
                    <p class="text-sm text-blue-600">Processing shops... (0/${shops.length})</p>
                </div>
            </div>
        `;
        document.body.appendChild(notification);
        
        shops.forEach(shopId => {
            fetch(`/dashboard/fetch-orders`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken()
                },
                body: JSON.stringify({ shop_id: shopId })
            })
            .then(response => response.json())
            .then(data => {
                completed++;
                if (!data.success) {
                    failed++;
                    errors.push(data.message || 'Unknown error');
                } else {
                    totalProcessed += data.processed_count || 0;
                }
                
                // Update progress
                notification.querySelector('.text-sm').textContent = 
                    `Processing shops... (${completed}/${shops.length})`;
                
                if (completed === shops.length) {
                    // Update notification
                    if (failed > 0) {
                        notification.className = 'fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg bg-yellow-50 border border-yellow-200';
                        notification.innerHTML = `
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-triangle text-yellow-500 text-xl mr-3"></i>
                                <div>
                                    <p class="font-semibold text-yellow-800">Fetch Completed</p>
                                    <p class="text-sm text-yellow-600">${totalProcessed} new orders processed. ${failed} shop(s) had errors.</p>
                                </div>
                            </div>
                        `;
                    } else {
                        notification.className = 'fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg bg-green-50 border border-green-200';
                        notification.innerHTML = `
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                                <div>
                                    <p class="font-semibold text-green-800">Success!</p>
                                    <p class="text-sm text-green-600">Fetched ${totalProcessed} new order(s) from all shops.</p>
                                </div>
                            </div>
                        `;
                    }
                    
                    // Auto-hide and reload
                    setTimeout(() => {
                        notification.style.display = 'none';
                        setTimeout(() => {
                            notification.remove();
                            location.reload();
                        }, 300);
                    }, 3000);
                    
                    button.innerHTML = originalHTML;
                    button.disabled = false;
                }
            })
            .catch(error => {
                completed++;
                failed++;
                errors.push(error.message);
                
                // Update progress
                notification.querySelector('.text-sm').textContent = 
                    `Processing shops... (${completed}/${shops.length})`;
                
                if (completed === shops.length) {
                    notification.className = 'fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg bg-red-50 border border-red-200';
                    notification.innerHTML = `
                        <div class="flex items-center">
                            <i class="fas fa-times-circle text-red-500 text-xl mr-3"></i>
                            <div>
                                <p class="font-semibold text-red-800">Error</p>
                                <p class="text-sm text-red-600">Failed to fetch orders. Please try again.</p>
                            </div>
                        </div>
                    `;
                    
                    setTimeout(() => {
                        notification.style.display = 'none';
                        setTimeout(() => {
                            notification.remove();
                            location.reload();
                        }, 300);
                    }, 3000);
                    
                    button.innerHTML = originalHTML;
                    button.disabled = false;
                }
            });
        });
    }
    
    function sendShipment(shipmentId, buttonElement) {
        const button = buttonElement || event.target.closest('button');
        const originalHTML = button.innerHTML;
        
        // Show loading state
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        
        fetch(`/dashboard/shipment/${shipmentId}/send`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCSRFToken()
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success notification
                const notification = document.createElement('div');
                notification.className = 'fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg bg-green-50 border border-green-200';
                notification.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                        <div>
                            <p class="font-semibold text-green-800">Shipment Queued</p>
                            <p class="text-sm text-green-600">${data.message}</p>
                        </div>
                    </div>
                `;
                document.body.appendChild(notification);
                
                // Auto-hide and reload
                setTimeout(() => {
                    notification.style.display = 'none';
                    setTimeout(() => {
                        notification.remove();
                        location.reload();
                    }, 300);
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
    
    function retryShipment(shipmentId, buttonElement) {
        const button = buttonElement || event.target.closest('button');
        const originalHTML = button.innerHTML;
        
        // Show loading state
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        
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
                // Show success notification
                const notification = document.createElement('div');
                notification.className = 'fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg bg-green-50 border border-green-200';
                notification.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                        <div>
                            <p class="font-semibold text-green-800">Retry Initiated</p>
                            <p class="text-sm text-green-600">${data.message}</p>
                        </div>
                    </div>
                `;
                document.body.appendChild(notification);
                
                // Auto-hide and reload
                setTimeout(() => {
                    notification.style.display = 'none';
                    setTimeout(() => {
                        notification.remove();
                        location.reload();
                    }, 300);
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
    
    function editShipment(shipmentId) {
        // Fetch shipment data
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
        
        // Create modal
        const modal = document.createElement('div');
        modal.id = 'editShipmentModal';
        modal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50';
        modal.innerHTML = `
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white">
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
                        <!-- Customer Information -->
                        <div class="border-b pb-4">
                            <h4 class="text-md font-semibold text-gray-700 mb-3">Customer Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Name</label>
                                    <input type="text" name="customer_name" 
                                           value="${shippingAddress.name || (shippingAddress.first_name || '') + ' ' + (shippingAddress.last_name || '')}"
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
                        
                        <!-- Shipping Address -->
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
                        
                        <!-- Package & COD -->
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
        
        // Show loading
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
                // Show success notification
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
                
                // Close modal and reload
                closeEditModal();
                setTimeout(() => {
                    notification.remove();
                    location.reload();
                }, 2000);
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
    
    function cancelShipment(shipmentId, buttonElement) {
        if (!confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
            return;
        }
        
        const button = buttonElement || event.target.closest('button');
        const originalHTML = button.innerHTML;
        
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
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
    
    function disableShipment(shipmentId, buttonElement) {
        if (!confirm('Disable this order? It will not be processed automatically.')) {
            return;
        }
        
        const button = buttonElement || event.target.closest('button');
        const originalHTML = button.innerHTML;
        
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
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
</script>
@endpush
@endsection

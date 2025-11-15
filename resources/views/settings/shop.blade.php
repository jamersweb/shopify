@extends('layouts.app')

@section('title', 'Shop Settings - EcoFreight Shopify App')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        <i class="fas fa-cog text-primary mr-2"></i>
                        Shop Settings
                    </h1>
                    <p class="text-gray-600 mt-1">{{ $shop->name }} - {{ $shop->domain }}</p>
                </div>
                <a href="/app/settings" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Settings
                </a>
            </div>
        </div>
    </div>

    <!-- Settings Form -->
    <form method="POST" action="/app/settings/shop/{{ $shop->id }}" class="space-y-6">
        @csrf
        
        <!-- EcoFreight Credentials -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    <i class="fas fa-key text-primary mr-2"></i>
                    EcoFreight Credentials
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="ecofreight_username" class="block text-sm font-medium text-gray-700">Username</label>
                        <input type="text" name="ecofreight_username" id="ecofreight_username" 
                               value="{{ old('ecofreight_username', $settings->ecofreight_username ?? 'apitesting') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="ecofreight_password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="ecofreight_password" id="ecofreight_password" 
                               value="{{ old('ecofreight_password', ($settings && $settings->ecofreight_password) ? '••••••••' : 'apitesting') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label for="ecofreight_base_url" class="block text-sm font-medium text-gray-700">Base URL</label>
                        <input type="url" name="ecofreight_base_url" id="ecofreight_base_url" 
                               value="{{ old('ecofreight_base_url', $settings->ecofreight_base_url ?? 'https://app.ecofreight.ae/en') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="button" onclick="testConnection()" 
                            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-plug mr-2"></i>
                        Test Connection
                    </button>
                </div>
            </div>
        </div>

        <!-- Ship-From Information -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    <i class="fas fa-map-marker-alt text-primary mr-2"></i>
                    Ship-From Information
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="company_name" class="block text-sm font-medium text-gray-700">Company Name</label>
                        <input type="text" name="company_name" id="company_name" 
                               value="{{ old('company_name', $settings->company_name ?? '') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="contact_name" class="block text-sm font-medium text-gray-700">Contact Name</label>
                        <input type="text" name="contact_name" id="contact_name" 
                               value="{{ old('contact_name', $settings->contact_name ?? '') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                        <input type="text" name="phone" id="phone" 
                               value="{{ old('phone', $settings->phone ?? '') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" id="email" 
                               value="{{ old('email', $settings->email ?? '') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label for="address1" class="block text-sm font-medium text-gray-700">Address Line 1</label>
                        <input type="text" name="address1" id="address1" 
                               value="{{ old('address1', $settings->address1 ?? '') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label for="address2" class="block text-sm font-medium text-gray-700">Address Line 2</label>
                        <input type="text" name="address2" id="address2" 
                               value="{{ old('address2', $settings->address2 ?? '') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700">City/Emirate</label>
                        <input type="text" name="city" id="city" 
                               value="{{ old('city', $settings->city ?? '') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="postcode" class="block text-sm font-medium text-gray-700">Postcode</label>
                        <input type="text" name="postcode" id="postcode" 
                               value="{{ old('postcode', $settings->postcode ?? '') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700">Country</label>
                        <input type="text" name="country" id="country" 
                               value="{{ old('country', $settings->country ?? 'UAE') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                    </div>
                </div>
            </div>
        </div>

        <!-- Default Package Rules -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    <i class="fas fa-box text-primary mr-2"></i>
                    Default Package Rules
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="default_weight" class="block text-sm font-medium text-gray-700">Default Weight (kg)</label>
                        <input type="number" step="0.1" name="default_weight" id="default_weight" 
                               value="{{ old('default_weight', $settings->default_weight ?? '1.0') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="packing_rule" class="block text-sm font-medium text-gray-700">Packing Rule</label>
                        <select name="packing_rule" id="packing_rule" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                            <option value="per_order" {{ old('packing_rule', $settings->packing_rule ?? 'per_order') === 'per_order' ? 'selected' : '' }}>1 parcel per order</option>
                            <option value="per_item" {{ old('packing_rule', $settings->packing_rule ?? 'per_order') === 'per_item' ? 'selected' : '' }}>1 parcel per line item</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="default_length" class="block text-sm font-medium text-gray-700">Length (cm)</label>
                        <input type="number" name="default_length" id="default_length" 
                               value="{{ old('default_length', $settings->default_length ?? '30') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="default_width" class="block text-sm font-medium text-gray-700">Width (cm)</label>
                        <input type="number" name="default_width" id="default_width" 
                               value="{{ old('default_width', $settings->default_width ?? '20') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="default_height" class="block text-sm font-medium text-gray-700">Height (cm)</label>
                        <input type="number" name="default_height" id="default_height" 
                               value="{{ old('default_height', $settings->default_height ?? '10') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                    </div>
                </div>
            </div>
        </div>

        <!-- Services & Business Rules -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    <i class="fas fa-shipping-fast text-primary mr-2"></i>
                    Services & Business Rules
                </h3>
                
                <div class="space-y-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="express_enabled" id="express_enabled" value="1"
                               {{ old('express_enabled', $settings->express_enabled ?? true) ? 'checked' : '' }}
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <label for="express_enabled" class="ml-2 block text-sm text-gray-900">
                            Enable Express Service
                        </label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="standard_enabled" id="standard_enabled" value="1"
                               {{ old('standard_enabled', $settings->standard_enabled ?? true) ? 'checked' : '' }}
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <label for="standard_enabled" class="ml-2 block text-sm text-gray-900">
                            Enable Standard Service
                        </label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="cod_enabled" id="cod_enabled" value="1"
                               {{ old('cod_enabled', $settings->cod_enabled ?? false) ? 'checked' : '' }}
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <label for="cod_enabled" class="ml-2 block text-sm text-gray-900">
                            Enable COD (Cash on Delivery)
                        </label>
                    </div>
                    
                    <div>
                        <label for="cod_fee" class="block text-sm font-medium text-gray-700">COD Fee (AED)</label>
                        <input type="number" step="0.01" name="cod_fee" id="cod_fee" 
                               value="{{ old('cod_fee', $settings->cod_fee ?? '') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                    </div>
                </div>
            </div>
        </div>

        <!-- Tracking & Notifications -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    <i class="fas fa-bell text-primary mr-2"></i>
                    Tracking & Notifications
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="tracking_poll_interval" class="block text-sm font-medium text-gray-700">Polling Interval (hours)</label>
                        <select name="tracking_poll_interval" id="tracking_poll_interval" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                            <option value="1" {{ old('tracking_poll_interval', $settings->tracking_poll_interval ?? '2') === '1' ? 'selected' : '' }}>1 hour</option>
                            <option value="2" {{ old('tracking_poll_interval', $settings->tracking_poll_interval ?? '2') === '2' ? 'selected' : '' }}>2 hours</option>
                            <option value="4" {{ old('tracking_poll_interval', $settings->tracking_poll_interval ?? '2') === '4' ? 'selected' : '' }}>4 hours</option>
                            <option value="6" {{ old('tracking_poll_interval', $settings->tracking_poll_interval ?? '2') === '6' ? 'selected' : '' }}>6 hours</option>
                            <option value="12" {{ old('tracking_poll_interval', $settings->tracking_poll_interval ?? '2') === '12' ? 'selected' : '' }}>12 hours</option>
                            <option value="24" {{ old('tracking_poll_interval', $settings->tracking_poll_interval ?? '2') === '24' ? 'selected' : '' }}>24 hours</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="alert_emails" class="block text-sm font-medium text-gray-700">Alert Emails</label>
                        <input type="text" name="alert_emails" id="alert_emails" 
                               placeholder="admin@example.com, support@example.com"
                               value="{{ old('alert_emails', $settings->alert_emails ?? '') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                        <p class="mt-1 text-sm text-gray-500">Comma-separated email addresses</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end">
            <button type="submit" 
                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary hover:bg-blue-700">
                <i class="fas fa-save mr-2"></i>
                Save Settings
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function testConnection() {
        const button = document.querySelector('button[onclick="testConnection()"]');
        const originalText = button.innerHTML;
        
        // Show loading state
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Testing Connection...';
        button.disabled = true;
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-md';
        notification.style.display = 'none';
        document.body.appendChild(notification);
        
        // Get credentials from form if available
        const username = document.getElementById('ecofreight_username')?.value;
        const password = document.getElementById('ecofreight_password')?.value;
        const baseUrl = document.getElementById('ecofreight_base_url')?.value;
        
        const payload = {};
        if (username) payload.username = username;
        if (password && password !== '••••••••') payload.password = password;
        if (baseUrl) payload.base_url = baseUrl;
        
        fetch(`/app/settings/shop/{{ $shop->id }}/test-connection`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                notification.className = 'fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-md bg-green-50 border border-green-200';
                notification.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                        <div>
                            <p class="font-semibold text-green-800">Connection Successful!</p>
                            <p class="text-sm text-green-600">${data.message || 'Your EcoFreight credentials are valid.'}</p>
                        </div>
                    </div>
                `;
            } else {
                notification.className = 'fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-md bg-red-50 border border-red-200';
                notification.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-times-circle text-red-500 text-xl mr-3"></i>
                        <div>
                            <p class="font-semibold text-red-800">Connection Failed</p>
                            <p class="text-sm text-red-600">${data.message || 'Please check your credentials and try again.'}</p>
                        </div>
                    </div>
                `;
            }
            notification.style.display = 'block';
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                notification.style.display = 'none';
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        })
        .catch(error => {
            notification.className = 'fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-md bg-red-50 border border-red-200';
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 text-xl mr-3"></i>
                    <div>
                        <p class="font-semibold text-red-800">Error</p>
                        <p class="text-sm text-red-600">${error.message || 'An unexpected error occurred. Please try again.'}</p>
                    </div>
                </div>
            `;
            notification.style.display = 'block';
            
            setTimeout(() => {
                notification.style.display = 'none';
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        })
        .finally(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }
</script>
@endpush
@endsection

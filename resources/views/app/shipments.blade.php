<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipments - EcoFreight - {{ $shopRecord->name }}</title>
    <script src="https://unpkg.com/@shopify/app-bridge@3"></script>
    <script src="https://unpkg.com/@shopify/app-bridge-utils@3"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f6f6f7;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            background: #008060;
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .nav {
            background: white;
            padding: 0;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
        }
        .nav li {
            flex: 1;
        }
        .nav a {
            display: block;
            padding: 15px 20px;
            text-decoration: none;
            color: #333;
            text-align: center;
            border-right: 1px solid #eee;
            transition: background-color 0.2s;
        }
        .nav li:last-child a {
            border-right: none;
        }
        .nav a:hover,
        .nav a.active {
            background: #f0f0f0;
            color: #008060;
        }
        .content {
            background: white;
            margin-top: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .filters {
            padding: 20px;
            border-bottom: 1px solid #eee;
            background: #f8f9fa;
        }
        .filter-row {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            min-width: 150px;
        }
        .filter-group label {
            font-size: 12px;
            font-weight: 600;
            color: #666;
            margin-bottom: 5px;
        }
        .filter-group input,
        .filter-group select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn {
            background: #008060;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #006b4f;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        .btn-warning:hover {
            background: #e0a800;
        }
        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }
        .shipments-table {
            width: 100%;
            border-collapse: collapse;
        }
        .shipments-table th,
        .shipments-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .shipments-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            position: sticky;
            top: 0;
        }
        .shipments-table tr:hover {
            background: #f8f9fa;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-created {
            background: #d1ecf1;
            color: #0c5460;
        }
        .status-label_generated {
            background: #d4edda;
            color: #155724;
        }
        .status-shipped {
            background: #cce5ff;
            color: #004085;
        }
        .status-delivered {
            background: #d4edda;
            color: #155724;
        }
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        .status-error {
            background: #f8d7da;
            color: #721c24;
        }
        .actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #008060;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .alert {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .pagination {
            display: flex;
            justify-content: center;
            padding: 20px;
            gap: 10px;
        }
        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #333;
            border-radius: 4px;
        }
        .pagination .active {
            background: #008060;
            color: white;
            border-color: #008060;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
        }
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #e9ecef;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #008060;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>EcoFreight Shipments</h1>
            <p>{{ $shopRecord->name }}</p>
        </div>
        
        <div class="nav">
            <ul>
                <li><a href="/app?shop={{ $shop }}">Dashboard</a></li>
                <li><a href="/app/shipments?shop={{ $shop }}" class="active">Shipments</a></li>
                <li><a href="/app/settings?shop={{ $shop }}">Settings</a></li>
            </ul>
        </div>
        
        <div class="content">
            <div id="alerts"></div>
            
            <!-- Statistics -->
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number">{{ $shipments->total() }}</div>
                    <div class="stat-label">Total</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{ $shipments->where('status', 'pending')->count() }}</div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{ $shipments->where('status', 'delivered')->count() }}</div>
                    <div class="stat-label">Delivered</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{ $shipments->where('status', 'error')->count() }}</div>
                    <div class="stat-label">Errors</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters">
                <form method="GET" id="filtersForm">
                    <input type="hidden" name="shop" value="{{ $shop }}">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>All Statuses</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="created" {{ request('status') === 'created' ? 'selected' : '' }}>Created</option>
                                <option value="label_generated" {{ request('status') === 'label_generated' ? 'selected' : '' }}>Label Generated</option>
                                <option value="shipped" {{ request('status') === 'shipped' ? 'selected' : '' }}>Shipped</option>
                                <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                <option value="error" {{ request('status') === 'error' ? 'selected' : '' }}>Error</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Date From</label>
                            <input type="date" name="date_from" value="{{ request('date_from') }}">
                        </div>
                        <div class="filter-group">
                            <label>Date To</label>
                            <input type="date" name="date_to" value="{{ request('date_to') }}">
                        </div>
                        <div class="filter-group">
                            <label>Search</label>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Order name or AWB">
                        </div>
                        <div class="filter-group">
                            <button type="submit" class="btn">Filter</button>
                            <a href="/app/shipments?shop={{ $shop }}" class="btn btn-secondary">Clear</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Shipments Table -->
            <div style="overflow-x: auto;">
                <table class="shipments-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Status</th>
                            <th>AWB</th>
                            <th>Service</th>
                            <th>COD</th>
                            <th>Created</th>
                            <th>Last Sync</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shipments as $shipment)
                            <tr>
                                <td>
                                    <strong>{{ $shipment->shopify_order_name }}</strong>
                                    <br>
                                    <small>{{ $shipment->shopify_order_id }}</small>
                                </td>
                                <td>
                                    <span class="status-badge status-{{ $shipment->status }}">
                                        {{ str_replace('_', ' ', $shipment->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if($shipment->ecofreight_awb)
                                        <a href="{{ $shipment->tracking_url }}" target="_blank">
                                            {{ $shipment->ecofreight_awb }}
                                        </a>
                                    @else
                                        <span style="color: #999;">-</span>
                                    @endif
                                </td>
                                <td>{{ ucfirst($shipment->service_type) }}</td>
                                <td>
                                    @if($shipment->cod_enabled)
                                        <span style="color: #28a745;">AED {{ number_format($shipment->cod_amount, 2) }}</span>
                                    @else
                                        <span style="color: #999;">No</span>
                                    @endif
                                </td>
                                <td>{{ $shipment->created_at->format('M j, Y g:i A') }}</td>
                                <td>
                                    @if($shipment->last_tracking_sync)
                                        {{ $shipment->last_tracking_sync->format('M j, Y g:i A') }}
                                    @else
                                        <span style="color: #999;">Never</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="actions">
                                        @if($shipment->status === 'error')
                                            <button onclick="retryShipment({{ $shipment->id }})" class="btn btn-warning btn-sm">
                                                Retry
                                            </button>
                                        @endif
                                        
                                        @if($shipment->ecofreight_awb)
                                            <button onclick="regenerateLabel({{ $shipment->id }})" class="btn btn-secondary btn-sm">
                                                Regenerate Label
                                            </button>
                                            <button onclick="syncTracking({{ $shipment->id }})" class="btn btn-secondary btn-sm">
                                                Sync Tracking
                                            </button>
                                        @endif
                                        
                                        @if(!$shipment->isTerminal())
                                            <button onclick="cancelShipment({{ $shipment->id }})" class="btn btn-danger btn-sm">
                                                Cancel
                                            </button>
                                        @endif
                                        
                                        <a href="/app/shipments/{{ $shipment->id }}?shop={{ $shop }}" class="btn btn-secondary btn-sm">
                                            View
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px; color: #999;">
                                    No shipments found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($shipments->hasPages())
                <div class="pagination">
                    @if($shipments->onFirstPage())
                        <span>&laquo;</span>
                    @else
                        <a href="{{ $shipments->previousPageUrl() }}">&laquo;</a>
                    @endif

                    @foreach($shipments->getUrlRange(1, $shipments->lastPage()) as $page => $url)
                        @if($page == $shipments->currentPage())
                            <span class="active">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach

                    @if($shipments->hasMorePages())
                        <a href="{{ $shipments->nextPageUrl() }}">&raquo;</a>
                    @else
                        <span>&raquo;</span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="loading" id="loading">
        <div class="spinner"></div>
        <p>Processing...</p>
    </div>

    <script>
        // Initialize Shopify App Bridge
        const app = window['app-bridge'].createApp({
            apiKey: '{{ config("shopify.api_key") }}',
            shopOrigin: '{{ $shop }}',
            forceRedirect: true
        });

        // Show/hide loading
        function showLoading() {
            document.getElementById('loading').style.display = 'block';
        }

        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
        }

        // Show alert
        function showAlert(message, type = 'success') {
            const alertsDiv = document.getElementById('alerts');
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.textContent = message;
            alertsDiv.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

        // Retry shipment
        async function retryShipment(shipmentId) {
            if (!confirm('Are you sure you want to retry this shipment?')) {
                return;
            }

            showLoading();
            
            try {
                const response = await fetch(`/app/shipments/${shipmentId}/retry?shop={{ $shop }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Shipment retry initiated successfully!', 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert('Failed to retry shipment: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Failed to retry shipment: ' + error.message, 'error');
            } finally {
                hideLoading();
            }
        }

        // Regenerate label
        async function regenerateLabel(shipmentId) {
            if (!confirm('Are you sure you want to regenerate the label?')) {
                return;
            }

            showLoading();
            
            try {
                const response = await fetch(`/app/shipments/${shipmentId}/regenerate-label?shop={{ $shop }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Label regeneration initiated successfully!', 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert('Failed to regenerate label: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Failed to regenerate label: ' + error.message, 'error');
            } finally {
                hideLoading();
            }
        }

        // Sync tracking
        async function syncTracking(shipmentId) {
            showLoading();
            
            try {
                const response = await fetch(`/app/shipments/${shipmentId}/sync-tracking?shop={{ $shop }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Tracking sync initiated successfully!', 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert('Failed to sync tracking: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Failed to sync tracking: ' + error.message, 'error');
            } finally {
                hideLoading();
            }
        }

        // Cancel shipment
        async function cancelShipment(shipmentId) {
            if (!confirm('Are you sure you want to cancel this shipment? This action cannot be undone.')) {
                return;
            }

            showLoading();
            
            try {
                const response = await fetch(`/app/shipments/${shipmentId}/cancel?shop={{ $shop }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Shipment cancelled successfully!', 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert('Failed to cancel shipment: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Failed to cancel shipment: ' + error.message, 'error');
            } finally {
                hideLoading();
            }
        }
    </script>
</body>
</html>

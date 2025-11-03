@extends('layouts.app')

@section('title', 'Ops Dashboard - EcoFreight')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">Ops Dashboard</h1>
            <p class="text-muted">Monitor and manage EcoFreight shipments</p>
        </div>
    </div>

    <!-- Health Metrics -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Active Shipments
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $metrics['active_shipments'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shipping-fast fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Delivered (24h)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $metrics['delivered_last_24h'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Exceptions
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $metrics['exceptions'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Stale >48h
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $metrics['stale_48h'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Success Rate (7 days)</h6>
                </div>
                <div class="card-body">
                    <div class="progress mb-2">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $metrics['success_rate'] }}%">
                            {{ $metrics['success_rate'] }}%
                        </div>
                    </div>
                    <small class="text-muted">{{ $metrics['success_rate'] }}% of shipments delivered successfully</small>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Average Delivery Time</h6>
                </div>
                <div class="card-body">
                    <h4 class="text-primary">{{ $metrics['avg_delivery_days'] }} days</h4>
                    <small class="text-muted">Average time from creation to delivery (30 days)</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Search Shipments</h6>
        </div>
        <div class="card-body">
            <form id="searchForm">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Order #, AWB, Customer...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="all">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="created">Created</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="error">Error</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="date_from">From Date</label>
                            <input type="date" class="form-control" id="date_from" name="date_from">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="date_to">To Date</label>
                            <input type="date" class="form-control" id="date_to" name="date_to">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="stale_only" name="stale_only">
                                <label class="form-check-label" for="stale_only">
                                    Stale Only
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Shipments Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Shipments</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="shipmentsTable">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>AWB</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Last Sync</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
            <div id="pagination"></div>
        </div>
    </div>
</div>

<!-- Shipment Details Modal -->
<div class="modal fade" id="shipmentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Shipment Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="shipmentDetails">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Load initial data
    loadShipments();

    // Search form submission
    $('#searchForm').on('submit', function(e) {
        e.preventDefault();
        loadShipments();
    });

    // Load shipments data
    function loadShipments(page = 1) {
        const formData = $('#searchForm').serialize();
        
        $.ajax({
            url: '{{ route("ops.search") }}',
            type: 'GET',
            data: formData + '&page=' + page,
            success: function(response) {
                updateShipmentsTable(response.shipments);
                updatePagination(response.pagination);
            },
            error: function(xhr) {
                console.error('Error loading shipments:', xhr.responseText);
            }
        });
    }

    // Update shipments table
    function updateShipmentsTable(shipments) {
        const tbody = $('#shipmentsTable tbody');
        tbody.empty();

        shipments.forEach(function(shipment) {
            const row = `
                <tr>
                    <td>${shipment.shopify_order_name}</td>
                    <td>${shipment.customer_name || 'N/A'}</td>
                    <td>${shipment.ecofreight_awb || 'N/A'}</td>
                    <td>${shipment.service_type}</td>
                    <td>
                        <span class="badge badge-${getStatusBadgeColor(shipment.status)}">
                            ${shipment.status}
                        </span>
                        ${shipment.stale_flag ? '<span class="badge badge-warning ml-1">Stale</span>' : ''}
                    </td>
                    <td>${formatDate(shipment.last_checked_at)}</td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="viewShipment(${shipment.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-primary" onclick="syncTracking(${shipment.id})">
                            <i class="fas fa-sync"></i>
                        </button>
                        ${shipment.can_void ? '<button class="btn btn-sm btn-warning" onclick="voidShipment(' + shipment.id + ')"><i class="fas fa-ban"></i></button>' : ''}
                        ${shipment.can_reship ? '<button class="btn btn-sm btn-success" onclick="reship(' + shipment.id + ')"><i class="fas fa-redo"></i></button>' : ''}
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // Update pagination
    function updatePagination(pagination) {
        const paginationHtml = `
            <nav>
                <ul class="pagination justify-content-center">
                    <li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
                        <a class="page-link" href="#" onclick="loadShipments(${pagination.current_page - 1})">Previous</a>
                    </li>
                    <li class="page-item active">
                        <span class="page-link">${pagination.current_page} of ${pagination.last_page}</span>
                    </li>
                    <li class="page-item ${pagination.current_page === pagination.last_page ? 'disabled' : ''}">
                        <a class="page-link" href="#" onclick="loadShipments(${pagination.current_page + 1})">Next</a>
                    </li>
                </ul>
            </nav>
        `;
        $('#pagination').html(paginationHtml);
    }

    // Get status badge color
    function getStatusBadgeColor(status) {
        const colors = {
            'pending': 'warning',
            'created': 'info',
            'shipped': 'primary',
            'delivered': 'success',
            'error': 'danger',
            'cancelled': 'secondary'
        };
        return colors[status] || 'secondary';
    }

    // Format date
    function formatDate(dateString) {
        if (!dateString) return 'Never';
        return new Date(dateString).toLocaleString();
    }
});

// View shipment details
function viewShipment(id) {
    $.ajax({
        url: '{{ route("ops.details") }}',
        type: 'GET',
        data: { id: id },
        success: function(response) {
            $('#shipmentDetails').html(formatShipmentDetails(response.shipment, response.tracking_logs));
            $('#shipmentModal').modal('show');
        },
        error: function(xhr) {
            console.error('Error loading shipment details:', xhr.responseText);
        }
    });
}

// Sync tracking
function syncTracking(id) {
    $.ajax({
        url: '{{ route("ops.sync") }}',
        type: 'POST',
        data: { id: id },
        success: function(response) {
            if (response.success) {
                alert('Tracking sync initiated');
                loadShipments();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr) {
            console.error('Error syncing tracking:', xhr.responseText);
        }
    });
}

// Void shipment
function voidShipment(id) {
    if (confirm('Are you sure you want to void this shipment?')) {
        $.ajax({
            url: '{{ route("ops.void") }}',
            type: 'POST',
            data: { id: id },
            success: function(response) {
                if (response.success) {
                    alert('Shipment voided successfully');
                    loadShipments();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                console.error('Error voiding shipment:', xhr.responseText);
            }
        });
    }
}

// Re-ship
function reship(id) {
    if (confirm('Are you sure you want to re-ship this order?')) {
        $.ajax({
            url: '{{ route("ops.reship") }}',
            type: 'POST',
            data: { id: id },
            success: function(response) {
                if (response.success) {
                    alert('Re-ship initiated successfully');
                    loadShipments();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                console.error('Error re-shipping:', xhr.responseText);
            }
        });
    }
}

// Format shipment details for modal
function formatShipmentDetails(shipment, trackingLogs) {
    let html = `
        <div class="row">
            <div class="col-md-6">
                <h6>Shipment Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>Order #:</strong></td><td>${shipment.shopify_order_name}</td></tr>
                    <tr><td><strong>AWB:</strong></td><td>${shipment.ecofreight_awb || 'N/A'}</td></tr>
                    <tr><td><strong>Service:</strong></td><td>${shipment.service_type}</td></tr>
                    <tr><td><strong>Status:</strong></td><td><span class="badge badge-${getStatusBadgeColor(shipment.status)}">${shipment.status}</span></td></tr>
                    <tr><td><strong>COD:</strong></td><td>${shipment.cod_enabled ? 'AED ' + shipment.cod_amount : 'No'}</td></tr>
                    <tr><td><strong>Created:</strong></td><td>${formatDate(shipment.created_at)}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Tracking Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>Last Sync:</strong></td><td>${formatDate(shipment.last_checked_at)}</td></tr>
                    <tr><td><strong>Sync Attempts:</strong></td><td>${shipment.sync_attempts || 0}</td></tr>
                    <tr><td><strong>Stale:</strong></td><td>${shipment.stale_flag ? 'Yes' : 'No'}</td></tr>
                    ${shipment.delivered_at ? `<tr><td><strong>Delivered:</strong></td><td>${formatDate(shipment.delivered_at)}</td></tr>` : ''}
                </table>
            </div>
        </div>
    `;

    if (trackingLogs && trackingLogs.length > 0) {
        html += `
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Tracking Timeline</h6>
                    <div class="timeline">
        `;
        
        trackingLogs.forEach(function(log) {
            html += `
                <div class="timeline-item">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h6 class="timeline-title">${log.status}</h6>
                        <p class="timeline-text">${log.description || 'No description'}</p>
                        <small class="text-muted">${formatDate(log.timestamp)}</small>
                    </div>
                </div>
            `;
        });
        
        html += `
                    </div>
                </div>
            </div>
        `;
    }

    return html;
}
</script>
@endsection

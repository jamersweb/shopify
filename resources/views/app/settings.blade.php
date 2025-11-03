<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoFreight Settings - {{ $shopRecord->name }}</title>
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
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: #008060;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #008060;
            box-shadow: 0 0 0 2px rgba(0,128,96,0.2);
        }
        .form-row {
            display: flex;
            gap: 15px;
        }
        .form-row .form-group {
            flex: 1;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
        .btn {
            background: #008060;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
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
        .btn-test {
            background: #17a2b8;
        }
        .btn-test:hover {
            background: #138496;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
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
        .section {
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 1px solid #eee;
        }
        .section:last-child {
            border-bottom: none;
        }
        .section h3 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 18px;
        }
        .connection-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .connection-status.connected {
            background: #d4edda;
            color: #155724;
        }
        .connection-status.disconnected {
            background: #f8d7da;
            color: #721c24;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>EcoFreight Settings</h1>
            <p>{{ $shopRecord->name }}</p>
        </div>
        
        <div class="content">
            <div id="alerts"></div>
            
            <form id="settingsForm">
                <!-- EcoFreight Connection -->
                <div class="section">
                    <h3>EcoFreight Connection</h3>
                    
                    <div class="form-group">
                        <label for="ecofreight_base_url">Base URL</label>
                        <input type="text" id="ecofreight_base_url" name="ecofreight_base_url" 
                               value="{{ $settings->ecofreight_base_url ?? 'https://app.ecofreight.ae/en' }}" readonly>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="ecofreight_username">Username</label>
                            <input type="text" id="ecofreight_username" name="ecofreight_username" 
                                   value="{{ $settings->ecofreight_username ?? '' }}" required>
                        </div>
                        <div class="form-group">
                            <label for="ecofreight_password">Password</label>
                            <input type="password" id="ecofreight_password" name="ecofreight_password" 
                                   value="{{ $settings->ecofreight_password ?? '' }}" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="button" id="testConnection" class="btn btn-test">Test Connection</button>
                        <span id="connectionStatus" class="connection-status {{ $settings->connection_status ? 'connected' : 'disconnected' }}">
                            {{ $settings->connection_status ? 'Connected' : 'Disconnected' }}
                        </span>
                        @if($settings->last_connection_test)
                            <small style="margin-left: 10px; color: #666;">
                                Last tested: {{ $settings->last_connection_test->format('M j, Y g:i A') }}
                            </small>
                        @endif
                    </div>
                </div>

                <!-- Ship-from (Origin) Settings -->
                <div class="section">
                    <h3>Ship-from (Origin) Settings</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="ship_from_company">Company</label>
                            <input type="text" id="ship_from_company" name="ship_from_company" 
                                   value="{{ $settings->ship_from_company ?? '' }}" required>
                        </div>
                        <div class="form-group">
                            <label for="ship_from_contact">Contact Person</label>
                            <input type="text" id="ship_from_contact" name="ship_from_contact" 
                                   value="{{ $settings->ship_from_contact ?? '' }}" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="ship_from_phone">Phone</label>
                            <input type="tel" id="ship_from_phone" name="ship_from_phone" 
                                   value="{{ $settings->ship_from_phone ?? '' }}" required>
                        </div>
                        <div class="form-group">
                            <label for="ship_from_email">Email</label>
                            <input type="email" id="ship_from_email" name="ship_from_email" 
                                   value="{{ $settings->ship_from_email ?? '' }}" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="ship_from_address1">Address Line 1</label>
                        <input type="text" id="ship_from_address1" name="ship_from_address1" 
                               value="{{ $settings->ship_from_address1 ?? '' }}" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="ship_from_address2">Address Line 2</label>
                        <input type="text" id="ship_from_address2" name="ship_from_address2" 
                               value="{{ $settings->ship_from_address2 ?? '' }}">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="ship_from_city">City/Emirate</label>
                            <input type="text" id="ship_from_city" name="ship_from_city" 
                                   value="{{ $settings->ship_from_city ?? '' }}" required>
                        </div>
                        <div class="form-group">
                            <label for="ship_from_postcode">Postcode</label>
                            <input type="text" id="ship_from_postcode" name="ship_from_postcode" 
                                   value="{{ $settings->ship_from_postcode ?? '' }}">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="ship_from_country">Country</label>
                        <input type="text" id="ship_from_country" name="ship_from_country" 
                               value="{{ $settings->ship_from_country ?? 'UAE' }}" readonly>
                    </div>
                </div>

                <!-- Default Package Rules -->
                <div class="section">
                    <h3>Default Package Rules</h3>
                    
                    <div class="form-group">
                        <label for="default_weight">Default Weight (kg)</label>
                        <input type="number" id="default_weight" name="default_weight" 
                               value="{{ $settings->default_weight ?? 1.0 }}" step="0.1" min="0.1" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="default_length">Length (cm)</label>
                            <input type="number" id="default_length" name="default_length" 
                                   value="{{ $settings->default_length ?? 30 }}" min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="default_width">Width (cm)</label>
                            <input type="number" id="default_width" name="default_width" 
                                   value="{{ $settings->default_width ?? 20 }}" min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="default_height">Height (cm)</label>
                            <input type="number" id="default_height" name="default_height" 
                                   value="{{ $settings->default_height ?? 10 }}" min="1" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Packing Rule</label>
                        <div class="checkbox-group">
                            <input type="radio" id="packing_per_order" name="packing_rule" value="per_order" 
                                   {{ ($settings->packing_rule ?? 'per_order') === 'per_order' ? 'checked' : '' }}>
                            <label for="packing_per_order">One parcel per order</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="radio" id="packing_per_item" name="packing_rule" value="per_item" 
                                   {{ ($settings->packing_rule ?? 'per_order') === 'per_item' ? 'checked' : '' }}>
                            <label for="packing_per_item">One parcel per line item</label>
                        </div>
                    </div>
                </div>

                <!-- Services -->
                <div class="section">
                    <h3>Services</h3>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="use_standard_service" name="use_standard_service" 
                               {{ ($settings->use_standard_service ?? true) ? 'checked' : '' }}>
                        <label for="use_standard_service">Use EcoFreight Standard service</label>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="use_express_service" name="use_express_service" 
                               {{ ($settings->use_express_service ?? true) ? 'checked' : '' }}>
                        <label for="use_express_service">Use EcoFreight Express service</label>
                    </div>
                </div>

                <!-- COD Settings -->
                <div class="section">
                    <h3>COD Settings</h3>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="cod_enabled" name="cod_enabled" 
                               {{ ($settings->cod_enabled ?? false) ? 'checked' : '' }}>
                        <label for="cod_enabled">Enable COD (Cash on Delivery)</label>
                    </div>
                    
                    <div class="form-group">
                        <label for="cod_fee">COD Fee (AED)</label>
                        <input type="number" id="cod_fee" name="cod_fee" 
                               value="{{ $settings->cod_fee ?? 0 }}" step="0.01" min="0">
                    </div>
                </div>

                <!-- Price Adjustments -->
                <div class="section">
                    <h3>Price Adjustments (Internal Reference Only)</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="markup_percentage">Markup Percentage (%)</label>
                            <input type="number" id="markup_percentage" name="markup_percentage" 
                                   value="{{ $settings->markup_percentage ?? 0 }}" step="0.01" min="0" max="100">
                        </div>
                        <div class="form-group">
                            <label for="discount_percentage">Discount Percentage (%)</label>
                            <input type="number" id="discount_percentage" name="discount_percentage" 
                                   value="{{ $settings->discount_percentage ?? 0 }}" step="0.01" min="0" max="100">
                        </div>
                    </div>
                </div>

                <!-- Tracking Settings -->
                <div class="section">
                    <h3>Tracking Settings</h3>
                    
                    <div class="form-group">
                        <label for="tracking_url_template">Tracking URL Template</label>
                        <input type="text" id="tracking_url_template" name="tracking_url_template" 
                               value="{{ $settings->tracking_url_template ?? '' }}" 
                               placeholder="https://tracking.ecofreight.ae/track/{awb}">
                        <small>Use {awb} as placeholder for tracking number</small>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="auto_poll_tracking" name="auto_poll_tracking" 
                               {{ ($settings->auto_poll_tracking ?? true) ? 'checked' : '' }}>
                        <label for="auto_poll_tracking">Auto-poll tracking updates</label>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="poll_interval_hours">Poll Interval (hours)</label>
                            <select id="poll_interval_hours" name="poll_interval_hours">
                                <option value="1" {{ ($settings->poll_interval_hours ?? 2) == 1 ? 'selected' : '' }}>1 hour</option>
                                <option value="2" {{ ($settings->poll_interval_hours ?? 2) == 2 ? 'selected' : '' }}>2 hours</option>
                                <option value="4" {{ ($settings->poll_interval_hours ?? 2) == 4 ? 'selected' : '' }}>4 hours</option>
                                <option value="6" {{ ($settings->poll_interval_hours ?? 2) == 6 ? 'selected' : '' }}>6 hours</option>
                                <option value="12" {{ ($settings->poll_interval_hours ?? 2) == 12 ? 'selected' : '' }}>12 hours</option>
                                <option value="24" {{ ($settings->poll_interval_hours ?? 2) == 24 ? 'selected' : '' }}>24 hours</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="stop_after_days">Stop After (days)</label>
                            <input type="number" id="stop_after_days" name="stop_after_days" 
                                   value="{{ $settings->stop_after_days ?? 10 }}" min="1" max="30" required>
                        </div>
                    </div>
                </div>

                <!-- Alert Settings -->
                <div class="section">
                    <h3>Alert Settings</h3>
                    
                    <div class="form-group">
                        <label for="error_alert_emails">Error Alert Emails</label>
                        <input type="text" id="error_alert_emails" name="error_alert_emails" 
                               value="{{ $settings->error_alert_emails ?? '' }}" 
                               placeholder="admin@example.com, support@example.com">
                        <small>Comma-separated list of email addresses</small>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="include_awb_in_alerts" name="include_awb_in_alerts" 
                               {{ ($settings->include_awb_in_alerts ?? true) ? 'checked' : '' }}>
                        <label for="include_awb_in_alerts">Include AWB in error alerts</label>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn">Save Settings</button>
                    <button type="button" id="resetDefaults" class="btn btn-secondary">Reset to Defaults</button>
                </div>
            </form>
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

        // Test connection
        document.getElementById('testConnection').addEventListener('click', async function() {
            showLoading();
            
            try {
                const response = await fetch('/app/test-connection?shop={{ $shop }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                });
                
                const result = await response.json();
                
                const statusElement = document.getElementById('connectionStatus');
                if (result.success) {
                    statusElement.textContent = 'Connected';
                    statusElement.className = 'connection-status connected';
                    showAlert('Connection test successful!', 'success');
                } else {
                    statusElement.textContent = 'Disconnected';
                    statusElement.className = 'connection-status disconnected';
                    showAlert('Connection test failed: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Connection test failed: ' + error.message, 'error');
            } finally {
                hideLoading();
            }
        });

        // Save settings
        document.getElementById('settingsForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            showLoading();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            // Convert checkboxes to boolean
            data.use_standard_service = document.getElementById('use_standard_service').checked;
            data.use_express_service = document.getElementById('use_express_service').checked;
            data.cod_enabled = document.getElementById('cod_enabled').checked;
            data.auto_poll_tracking = document.getElementById('auto_poll_tracking').checked;
            data.include_awb_in_alerts = document.getElementById('include_awb_in_alerts').checked;
            
            try {
                const response = await fetch('/app/settings?shop={{ $shop }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Settings saved successfully!', 'success');
                } else {
                    showAlert('Failed to save settings: ' + (result.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                showAlert('Failed to save settings: ' + error.message, 'error');
            } finally {
                hideLoading();
            }
        });

        // Reset to defaults
        document.getElementById('resetDefaults').addEventListener('click', function() {
            if (confirm('Are you sure you want to reset all settings to defaults? This will not affect your EcoFreight credentials.')) {
                // Reset form to default values
                document.getElementById('default_weight').value = '1.0';
                document.getElementById('default_length').value = '30';
                document.getElementById('default_width').value = '20';
                document.getElementById('default_height').value = '10';
                document.getElementById('packing_per_order').checked = true;
                document.getElementById('use_standard_service').checked = true;
                document.getElementById('use_express_service').checked = true;
                document.getElementById('cod_enabled').checked = false;
                document.getElementById('cod_fee').value = '0';
                document.getElementById('markup_percentage').value = '0';
                document.getElementById('discount_percentage').value = '0';
                document.getElementById('auto_poll_tracking').checked = true;
                document.getElementById('poll_interval_hours').value = '2';
                document.getElementById('stop_after_days').value = '10';
                document.getElementById('include_awb_in_alerts').checked = true;
                
                showAlert('Settings reset to defaults. Click Save to apply changes.', 'success');
            }
        });
    </script>
</body>
</html>

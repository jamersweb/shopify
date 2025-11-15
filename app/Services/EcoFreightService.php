<?php

namespace App\Services;

use App\Models\ShopSetting;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class EcoFreightService
{
    protected $client;
    protected $settings;

    public function __construct(ShopSetting $settings = null)
    {
        $this->settings = $settings;
        $baseUrl = $settings ? $settings->ecofreight_base_url : config('ecofreight.base_url');
        
        // Ensure base URL doesn't have trailing slash
        $baseUrl = rtrim($baseUrl, '/');
        
        $this->client = new Client([
            'base_uri' => $baseUrl,
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Test the connection to EcoFreight API and retrieve bearer token.
     */
    public function testConnection(string $username = null, string $password = null): array
    {
        try {
            $username = $username ?: $this->settings->ecofreight_username;
            $password = $password ?: $this->settings->ecofreight_password;

            // Check if credentials are provided
            if (!$username || !$password) {
                return [
                    'success' => false,
                    'message' => 'Username and password are required for connection test',
                    'data' => null,
                ];
            }

            // Get the auth endpoint from config
            $authEndpoint = config('ecofreight.endpoints.auth', '/api/auth');
            
            // Get base URL to check if it already includes /en
            $baseUrl = $this->settings ? $this->settings->ecofreight_base_url : config('ecofreight.base_url');
            $baseUrl = rtrim($baseUrl, '/');
            
            // Try endpoints - first without /en, then with /en (in case base URL doesn't include it)
            $endpointsToTry = [
                $authEndpoint,  // Try /api/auth first
                '/en' . $authEndpoint,  // Then try /en/api/auth
            ];
            
            $lastException = null;
            foreach ($endpointsToTry as $endpoint) {
                try {
                    $response = $this->client->post($endpoint, [
                        'json' => [
                            'username' => $username,
                            'password' => $password,
                        ],
                    ]);
                    
                    // If we get here, the request succeeded
                    $data = json_decode($response->getBody()->getContents(), true);
                    
                    if ($response->getStatusCode() === 200 && isset($data['data']['token'])) {
                        // Store bearer token in settings if settings is available
                        if ($this->settings) {
                            $this->settings->ecofreight_bearer_token = $data['data']['token'];
                            $this->settings->last_connection_test = now();
                            $this->settings->connection_status = true;
                            $this->settings->save();
                        }
                        
                        return [
                            'success' => true,
                            'message' => 'Connection successful',
                            'data' => $data,
                            'token' => $data['data']['token'],
                        ];
                    }
                    
                    // If status is 200 but no token, return failure
                    return [
                        'success' => false,
                        'message' => 'Authentication failed: Token not received',
                        'data' => $data,
                    ];
                } catch (RequestException $e) {
                    $lastException = $e;
                    // Continue to next endpoint
                    continue;
                }
            }
            
            // If all endpoints failed, handle the exception
            if ($lastException) {
                $statusCode = $lastException->getResponse() ? $lastException->getResponse()->getStatusCode() : null;
                $errorMessage = $lastException->getMessage();
                
                Log::error('EcoFreight connection test failed', [
                    'error' => $errorMessage,
                    'code' => $lastException->getCode(),
                    'status_code' => $statusCode,
                    'url' => $lastException->getRequest() ? $lastException->getRequest()->getUri() : null,
                    'tried_endpoints' => $endpointsToTry,
                ]);

                // Provide more specific error messages
                if ($statusCode === 404) {
                    return [
                        'success' => false,
                        'message' => 'API endpoint not found. Tried: ' . implode(', ', $endpointsToTry) . '. Please verify the EcoFreight API base URL and endpoint are correct.',
                        'data' => null,
                    ];
                } elseif ($statusCode === 401) {
                    return [
                        'success' => false,
                        'message' => 'Authentication failed. Please check your username and password.',
                        'data' => null,
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Connection failed: ' . $errorMessage,
                        'data' => null,
                    ];
                }
            }
            
            // This should never be reached, but just in case
            return [
                'success' => false,
                'message' => 'Connection test failed: No valid endpoint found',
                'data' => null,
            ];
        } catch (\Exception $e) {
            Log::error('EcoFreight connection test exception', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Create a shipment in EcoFreight.
     */
    public function createShipment(array $shipmentData): array
    {
        try {
            // Get bearer token - try shop settings first, then fallback to production token from config
            $bearerToken = null;
            
            if ($this->settings) {
                $bearerToken = $this->settings->ecofreight_bearer_token;
            }
            
            // Fallback to production token from config
            if (!$bearerToken) {
                $bearerToken = config('ecofreight.production_token');
            }
            
            // Log token status (without exposing the actual token)
            Log::info('EcoFreight createShipment - Token check', [
                'has_settings' => $this->settings ? 'yes' : 'no',
                'has_token_in_settings' => ($this->settings && $this->settings->ecofreight_bearer_token) ? 'yes' : 'no',
                'has_production_token' => config('ecofreight.production_token') ? 'yes' : 'no',
                'token_length' => $bearerToken ? strlen($bearerToken) : 0,
            ]);
            
            if (!$bearerToken) {
                Log::error('EcoFreight bearer token not found, attempting to retrieve');
                
                // Try to get token by calling testConnection
                if ($this->settings) {
                    $connectionResult = $this->testConnection();
                    if (!$connectionResult['success']) {
                        return [
                            'success' => false,
                            'message' => 'Bearer token not available. Please test connection first or set production token.',
                            'data' => null,
                        ];
                    }
                    $bearerToken = $this->settings->ecofreight_bearer_token ?? config('ecofreight.production_token');
                } else {
                    return [
                        'success' => false,
                        'message' => 'Bearer token not available. Settings not provided and production token not configured.',
                        'data' => null,
                    ];
                }
            }
            
            if (!$bearerToken) {
                return [
                    'success' => false,
                    'message' => 'Bearer token is required but not found. Please test connection or set production token.',
                    'data' => null,
                ];
            }
            
            // Ensure shipmentData is wrapped in an array (API expects array of orders)
            $payload = is_array($shipmentData) && isset($shipmentData[0]) ? $shipmentData : [$shipmentData];
            
            // Format Authorization header - add Bearer prefix if not present
            $authHeader = $bearerToken;
            // If token doesn't start with "Bearer ", add it
            if (strpos($bearerToken, 'Bearer ') !== 0) {
                $authHeader = 'Bearer ' . $bearerToken;
            }
            
            // Use the new v2 API endpoint
            $response = $this->client->post('/v2/api/client/order', [
                'json' => $payload,
                'headers' => [
                    'Authorization' => $authHeader,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() === 201 || $response->getStatusCode() === 200) {
                // Check for success response - API returns status: 1 for success, 0 for failure
                if (isset($data['status']) && $data['status'] === 1) {
                    // Check if there are valid orders
                    $validOrders = $data['data']['valid_order'] ?? [];
                    $invalidOrders = $data['data']['in_valid_order'] ?? [];
                    
                    // If there are valid orders, it's a success
                    if (!empty($validOrders)) {
                        return [
                            'success' => true,
                            'message' => $data['message'] ?? 'Shipment created successfully',
                            'data' => $data,
                            'valid_orders' => $validOrders,
                            'invalid_orders' => $invalidOrders,
                        ];
                    }
                    
                    // If there are only invalid orders, it's a failure
                    if (!empty($invalidOrders)) {
                        // Extract error message from first invalid order
                        $firstInvalidOrder = is_array($invalidOrders[0]) ? $invalidOrders[0] : [];
                        $errorMessage = $firstInvalidOrder['message'] ?? $data['message'] ?? 'Shipment creation failed';
                        return [
                            'success' => false,
                            'message' => $errorMessage,
                            'data' => $data,
                            'valid_orders' => $validOrders,
                            'invalid_orders' => $invalidOrders,
                        ];
                    }
                    
                    // If status is 1 but no orders in response, still consider it success
                    return [
                        'success' => true,
                        'message' => $data['message'] ?? 'Shipment created successfully',
                        'data' => $data,
                    ];
                }
                
                // Status is 0 or not set - failure
                $errorMessage = $data['message'] ?? 'Shipment creation failed';
                if (isset($data['data']['in_valid_order']) && !empty($data['data']['in_valid_order'])) {
                    $errorMessage = $data['data']['in_valid_order'][0]['message'] ?? $errorMessage;
                }
                
                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Shipment creation failed',
                'data' => $data,
            ];
        } catch (RequestException $e) {
            $responseBody = null;
            if ($e->getResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
            }
            
            Log::error('EcoFreight shipment creation failed', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'status_code' => $e->getResponse() ? $e->getResponse()->getStatusCode() : null,
                'response_body' => $responseBody,
                'shipment_data' => $this->redactSensitiveData($shipmentData),
            ]);

            return [
                'success' => false,
                'message' => 'Shipment creation failed: ' . $e->getMessage(),
                'data' => $responseBody ? json_decode($responseBody, true) : null,
            ];
        }
    }

    /**
     * Get shipment label from EcoFreight.
     */
    public function getShipmentLabel(string $awb): array
    {
        try {
            $response = $this->client->get("/api/shipments/{$awb}/label", [
                'auth' => [
                    $this->settings->ecofreight_username,
                    $this->settings->ecofreight_password,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() === 200) {
                return [
                    'success' => true,
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'message' => 'Label retrieval failed',
                'data' => $data,
            ];
        } catch (RequestException $e) {
            Log::error('EcoFreight label retrieval failed', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'awb' => $awb,
            ]);

            return [
                'success' => false,
                'message' => 'Label retrieval failed: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Track shipment in EcoFreight.
     */
    public function trackShipment(string $awb): array
    {
        try {
            $response = $this->client->get("/api/shipments/{$awb}/track", [
                'auth' => [
                    $this->settings->ecofreight_username,
                    $this->settings->ecofreight_password,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() === 200) {
                return [
                    'success' => true,
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'message' => 'Tracking failed',
                'data' => $data,
            ];
        } catch (RequestException $e) {
            Log::error('EcoFreight tracking failed', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'awb' => $awb,
            ]);

            return [
                'success' => false,
                'message' => 'Tracking failed: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Cancel shipment in EcoFreight.
     */
    public function cancelShipment(string $awb): array
    {
        try {
            $response = $this->client->delete("/api/shipments/{$awb}/cancel", [
                'auth' => [
                    $this->settings->ecofreight_username,
                    $this->settings->ecofreight_password,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() === 200) {
                return [
                    'success' => true,
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'message' => 'Shipment cancellation failed',
                'data' => $data,
            ];
        } catch (RequestException $e) {
            Log::error('EcoFreight shipment cancellation failed', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'awb' => $awb,
            ]);

            return [
                'success' => false,
                'message' => 'Shipment cancellation failed: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Normalize country name to full format expected by EcoFreight API.
     */
    protected function normalizeCountryName(?string $country): string
    {
        // Country name mapping - convert abbreviations and variations to full names
        $countryMap = [
            'UAE' => 'United Arab Emirates',
            'U.A.E' => 'United Arab Emirates',
            'U.A.E.' => 'United Arab Emirates',
            'United Arab Emirates' => 'United Arab Emirates',
            'AE' => 'United Arab Emirates',
            'ARE' => 'United Arab Emirates',
            // Add more mappings as needed
        ];
        
        // Handle null or empty
        if (empty($country)) {
            Log::warning('EcoFreight: Empty country name, defaulting to United Arab Emirates');
            return 'United Arab Emirates';
        }
        
        $country = trim($country);
        $countryUpper = strtoupper($country);
        
        // Check if we have a mapping
        if (isset($countryMap[$countryUpper])) {
            $normalized = $countryMap[$countryUpper];
            if ($normalized !== $country) {
                Log::info('EcoFreight: Country name normalized', [
                    'original' => $country,
                    'normalized' => $normalized,
                ]);
            }
            return $normalized;
        }
        
        // If it's already a known full name, return as is
        if (in_array($country, $countryMap)) {
            return $country;
        }
        
        // Log if we're using an unmapped country name
        Log::info('EcoFreight: Using unmapped country name', [
            'country' => $country,
        ]);
        
        // Return the country as-is if no mapping found (might be valid)
        return $country;
    }

    /**
     * Build shipment payload from order data and settings.
     */
    public function buildShipmentPayload(array $orderData, ShopSetting $settings): array
    {
        $shipTo = $orderData['shipping_address'];
        $shipFrom = $settings; // Use settings for shipper details

        // Determine service type from shipping rate title (fallback to normal_delivery if not found)
        $serviceType = $this->mapServiceType($orderData['shipping_lines'][0]['title'] ?? 'Standard');

        // Build package details with item details
        $packageDetails = [];
        $totalWeight = 0;
        $totalQuantity = 0;
        $itemDescriptions = [];
        
        foreach ($orderData['line_items'] as $index => $item) {
            // Get weight from item - check both 'weight' (kg) and 'grams'
            // Priority: 'weight' (kg) if set, then 'grams', then default
            $itemWeightKg = null;
            $weightSource = 'none';
            
            // First check if 'weight' (in kg) is explicitly set
            if (isset($item['weight']) && $item['weight'] !== null) {
                $itemWeightKg = floatval($item['weight']);
                $weightSource = 'weight_key';
            } elseif (isset($item['grams']) && $item['grams'] !== null) {
                // Convert grams to kg
                $itemWeightKg = floatval($item['grams']) / 1000;
                $weightSource = 'grams_key';
            }
            
            // If weight is missing, zero, or invalid, use default weight
            // But only if weight wasn't explicitly set by user (check if 'weight' key exists)
            if ($itemWeightKg === null || ($itemWeightKg <= 0 && !isset($item['weight']))) {
                $itemWeightKg = $settings->default_weight ?? 0.4; // Use default weight (400g)
                $weightSource = 'default';
            }
            
            // Ensure minimum weight of 0.01 kg (10g) if weight was set but is too low
            if ($itemWeightKg > 0 && $itemWeightKg < 0.01) {
                $itemWeightKg = 0.01;
            }
            
            // Log weight calculation for debugging
            Log::debug('EcoFreight buildShipmentPayload - Item weight calculation', [
                'item_index' => $index,
                'item_title' => $item['title'] ?? 'N/A',
                'weight_kg' => $itemWeightKg,
                'weight_source' => $weightSource,
                'has_weight_key' => isset($item['weight']),
                'weight_value' => $item['weight'] ?? null,
                'has_grams_key' => isset($item['grams']),
                'grams_value' => $item['grams'] ?? null,
            ]);
            
            $quantity = $item['quantity'] ?? 1;
            $totalWeight += $itemWeightKg * $quantity;
            $totalQuantity += $quantity;
            $itemDescriptions[] = $item['title'];
            
            // Build item details for this line item
            $itemDetails = [];
            
            // Extract HS code from properties (Shopify properties are array of objects with 'name' and 'value')
            $hsCode = '';
            if (isset($item['properties']) && is_array($item['properties'])) {
                foreach ($item['properties'] as $property) {
                    if (isset($property['name']) && strtolower($property['name']) === 'hs_code') {
                        $hsCode = $property['value'] ?? '';
                        break;
                    }
                }
            }
            
            for ($i = 0; $i < $quantity; $i++) {
                $itemDetails[] = [
                    'item_code' => $item['sku'] ?? (string)($item['variant_id'] ?? ''),
                    'hs_code' => $hsCode,
                    'description' => $item['title'],
                    'quantity' => '1',
                ];
            }
            
            // Get dimensions (default if not available)
            $length = $item['dimensions']['length'] ?? ($settings->default_dimensions['length'] ?? 10);
            $width = $item['dimensions']['width'] ?? ($settings->default_dimensions['width'] ?? 10);
            $height = $item['dimensions']['height'] ?? ($settings->default_dimensions['height'] ?? 10);
            
            // Calculate package weight (ensure it's at least default weight per item)
            $packageWeight = $itemWeightKg * $quantity;
            // Ensure minimum weight per package (at least default weight)
            $minPackageWeight = ($settings->default_weight ?? 0.4) * $quantity;
            if ($packageWeight < $minPackageWeight) {
                $packageWeight = $minPackageWeight;
            }
            
            $packageDetails[] = [
                'description' => $item['title'],
                'quantity' => (string)$quantity,
                'weight' => (string)round($packageWeight, 2),
                'height' => (string)$height,
                'width' => (string)$width,
                'length' => (string)$length,
                'dimension_units' => 'CM',
                'item_details' => $itemDetails,
            ];
        }
        
        // Ensure all packages have valid weight (fix any that are 0 or too low)
        foreach ($packageDetails as &$package) {
            $packageWeight = floatval($package['weight']);
            $minWeight = $settings->default_weight ?? 0.4;
            
            // If weight is 0 or too low, use default weight per item
            if ($packageWeight <= 0) {
                $quantity = intval($package['quantity'] ?? 1);
                $package['weight'] = (string)round($minWeight * $quantity, 2);
            } elseif ($packageWeight < 0.01) {
                // Ensure minimum 0.01 kg (10g)
                $package['weight'] = '0.01';
            }
        }
        unset($package); // Break reference
        
        // If no line items, create a default package
        if (empty($packageDetails)) {
            $itemDescription = implode(', ', $itemDescriptions) ?: 'General Goods';
            $defaultWeight = $settings->default_weight ?? 0.4;
            $defaultDimensions = $settings->default_dimensions ?? ['length' => 10, 'width' => 10, 'height' => 10];
            
            // Ensure minimum weight
            if ($defaultWeight <= 0) {
                $defaultWeight = 0.4; // 400g minimum
            }
            
            $packageDetails[] = [
                'description' => $itemDescription,
                'quantity' => '1',
                'weight' => (string)round($defaultWeight, 2),
                'height' => (string)$defaultDimensions['height'],
                'width' => (string)$defaultDimensions['width'],
                'length' => (string)$defaultDimensions['length'],
                'dimension_units' => 'CM',
                'item_details' => [
                    [
                        'item_code' => '',
                        'hs_code' => '',
                        'description' => $itemDescription,
                        'quantity' => '1',
                    ],
                ],
            ];
        }

        // Build EcoFreight v2 API payload with new structure
        $payload = [
            'order_reference' => $orderData['name'] ?? (string)($orderData['order_number'] ?? ''),
            'service_type' => $serviceType === 'Express' ? 'express_delivery' : 'normal_delivery',
            'product_type' => 'non_document',
            'customer_service_type' => 'B2C', // Default to B2C, can be configured in settings if needed
            'schedule_delivery_date' => '',
            'shipment_value' => floatval($orderData['subtotal_price'] ?? 0),
            'currency' => $orderData['currency'] ?? 'AED',
            'request_type' => '1',
            'delivery_attempt_mode' => '1',
            'location' => [
                'lat' => isset($shipTo['latitude']) ? (string)$shipTo['latitude'] : '',
                'lon' => isset($shipTo['longitude']) ? (string)$shipTo['longitude'] : '',
            ],
            'shipper_details' => [
                'company_name' => $shipFrom->ship_from_company ?? '',
                'sender_name' => $shipFrom->ship_from_contact ?? '',
                'address' => $shipFrom->ship_from_address1 ?? '',
                'city' => $shipFrom->ship_from_city ?? '',
                'country' => $this->normalizeCountryName($shipFrom->ship_from_country ?? 'United Arab Emirates'),
                'email' => $shipFrom->ship_from_email ?? '',
                'mobile_no' => $shipFrom->ship_from_phone ?? '',
                'alt_mobile_no' => '',
            ],
            'consignee_details' => [
                'company_name' => $shipTo['company'] ?? '',
                'receiver_name' => $shipTo['name'] ?? ($shipTo['first_name'] . ' ' . ($shipTo['last_name'] ?? '')),
                'email' => $shipTo['email'] ?? '',
                'address' => $shipTo['address1'] ?? '',
                'city' => $shipTo['city'] ?? '',
                'country' => $this->normalizeCountryName($shipTo['country'] ?? 'United Arab Emirates'),
                'mobile_no' => $shipTo['phone'] ?? '',
                'alt_mobile_no' => $shipTo['phone2'] ?? '',
            ],
            'package_details' => $packageDetails,
        ];

        // Add COD if enabled
        if ($settings->cod_enabled) {
            $codAmount = floatval($orderData['total_price'] ?? 0) + floatval($settings->cod_fee ?? 0);
            $payload['cod'] = [
                'amount' => (string)$codAmount,
                'payment_mode' => $settings->cod_payment_mode ?? 'any',
            ];
        }

        // Add customs payment if needed
        if (isset($orderData['customs_payment_mode'])) {
            $payload['customs_payment'] = [
                'payment_mode' => $orderData['customs_payment_mode'],
            ];
        } else {
            $payload['customs_payment'] = [
                'payment_mode' => 'DDP',
            ];
        }

        // Add address2 if available
        if (!empty($shipTo['address2'])) {
            $payload['consignee_details']['address'] .= ', ' . $shipTo['address2'];
        }

        // Validate and log country names before validation
        $shipperCountry = $payload['shipper_details']['country'] ?? '';
        $consigneeCountry = $payload['consignee_details']['country'] ?? '';
        
        Log::info('EcoFreight buildShipmentPayload - Country names', [
            'shipper_country' => $shipperCountry,
            'consignee_country' => $consigneeCountry,
        ]);
        
        // Validate payload before returning
        $this->validatePayload($payload);

        return $payload;
    }

    /**
     * Validate shipment payload before sending to EcoFreight.
     */
    protected function validatePayload(array $payload): void
    {
        $requiredFields = [
            'order_reference',
            'service_type',
            'product_type',
            'customer_service_type',
            'shipper_details.company_name',
            'shipper_details.sender_name',
            'shipper_details.address',
            'shipper_details.city',
            'shipper_details.mobile_no',
            'consignee_details.receiver_name',
            'consignee_details.address',
            'consignee_details.city',
            'consignee_details.mobile_no',
            'package_details',
        ];

        foreach ($requiredFields as $field) {
            $value = data_get($payload, $field);
            if (empty($value) && $value !== '0') {
                throw new \InvalidArgumentException("Required field '{$field}' is missing or empty in shipment payload");
            }
        }

        // Validate package_details
        if (empty($payload['package_details']) || !is_array($payload['package_details'])) {
            throw new \InvalidArgumentException("package_details must be a non-empty array");
        }

        // Validate each package
        foreach ($payload['package_details'] as $index => $package) {
            if (empty($package['description'])) {
                throw new \InvalidArgumentException("Package {$index}: description is required");
            }
            if (empty($package['quantity']) || !is_numeric($package['quantity']) || $package['quantity'] <= 0) {
                throw new \InvalidArgumentException("Package {$index}: quantity must be a valid number > 0");
            }
            if (empty($package['weight']) || !is_numeric($package['weight']) || floatval($package['weight']) <= 0) {
                throw new \InvalidArgumentException("Package {$index}: weight must be a valid number > 0. Current value: " . ($package['weight'] ?? 'empty'));
            }
            
            // Ensure weight is at least 0.01 kg (10g minimum)
            $packageWeight = floatval($package['weight']);
            if ($packageWeight < 0.01) {
                $package['weight'] = '0.01'; // Set minimum weight
            }
        }
    }

    /**
     * Map Shopify shipping rate title to EcoFreight service type.
     */
    protected function mapServiceType(string $rateTitle): string
    {
        $rateTitle = strtolower($rateTitle);
        
        if (str_contains($rateTitle, 'express')) {
            return 'Express'; // Will be converted to 'express_delivery' in buildShipmentPayload
        }
        
        return 'Standard'; // Will be converted to 'normal_delivery' in buildShipmentPayload
    }

    /**
     * Redact sensitive data from logs.
     */
    public function redactSensitiveData(array $data): array
    {
        // Handle array of orders
        if (isset($data[0]) && is_array($data[0])) {
            foreach ($data as $index => $order) {
                $data[$index] = $this->redactOrderData($order);
            }
            return $data;
        }
        
        return $this->redactOrderData($data);
    }

    /**
     * Redact sensitive data from a single order.
     */
    protected function redactOrderData(array $order): array
    {
        $sensitiveFields = [
            'shipper_details.mobile_no',
            'shipper_details.alt_mobile_no',
            'shipper_details.email',
            'shipper_details.address',
            'consignee_details.mobile_no',
            'consignee_details.alt_mobile_no',
            'consignee_details.email',
            'consignee_details.address',
        ];
        
        foreach ($sensitiveFields as $field) {
            if (data_get($order, $field) !== null) {
                data_set($order, $field, '[REDACTED]');
            }
        }

        return $order;
    }
}

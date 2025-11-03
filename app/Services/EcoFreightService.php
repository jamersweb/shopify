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
        $this->client = new Client([
            'base_uri' => $settings ? $settings->ecofreight_base_url : config('ecofreight.base_url'),
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

            // Note: The base_uri already includes /en, so we just use /api/auth
            $response = $this->client->post('/api/auth', [
                'json' => [
                    'username' => $username,
                    'password' => $password,
                ],
            ]);

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

            return [
                'success' => false,
                'message' => 'Authentication failed',
                'data' => $data,
            ];
        } catch (RequestException $e) {
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : null;
            $errorMessage = $e->getMessage();
            
            Log::error('EcoFreight connection test failed', [
                'error' => $errorMessage,
                'code' => $e->getCode(),
                'status_code' => $statusCode,
                'url' => $e->getRequest() ? $e->getRequest()->getUri() : null,
            ]);

            // Provide more specific error messages
            if ($statusCode === 404) {
                return [
                    'success' => false,
                    'message' => 'API endpoint not found. Please verify that the EcoFreight API endpoint "/api/auth" is correct.',
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
    }

    /**
     * Create a shipment in EcoFreight.
     */
    public function createShipment(array $shipmentData): array
    {
        try {
            // Get bearer token
            $bearerToken = $this->settings->ecofreight_bearer_token;
            
            if (!$bearerToken) {
                Log::error('EcoFreight bearer token not found, attempting to retrieve');
                
                // Try to get token by calling testConnection
                $connectionResult = $this->testConnection();
                if (!$connectionResult['success']) {
                    return [
                        'success' => false,
                        'message' => 'Bearer token not available. Please test connection first.',
                        'data' => null,
                    ];
                }
                $bearerToken = $this->settings->ecofreight_bearer_token;
            }
            
            $response = $this->client->post('/api/create-order', [
                'json' => $shipmentData,
                'headers' => [
                    'Authorization' => 'Bearer ' . $bearerToken,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() === 201 || $response->getStatusCode() === 200) {
                // EcoFreight v2 API returns status: success
                if (isset($data['status']) && $data['status'] === 'success') {
                    return [
                        'success' => true,
                        'data' => $data,
                    ];
                }
            }

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Shipment creation failed',
                'data' => $data,
            ];
        } catch (RequestException $e) {
            Log::error('EcoFreight shipment creation failed', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'shipment_data' => $this->redactSensitiveData($shipmentData),
            ]);

            return [
                'success' => false,
                'message' => 'Shipment creation failed: ' . $e->getMessage(),
                'data' => null,
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
     * Build shipment payload from order data and settings.
     */
    public function buildShipmentPayload(array $orderData, ShopSetting $settings): array
    {
        $shipTo = $orderData['shipping_address'];

        // Determine service type from shipping rate title (fallback to normal_delivery if not found)
        $serviceType = $this->mapServiceType($orderData['shipping_lines'][0]['title'] ?? 'Standard');

        // Calculate total weight and quantity
        $totalWeight = 0;
        $totalQuantity = 0;
        $itemDescriptions = [];
        
        foreach ($orderData['line_items'] as $item) {
            $weight = $item['weight'] ?? $settings->default_weight;
            $totalWeight += $weight * $item['quantity'];
            $totalQuantity += $item['quantity'];
            $itemDescriptions[] = $item['title'];
        }
        
        // Build description from item titles
        $itemDescription = implode(', ', $itemDescriptions);

        // Build EcoFreight v2 API payload
        $payload = [
            'order_reference' => $orderData['name'] ?? $orderData['order_number'],
            'service_type' => $serviceType === 'Express' ? 'express_delivery' : 'normal_delivery',
            'product_type' => 'non_document',
            'consignee_name' => $shipTo['name'] ?? ($shipTo['first_name'] . ' ' . $shipTo['last_name']),
            'consignee_address' => $shipTo['address1'],
            'consignee_city' => $shipTo['city'],
            'consignee_mobile_no' => $shipTo['phone'],
            'consignee_alt_mobile_no' => $shipTo['phone2'] ?? '',
        ];

        // Add COD if enabled
        if ($settings->cod_enabled) {
            $payload['cod'] = floatval($orderData['total_price']) + floatval($settings->cod_fee);
            $payload['cod_payment_mode'] = 'cash_only';
        }

        // Add item details
        $payload['item_description'] = $itemDescription;
        $payload['item_quantity'] = $totalQuantity;
        $payload['item_weight'] = round($totalWeight, 2);
        $payload['shipment_value'] = floatval($orderData['subtotal_price']);

        // Add optional fields
        if (!empty($shipTo['address2'])) {
            $payload['consignee_address'] .= ', ' . $shipTo['address2'];
        }

        // Add special instructions if available (from order notes)
        if (!empty($orderData['note'])) {
            $payload['special_instruction'] = $orderData['note'];
        }

        // Customer service type (C2C is default)
        $payload['customer_service_type'] = 'C2C';

        // Add coordinates if available
        if (isset($shipTo['latitude']) && isset($shipTo['longitude'])) {
            $payload['lat'] = floatval($shipTo['latitude']);
            $payload['lon'] = floatval($shipTo['longitude']);
        }

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
            'consignee_name',
            'consignee_address',
            'consignee_city',
            'consignee_mobile_no',
            'item_description',
            'item_quantity',
            'item_weight',
        ];

        foreach ($requiredFields as $field) {
            $value = data_get($payload, $field);
            if (empty($value)) {
                throw new \InvalidArgumentException("Required field '{$field}' is missing or empty in shipment payload");
            }
        }

        // Validate item_weight
        if (!is_numeric($payload['item_weight']) || $payload['item_weight'] <= 0) {
            throw new \InvalidArgumentException("Item weight must be a valid number > 0");
        }

        // Validate item_quantity
        if (!is_numeric($payload['item_quantity']) || $payload['item_quantity'] <= 0) {
            throw new \InvalidArgumentException("Item quantity must be a valid number > 0");
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
    protected function redactSensitiveData(array $data): array
    {
        $sensitiveFields = [
            'consignee_mobile_no',
            'consignee_alt_mobile_no',
            'consignee_address',
        ];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        return $data;
    }
}

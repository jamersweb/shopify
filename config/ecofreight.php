<?php

return [
    /*
    |--------------------------------------------------------------------------
    | EcoFreight API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the EcoFreight API integration including base URL,
    | sandbox credentials, and API settings.
    |
    */

    'base_url' => env('ECOFREIGHT_BASE_URL', 'https://app.ecofreight.ae'),
    'sandbox_username' => env('ECOFREIGHT_SANDBOX_USERNAME', 'apitesting'),
    'sandbox_password' => env('ECOFREIGHT_SANDBOX_PASSWORD', 'apitesting'),
    
    /*
    |--------------------------------------------------------------------------
    | Production Bearer Token
    |--------------------------------------------------------------------------
    |
    | Production JWT token for EcoFreight API. This can be used as a fallback
    | if shop-specific tokens are not available.
    |
    */
    'production_token' => env('ECOFREIGHT_PRODUCTION_TOKEN', null),

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    |
    | EcoFreight API endpoints for different operations.
    |
    */

    'endpoints' => [
        'auth' => '/api/auth',
        'create_shipment' => '/v2/api/client/order',
        'get_label' => '/api/shipments/{awb}/label',
        'track_shipment' => '/api/shipments/{awb}/track',
        'cancel_shipment' => '/api/shipments/{awb}/cancel',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | Default values for shipment creation and tracking.
    |
    */

    'defaults' => [
        'weight' => 1.0, // kg
        'dimensions' => [
            'length' => 30, // cm
            'width' => 20,  // cm
            'height' => 10, // cm
        ],
        'packing_rule' => 'per_order', // per_order or per_item
        'services' => [
            'standard' => true,
            'express' => true,
        ],
        'cod' => [
            'enabled' => false,
            'fee' => 0,
        ],
        'tracking' => [
            'auto_poll' => true,
            'poll_interval' => 2, // hours
            'stop_after_days' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Mapping
    |--------------------------------------------------------------------------
    |
    | Mapping between Shopify shipping rate titles and EcoFreight services.
    |
    */

    'service_mapping' => [
        'express' => 'Express',
        'standard' => 'Standard',
    ],

    /*
    |--------------------------------------------------------------------------
    | Status Mapping
    |--------------------------------------------------------------------------
    |
    | Mapping between EcoFreight tracking statuses and Shopify fulfillment statuses.
    |
    */

    'status_mapping' => [
        'picked_up' => 'in_transit',
        'in_transit' => 'in_transit',
        'out_for_delivery' => 'in_transit',
        'delivered' => 'fulfilled',
        'exception' => 'cancelled',
        'cancelled' => 'cancelled',
    ],
];

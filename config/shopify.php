<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Shopify App Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Shopify app integration including API credentials,
    | scopes, and app settings.
    |
    */

    'api_key' => env('SHOPIFY_API_KEY'),
    'api_secret' => env('SHOPIFY_API_SECRET'),
    'scopes' => env('SHOPIFY_SCOPES', 'write_fulfillments,read_fulfillments,write_orders,read_orders,read_products,write_shipping'),
    'app_url' => env('SHOPIFY_APP_URL'),
    'session_secret' => env('SESSION_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Shopify webhooks that the app will listen to.
    |
    */

    'webhooks' => [
        'orders_paid' => [
            'topic' => 'orders/paid',
            'address' => '/webhooks/orders/paid',
        ],
        'orders_updated' => [
            'topic' => 'orders/updated',
            'address' => '/webhooks/orders/updated',
        ],
        'fulfillments_update' => [
            'topic' => 'fulfillments/update',
            'address' => '/webhooks/fulfillments/update',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | App Settings
    |--------------------------------------------------------------------------
    |
    | General app settings and behavior configuration.
    |
    */

    'embedded' => true,
    'app_bridge_version' => '3',
    'api_version' => '2023-10',
];

# EcoFreight API v2 Integration Complete

## Overview
This document summarizes the implementation of the EcoFreight Create Order v2 API integration as per the [official API documentation](https://ecofreight.docs.apiary.io/#reference/0/creating-order-v2/create-order).

## Changes Made

### 1. Database Migration
**File:** `database/migrations/2025_11_03_162038_add_bearer_token_to_shop_settings_table.php`

- Added `ecofreight_bearer_token` field to `shop_settings` table
- Field is encrypted for security

### 2. Model Updates
**File:** `app/Models/ShopSetting.php`

- Added `ecofreight_bearer_token` to fillable array
- Implemented `getEcofreightBearerTokenAttribute()` and `setEcofreightBearerTokenAttribute()` methods
- Token is automatically encrypted/decrypted using Laravel's Crypt facade

### 3. Service Implementation
**File:** `app/Services/EcoFreightService.php`

#### Major Changes:

**a) Authentication (`testConnection` method)**
- Changed endpoint from `/api/login` to `/api/auth`
- Updated to retrieve and store bearer token from response
- Token is automatically saved to shop settings on successful authentication

**b) Shipment Creation (`createShipment` method)**
- Changed endpoint from `/api/shipments` to `/api/create-order`
- Implemented Bearer token authentication instead of basic auth
- Added automatic token retrieval if token is not available
- Updated response handling to check for `status: success` as per v2 API

**c) Payload Building (`buildShipmentPayload` method)**
Completely rewritten to match EcoFreight v2 API flat structure:

**Required Fields:**
```php
'order_reference' => string
'service_type' => 'normal_delivery' | 'express_delivery'
'product_type' => 'non_document'
'consignee_name' => string
'consignee_address' => string
'consignee_city' => string
'consignee_mobile_no' => string
'item_description' => string
'item_quantity' => integer
'item_weight' => decimal
```

**Optional Fields:**
```php
'consignee_alt_mobile_no' => string
'cod' => decimal (if COD enabled)
'cod_payment_mode' => 'cash_only' (if COD enabled)
'shipment_value' => decimal
'special_instruction' => string
'customer_service_type' => 'C2C' (default)
'lat' => decimal (if available)
'lon' => decimal (if available)
```

**d) Validation (`validatePayload` method)**
- Updated to validate new v2 API structure
- Validates required fields, numeric values, and positive numbers

**e) Data Redaction (`redactSensitiveData` method)**
- Updated to redact v2 API field names

**f) Service Type Mapping (`mapServiceType` method)**
- Returns 'Express' or 'Standard' which are converted to 'express_delivery' or 'normal_delivery' in payload

### 4. Configuration Updates
**File:** `config/ecofreight.php`

- Updated base URL to include `/en` path: `https://app.ecofreight.ae/en`
- Updated auth endpoint from `/api/login` to `/api/auth`
- Updated create shipment endpoint from `/api/shipments` to `/api/create-order`

## API Request Flow

### 1. Authentication
```http
POST /api/auth
Content-Type: application/json

{
  "username": "your_username",
  "password": "your_password"
}

Response:
{
  "success": true,
  "data": {
    "token": "bearer_token_here"
  }
}
```

The token is automatically stored encrypted in `shop_settings.ecofreight_bearer_token`.

### 2. Create Order
```http
POST /api/create-order
Content-Type: application/json
Authorization: Bearer {token}

{
  "order_reference": "AWB00001",
  "service_type": "normal_delivery",
  "product_type": "non_document",
  "consignee_name": "Mohmed Hassan",
  "consignee_address": "No:15,Mussafa road, Abu Dhabi",
  "consignee_city": "Abu Dhabi",
  "consignee_mobile_no": "+971-555558552",
  "consignee_alt_mobile_no": "+971-543433432",
  "cod": 25,
  "cod_payment_mode": "cash_only",
  "item_description": "Gym home set",
  "item_quantity": 2,
  "item_weight": 35.4,
  "shipment_value": 25,
  "special_instruction": "Deliver at evening time",
  "customer_service_type": "C2C",
  "schedule_delivery_date": "2021-02-03 12:30:00",
  "lat": 24.466667,
  "lon": 54.366669
}

Response:
{
  "status": "success",
  "data": {
    "message": "Order created successfully",
    "tracking_no": "ECO0000010001",
    "order_reference": "AWB00001",
    "awb_label_print": "https://app.ecofreight.ae/api/print-awb/ECO0000010001/print"
  }
}
```

## Key Differences from Previous Version

| Previous (v1) | Current (v2) |
|---------------|--------------|
| Basic Auth | Bearer Token |
| Nested payload structure | Flat payload structure |
| `/api/login` endpoint | `/api/auth` endpoint |
| `/api/shipments` endpoint | `/api/create-order` endpoint |
| Multiple parcels support | Single aggregated shipment |
| Complex validation | Simplified validation |

## Testing

To test the integration:

1. Run the migration:
```bash
php artisan migrate
```

2. Update shop settings with EcoFreight credentials

3. Test connection (this will retrieve and store bearer token):
```bash
php artisan ecofreight:test {shop_domain} connection
```

4. Create a test shipment:
```bash
php artisan ecofreight:test {shop_domain} create-shipment --order-id={order_id}
```

## Notes

- The bearer token is automatically retrieved and stored when testing the connection
- If a shipment creation is attempted without a bearer token, the system will automatically attempt to retrieve one
- All sensitive data (token, credentials) are encrypted in the database
- The integration handles all payload transformations automatically based on Shopify order data
- COD, special instructions, and coordinates are automatically included if available in the order

## Compatibility

- Laravel Framework
- GuzzleHttp Client
- ShopSettings model with encrypted fields
- Automatic token management
- Error handling and logging

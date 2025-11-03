<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shopify_domain',
        'shopify_token',
        'access_token',
        'name',
        'email',
        'domain',
        'province',
        'country',
        'address1',
        'zip',
        'city',
        'source',
        'phone',
        'shopify_updated_at',
        'shopify_created_at',
        'country_code',
        'country_name',
        'currency',
        'customer_email',
        'timezone',
        'iana_timezone',
        'shopify_plan_name',
        'has_discounts',
        'has_gift_cards',
        'force_ssl',
        'checkout_api_supported',
        'multi_location_enabled',
        'has_storefront',
        'eligible_for_payments',
        'eligible_for_card_reader_giveaway',
        'finances',
        'primary_location_id',
        'cookie_consent_level',
        'visitor_tracking_consent_preference',
    ];

    protected $casts = [
        'shopify_updated_at' => 'datetime',
        'shopify_created_at' => 'datetime',
        'has_discounts' => 'boolean',
        'has_gift_cards' => 'boolean',
        'force_ssl' => 'boolean',
        'checkout_api_supported' => 'boolean',
        'multi_location_enabled' => 'boolean',
        'has_storefront' => 'boolean',
        'eligible_for_payments' => 'boolean',
        'eligible_for_card_reader_giveaway' => 'boolean',
        'finances' => 'boolean',
    ];

    /**
     * Get the shop settings.
     */
    public function settings(): HasOne
    {
        return $this->hasOne(ShopSetting::class);
    }

    /**
     * Get the user that owns the shop.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the shipments for the shop.
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    /**
     * Get the Shopify API client for this shop.
     */
    public function getShopifyClient()
    {
        return new \Shopify\Rest\Admin2023_10\ShopifyRestClient(
            $this->shopify_domain,
            $this->shopify_token
        );
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class ShopSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'ecofreight_base_url',
        'ecofreight_username',
        'ecofreight_password',
        'ecofreight_bearer_token',
        'last_connection_test',
        'connection_status',
        'ship_from_company',
        'ship_from_contact',
        'ship_from_phone',
        'ship_from_email',
        'ship_from_address1',
        'ship_from_address2',
        'ship_from_city',
        'ship_from_postcode',
        'ship_from_country',
        'default_weight',
        'default_length',
        'default_width',
        'default_height',
        'packing_rule',
        'use_standard_service',
        'use_express_service',
        'cod_enabled',
        'cod_fee',
        'markup_percentage',
        'discount_percentage',
        'tracking_url_template',
        'auto_poll_tracking',
        'poll_interval_hours',
        'stop_after_days',
        'error_alert_emails',
        'include_awb_in_alerts',
    ];

    protected $casts = [
        'last_connection_test' => 'datetime',
        'connection_status' => 'boolean',
        'default_weight' => 'decimal:2',
        'default_length' => 'decimal:2',
        'default_width' => 'decimal:2',
        'default_height' => 'decimal:2',
        'use_standard_service' => 'boolean',
        'use_express_service' => 'boolean',
        'cod_enabled' => 'boolean',
        'cod_fee' => 'decimal:2',
        'markup_percentage' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'auto_poll_tracking' => 'boolean',
        'include_awb_in_alerts' => 'boolean',
    ];

    /**
     * Get the shop that owns the settings.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Get the decrypted EcoFreight username.
     */
    public function getEcofreightUsernameAttribute($value)
    {
        if (!$value) {
            return null;
        }
        
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            // If decryption fails (invalid payload), return null
            // This happens when the data was encrypted with a different APP_KEY
            \Log::warning('Failed to decrypt ecofreight_username', [
                'shop_id' => $this->shop_id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Set the encrypted EcoFreight username.
     */
    public function setEcofreightUsernameAttribute($value)
    {
        $this->attributes['ecofreight_username'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get the decrypted EcoFreight password.
     */
    public function getEcofreightPasswordAttribute($value)
    {
        if (!$value) {
            return null;
        }
        
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            // If decryption fails (invalid payload), return null
            // This happens when the data was encrypted with a different APP_KEY
            \Log::warning('Failed to decrypt ecofreight_password', [
                'shop_id' => $this->shop_id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Set the encrypted EcoFreight password.
     */
    public function setEcofreightPasswordAttribute($value)
    {
        $this->attributes['ecofreight_password'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get the decrypted EcoFreight bearer token.
     */
    public function getEcofreightBearerTokenAttribute($value)
    {
        if (!$value) {
            return null;
        }
        
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            // If decryption fails (invalid payload), return null
            // This happens when the data was encrypted with a different APP_KEY
            \Log::warning('Failed to decrypt ecofreight_bearer_token', [
                'shop_id' => $this->shop_id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Set the encrypted EcoFreight bearer token.
     */
    public function setEcofreightBearerTokenAttribute($value)
    {
        $this->attributes['ecofreight_bearer_token'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get error alert emails as an array.
     */
    public function getErrorAlertEmailsArrayAttribute()
    {
        if (!$this->error_alert_emails) {
            return [];
        }
        
        return array_map('trim', explode(',', $this->error_alert_emails));
    }

    /**
     * Set error alert emails from an array.
     */
    public function setErrorAlertEmailsArrayAttribute($emails)
    {
        $this->error_alert_emails = is_array($emails) ? implode(',', $emails) : $emails;
    }

    /**
     * Get default dimensions as an array.
     */
    public function getDefaultDimensionsAttribute()
    {
        return [
            'length' => $this->default_length,
            'width' => $this->default_width,
            'height' => $this->default_height,
        ];
    }

    /**
     * Get ship-from address as an array.
     */
    public function getShipFromAddressAttribute()
    {
        return [
            'company' => $this->ship_from_company,
            'contact' => $this->ship_from_contact,
            'phone' => $this->ship_from_phone,
            'email' => $this->ship_from_email,
            'address1' => $this->ship_from_address1,
            'address2' => $this->ship_from_address2,
            'city' => $this->ship_from_city,
            'postcode' => $this->ship_from_postcode,
            'country' => $this->ship_from_country,
        ];
    }
}

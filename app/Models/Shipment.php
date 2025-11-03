<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'shopify_order_id',
        'shopify_order_name',
        'ecofreight_awb',
        'ecofreight_reference',
        'service_type',
        'status',
        'last_status',
        'error_message',
        'shipment_data',
        'label_data',
        'tracking_url',
        'cod_enabled',
        'cod_amount',
        'last_tracking_sync',
        'last_checked_at',
        'delivered_at',
        'stale_flag',
        'sync_attempts',
        'webhook_opt_in',
        'webhook_last_seen_at',
        'first_scan_at',
        'label_generated_at',
        'retry_count',
        'next_retry_at',
    ];

    protected $casts = [
        'shipment_data' => 'array',
        'label_data' => 'array',
        'cod_enabled' => 'boolean',
        'cod_amount' => 'decimal:2',
        'last_tracking_sync' => 'datetime',
        'last_checked_at' => 'datetime',
        'delivered_at' => 'datetime',
        'stale_flag' => 'boolean',
        'webhook_opt_in' => 'boolean',
        'webhook_last_seen_at' => 'datetime',
        'first_scan_at' => 'datetime',
        'label_generated_at' => 'datetime',
        'next_retry_at' => 'datetime',
    ];

    /**
     * Get the shop that owns the shipment.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Get the tracking logs for the shipment.
     */
    public function trackingLogs()
    {
        return $this->hasMany(TrackingLog::class);
    }

    /**
     * Check if the shipment is in a terminal state.
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, ['delivered', 'cancelled']);
    }

    /**
     * Check if the shipment can be retried.
     */
    public function canRetry(): bool
    {
        return $this->status === 'error' && $this->retry_count < 3;
    }

    /**
     * Check if the shipment is stale (no updates for specified hours).
     */
    public function isStale(int $hours = 48): bool
    {
        if ($this->isTerminal()) {
            return false;
        }

        $lastUpdate = $this->last_checked_at ?? $this->created_at;
        return $lastUpdate->addHours($hours)->isPast();
    }

    /**
     * Check if the shipment can be voided.
     */
    public function canVoid(): bool
    {
        return !$this->isTerminal() && !in_array($this->status, ['delivered']);
    }

    /**
     * Check if the shipment can be re-shipped.
     */
    public function canReship(): bool
    {
        return in_array($this->status, ['error', 'cancelled']) || $this->isStale();
    }

    /**
     * Get the status badge color.
     */
    public function getStatusBadgeColor(): string
    {
        if ($this->status === 'error' || $this->stale_flag) {
            return 'red';
        }
        
        if (in_array($this->status, ['pending', 'created'])) {
            return 'yellow';
        }
        
        if (in_array($this->status, ['delivered'])) {
            return 'green';
        }
        
        return 'blue';
    }

    /**
     * Get the latest tracking log.
     */
    public function getLatestTrackingLog()
    {
        return $this->trackingLogs()->orderBy('timestamp', 'desc')->first();
    }

    /**
     * Scope for active shipments (not terminal).
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['delivered', 'cancelled']);
    }

    /**
     * Scope for stale shipments.
     */
    public function scopeStale($query, int $hours = 48)
    {
        return $query->where(function ($q) use ($hours) {
            $q->where('stale_flag', true)
              ->orWhere(function ($subQ) use ($hours) {
                  $subQ->whereNotIn('status', ['delivered', 'cancelled'])
                       ->where(function ($lastCheck) use ($hours) {
                           $lastCheck->whereNull('last_checked_at')
                                    ->where('created_at', '<', now()->subHours($hours))
                                    ->orWhere('last_checked_at', '<', now()->subHours($hours));
                       });
              });
        });
    }

    /**
     * Scope for delivered shipments.
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    /**
     * Scope for exception shipments.
     */
    public function scopeException($query)
    {
        return $query->where('status', 'error');
    }

    /**
     * Get the EcoFreight tracking URL.
     */
    public function getTrackingUrlAttribute($value)
    {
        if ($value) {
            return $value;
        }

        if ($this->ecofreight_awb && $this->shop->settings) {
            $template = $this->shop->settings->tracking_url_template;
            if ($template) {
                return str_replace('{awb}', $this->ecofreight_awb, $template);
            }
        }

        return null;
    }

    /**
     * Get the label file path.
     */
    public function getLabelFilePathAttribute()
    {
        if ($this->label_data && isset($this->label_data['file_path'])) {
            return $this->label_data['file_path'];
        }

        return null;
    }

    /**
     * Get the label file URL.
     */
    public function getLabelFileUrlAttribute()
    {
        if ($this->label_data && isset($this->label_data['url'])) {
            return $this->label_data['url'];
        }

        return null;
    }
}

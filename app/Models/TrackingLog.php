<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'status',
        'description',
        'location',
        'city',
        'state',
        'country',
        'timestamp',
        'raw_data',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'raw_data' => 'array',
    ];

    /**
     * Get the shipment that owns the tracking log.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Scope for filtering by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for recent logs.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('timestamp', '>=', now()->subDays($days));
    }
}

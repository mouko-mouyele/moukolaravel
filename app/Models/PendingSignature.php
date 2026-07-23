<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingSignature extends Model
{
    use HasFactory;

    protected $fillable = [
        'operation_type',
        'vehicle_id',
        'payload',
        'data_hash',
        'initiated_by',
        'admin_signature',
        'buyer_signature',
        'admin_wallet',
        'buyer_wallet',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'expires_at' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }
}

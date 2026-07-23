<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BlockchainRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'record_type',
        'reference_type',
        'reference_id',
        'data_hash',
        'tx_hash',
        'block_number',
        'contract_address',
        'payload',
        'status',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}

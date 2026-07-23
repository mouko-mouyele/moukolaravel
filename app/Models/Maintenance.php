<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Maintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'mechanic_id',
        'intervention_type',
        'description',
        'mileage_at_service',
        'cost',
        'parts_changed',
        'service_date',
        'blockchain_tx_hash',
        'blockchain_record_id',
        'certified_on_chain',
    ];

    protected $casts = [
        'parts_changed' => 'array',
        'service_date' => 'date',
        'cost' => 'decimal:2',
        'certified_on_chain' => 'boolean',
        'mileage_at_service' => 'integer',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }
}

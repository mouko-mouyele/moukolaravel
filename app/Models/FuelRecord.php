<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'recorded_by',
        'mileage_at_fill',
        'liters',
        'total_cost',
        'consumption_per_100km',
        'filled_at',
        'station',
    ];

    protected $casts = [
        'filled_at' => 'datetime',
        'liters' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'consumption_per_100km' => 'decimal:2',
        'mileage_at_fill' => 'integer',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

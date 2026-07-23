<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MileageReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'recorded_by',
        'assignment_id',
        'mileage',
        'recorded_at',
        'blockchain_tx_hash',
        'certified_on_chain',
        'notes',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'certified_on_chain' => 'boolean',
        'mileage' => 'integer',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(VehicleAssignment::class, 'assignment_id');
    }
}

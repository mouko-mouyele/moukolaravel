<?php

namespace App\Models;

use App\Enums\AssignmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'driver_id',
        'assigned_by',
        'status',
        'start_mileage',
        'end_mileage',
        'started_at',
        'ended_at',
        'notes',
        'blockchain_tx_hash',
    ];

    protected $casts = [
        'status' => AssignmentStatus::class,
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'start_mileage' => 'integer',
        'end_mileage' => 'integer',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}

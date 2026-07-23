<?php

namespace App\Models;

use App\Enums\AlertType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'type',
        'title',
        'message',
        'due_date',
        'due_mileage',
        'is_read',
        'is_resolved',
        'resolved_at',
    ];

    protected $casts = [
        'type' => AlertType::class,
        'due_date' => 'date',
        'is_read' => 'boolean',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'due_mileage' => 'integer',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}

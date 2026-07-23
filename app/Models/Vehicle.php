<?php

namespace App\Models;

use App\Enums\VehicleStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'vin',
        'license_plate',
        'brand',
        'model',
        'year',
        'fuel_type',
        'current_mileage',
        'status',
        'blockchain_asset_id',
        'blockchain_tx_hash',
        'insurance_expiry',
        'technical_inspection_expiry',
        'next_oil_change_km',
        'next_maintenance_km',
        'registered_by',
    ];

    protected $casts = [
        'status' => VehicleStatus::class,
        'insurance_expiry' => 'date',
        'technical_inspection_expiry' => 'date',
        'year' => 'integer',
        'current_mileage' => 'integer',
        'next_oil_change_km' => 'integer',
        'next_maintenance_km' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Vehicle $vehicle) {
            if (empty($vehicle->uuid)) {
                $vehicle->uuid = (string) Str::uuid();
            }
        });
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(VehicleAssignment::class);
    }

    public function activeAssignment(): HasMany
    {
        return $this->hasMany(VehicleAssignment::class)->where('status', 'active');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class);
    }

    public function mileageReadings(): HasMany
    {
        return $this->hasMany(MileageReading::class);
    }

    public function fuelRecords(): HasMany
    {
        return $this->hasMany(FuelRecord::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function blockchainRecords(): HasMany
    {
        return $this->hasMany(BlockchainRecord::class);
    }
}

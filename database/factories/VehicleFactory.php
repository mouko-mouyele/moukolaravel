<?php

namespace Database\Factories;

use App\Enums\VehicleStatus;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'vin' => strtoupper(fake()->bothify('VF1RJA00#####')),
            'license_plate' => fake()->bothify('??-###-??'),
            'brand' => fake()->randomElement(['Renault', 'Peugeot', 'BMW', 'Toyota']),
            'model' => fake()->word(),
            'year' => fake()->numberBetween(2018, 2024),
            'fuel_type' => fake()->randomElement(['essence', 'diesel', 'hybride', 'electrique']),
            'current_mileage' => fake()->numberBetween(10000, 150000),
            'status' => VehicleStatus::Available,
            'registered_by' => User::factory(),
        ];
    }
}

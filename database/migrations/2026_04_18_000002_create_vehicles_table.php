<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('vin', 17)->unique();
            $table->string('license_plate', 20)->unique();
            $table->string('brand');
            $table->string('model');
            $table->unsignedSmallInteger('year');
            $table->string('fuel_type', 30);
            $table->unsignedInteger('current_mileage')->default(0);
            $table->string('status')->default('available');
            $table->string('blockchain_asset_id')->nullable()->unique();
            $table->string('blockchain_tx_hash')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->date('technical_inspection_expiry')->nullable();
            $table->unsignedInteger('next_oil_change_km')->nullable();
            $table->unsignedInteger('next_maintenance_km')->nullable();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};

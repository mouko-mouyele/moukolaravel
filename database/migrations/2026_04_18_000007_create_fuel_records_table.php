<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('mileage_at_fill');
            $table->decimal('liters', 8, 2);
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->decimal('consumption_per_100km', 6, 2)->nullable();
            $table->timestamp('filled_at');
            $table->string('station')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_records');
    }
};

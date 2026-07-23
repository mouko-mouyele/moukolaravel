<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mechanic_id')->constrained('users')->cascadeOnDelete();
            $table->string('intervention_type');
            $table->text('description')->nullable();
            $table->unsignedInteger('mileage_at_service');
            $table->decimal('cost', 10, 2)->nullable();
            $table->json('parts_changed')->nullable();
            $table->date('service_date');
            $table->string('blockchain_tx_hash')->nullable();
            $table->string('blockchain_record_id')->nullable();
            $table->boolean('certified_on_chain')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};

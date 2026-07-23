<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_signatures', function (Blueprint $table) {
            $table->id();
            $table->string('operation_type');
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->json('payload');
            $table->string('data_hash', 64);
            $table->foreignId('initiated_by')->constrained('users')->cascadeOnDelete();
            $table->string('admin_signature')->nullable();
            $table->string('buyer_signature')->nullable();
            $table->string('admin_wallet')->nullable();
            $table->string('buyer_wallet')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_signatures');
    }
};

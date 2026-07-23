<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('driver')->after('email');
            $table->string('wallet_address', 42)->nullable()->unique()->after('role');
            $table->string('phone', 20)->nullable()->after('wallet_address');
            $table->boolean('is_active')->default(true)->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'wallet_address', 'phone', 'is_active']);
        });
    }
};

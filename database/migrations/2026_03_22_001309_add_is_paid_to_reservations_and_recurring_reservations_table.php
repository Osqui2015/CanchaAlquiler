<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->boolean('is_paid')->default(false)->after('status');
        });

        Schema::table('recurring_reservations', function (Blueprint $table) {
            $table->boolean('is_paid')->default(false)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('is_paid');
        });

        Schema::table('recurring_reservations', function (Blueprint $table) {
            $table->dropColumn('is_paid');
        });
    }
};

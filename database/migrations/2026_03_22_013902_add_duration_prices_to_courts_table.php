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
        Schema::table('courts', function (Blueprint $table) {
            $table->decimal('price_30_min', 12, 2)->nullable()->after('base_price');
            $table->decimal('price_60_min', 12, 2)->nullable()->after('price_30_min');
            $table->decimal('price_90_min', 12, 2)->nullable()->after('price_60_min');
            $table->decimal('price_120_min', 12, 2)->nullable()->after('price_90_min');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courts', function (Blueprint $table) {
            $table->dropColumn(['price_30_min', 'price_60_min', 'price_90_min', 'price_120_min']);
        });
    }
};

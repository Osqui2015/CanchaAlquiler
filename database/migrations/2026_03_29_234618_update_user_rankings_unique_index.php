<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('user_rankings', 'type')) {
            Schema::table('user_rankings', function (Blueprint $table) {
                $table->string('type')->default('individual')->after('sport_id');
            });
        }

        // Drop the old unique index if it exists using raw SQL
        try {
            DB::statement('ALTER TABLE user_rankings DROP INDEX user_rankings_user_id_sport_id_unique');
        } catch (\Exception $e) {
            // Index might not exist or has a different name
        }

        Schema::table('user_rankings', function (Blueprint $table) {
            $table->unique(['user_id', 'sport_id', 'type'], 'user_rankings_user_sport_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_rankings', function (Blueprint $table) {
            $table->dropUnique('user_rankings_user_sport_type_unique');
            if (Schema::hasColumn('user_rankings', 'type')) {
                $table->dropColumn('type');
            }
            $table->unique(['user_id', 'sport_id']);
        });
    }
};

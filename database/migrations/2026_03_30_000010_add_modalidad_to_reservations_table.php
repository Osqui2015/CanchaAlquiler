<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('reservations', function (Blueprint $table) {
      $table->string('modalidad', 50)->default('individual')->after('sport_id');
      $table->index(['sport_id', 'modalidad']);
    });
  }

  public function down(): void
  {
    Schema::table('reservations', function (Blueprint $table) {
      $table->dropIndex(['sport_id', 'modalidad']);
      $table->dropColumn('modalidad');
    });
  }
};

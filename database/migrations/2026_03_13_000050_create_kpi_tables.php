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
    Schema::create('kpi_owner_daily', function (Blueprint $table) {
      $table->id();
      $table->date('date');
      $table->foreignId('complex_id')->constrained()->cascadeOnDelete();
      $table->decimal('gross_revenue', 12, 2)->default(0);
      $table->decimal('deposits_revenue', 12, 2)->default(0);
      $table->unsignedInteger('reservations_confirmed')->default(0);
      $table->unsignedInteger('occupancy_minutes')->default(0);
      $table->unsignedInteger('available_minutes')->default(0);
      $table->timestamps();

      $table->unique(['date', 'complex_id']);
    });

    Schema::create('kpi_global_daily', function (Blueprint $table) {
      $table->id();
      $table->date('date')->unique();
      $table->decimal('gross_revenue', 12, 2)->default(0);
      $table->unsignedInteger('reservations_confirmed')->default(0);
      $table->unsignedInteger('new_clients')->default(0);
      $table->unsignedInteger('active_complexes')->default(0);
      $table->unsignedInteger('active_admin_cancha')->default(0);
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('kpi_global_daily');
    Schema::dropIfExists('kpi_owner_daily');
  }
};

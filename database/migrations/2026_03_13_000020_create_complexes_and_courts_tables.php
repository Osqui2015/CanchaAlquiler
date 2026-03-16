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
    Schema::create('complexes', function (Blueprint $table) {
      $table->id();
      $table->foreignId('city_id')->constrained()->cascadeOnDelete();
      $table->string('name');
      $table->string('slug')->unique();
      $table->string('address_line');
      $table->decimal('latitude', 10, 7)->nullable();
      $table->decimal('longitude', 10, 7)->nullable();
      $table->text('description')->nullable();
      $table->string('phone_contact')->nullable();
      $table->enum('status', ['activo', 'suspendido'])->default('activo');
      $table->boolean('booking_enabled')->default(true);
      $table->timestamps();

      $table->index(['city_id', 'status']);
    });

    Schema::create('complex_user_assignments', function (Blueprint $table) {
      $table->id();
      $table->foreignId('complex_id')->constrained()->cascadeOnDelete();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->enum('assignment_type', ['owner', 'manager'])->default('owner');
      $table->boolean('is_primary')->default(false);
      $table->timestamps();

      $table->unique(['complex_id', 'user_id']);
    });

    Schema::create('complex_services', function (Blueprint $table) {
      $table->foreignId('complex_id')->constrained()->cascadeOnDelete();
      $table->foreignId('service_id')->constrained('services_catalog')->cascadeOnDelete();

      $table->primary(['complex_id', 'service_id']);
    });

    Schema::create('courts', function (Blueprint $table) {
      $table->id();
      $table->foreignId('complex_id')->constrained()->cascadeOnDelete();
      $table->foreignId('sport_id')->constrained()->cascadeOnDelete();
      $table->string('name');
      $table->enum('surface_type', ['cesped_sintetico', 'cemento', 'polvo_ladrillo', 'otro']);
      $table->unsignedSmallInteger('players_capacity');
      $table->unsignedSmallInteger('slot_duration_minutes');
      $table->decimal('base_price', 10, 2);
      $table->enum('status', ['activa', 'inactiva', 'mantenimiento'])->default('activa');
      $table->timestamps();

      $table->index(['complex_id', 'sport_id', 'status']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('courts');
    Schema::dropIfExists('complex_services');
    Schema::dropIfExists('complex_user_assignments');
    Schema::dropIfExists('complexes');
  }
};

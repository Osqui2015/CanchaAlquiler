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
    Schema::create('complex_opening_hours', function (Blueprint $table) {
      $table->id();
      $table->foreignId('complex_id')->constrained()->cascadeOnDelete();
      $table->unsignedTinyInteger('day_of_week');
      $table->boolean('is_open')->default(true);
      $table->time('open_time')->nullable();
      $table->time('close_time')->nullable();
      $table->timestamps();

      $table->unique(['complex_id', 'day_of_week']);
    });

    Schema::create('complex_special_dates', function (Blueprint $table) {
      $table->id();
      $table->foreignId('complex_id')->constrained()->cascadeOnDelete();
      $table->date('date');
      $table->enum('mode', ['cerrado', 'horario_especial'])->default('cerrado');
      $table->time('open_time')->nullable();
      $table->time('close_time')->nullable();
      $table->string('reason')->nullable();
      $table->timestamps();

      $table->unique(['complex_id', 'date']);
    });

    Schema::create('court_blocks', function (Blueprint $table) {
      $table->id();
      $table->foreignId('court_id')->constrained()->cascadeOnDelete();
      $table->dateTime('start_at');
      $table->dateTime('end_at');
      $table->string('reason')->nullable();
      $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
      $table->timestamps();

      $table->index(['court_id', 'start_at']);
      $table->index(['court_id', 'end_at']);
    });

    Schema::create('court_price_rules', function (Blueprint $table) {
      $table->id();
      $table->foreignId('court_id')->constrained()->cascadeOnDelete();
      $table->unsignedTinyInteger('day_of_week');
      $table->time('start_time');
      $table->time('end_time');
      $table->enum('price_type', ['fijo', 'multiplicador'])->default('fijo');
      $table->decimal('value', 10, 2);
      $table->date('valid_from')->nullable();
      $table->date('valid_to')->nullable();
      $table->timestamps();

      $table->index(['court_id', 'day_of_week', 'valid_from', 'valid_to']);
    });

    Schema::create('complex_policies', function (Blueprint $table) {
      $table->id();
      $table->foreignId('complex_id')->constrained()->cascadeOnDelete()->unique();
      $table->unsignedTinyInteger('deposit_percent')->default(30);
      $table->unsignedInteger('cancel_limit_minutes')->default(180);
      $table->unsignedTinyInteger('refund_percent_before_limit')->default(100);
      $table->unsignedTinyInteger('no_show_penalty_percent')->default(100);
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('complex_policies');
    Schema::dropIfExists('court_price_rules');
    Schema::dropIfExists('court_blocks');
    Schema::dropIfExists('complex_special_dates');
    Schema::dropIfExists('complex_opening_hours');
  }
};

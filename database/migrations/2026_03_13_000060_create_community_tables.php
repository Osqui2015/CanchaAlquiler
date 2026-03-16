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
    Schema::create('complex_tournaments', function (Blueprint $table) {
      $table->id();
      $table->foreignId('complex_id')->constrained()->cascadeOnDelete();
      $table->foreignId('sport_id')->nullable()->constrained()->nullOnDelete();
      $table->string('name');
      $table->string('category')->nullable();
      $table->string('format')->nullable();
      $table->date('start_date');
      $table->date('end_date');
      $table->enum('status', ['inscripciones_abiertas', 'cupos_limitados', 'cerrado'])->default('inscripciones_abiertas');
      $table->unsignedSmallInteger('max_teams')->default(16);
      $table->decimal('entry_fee', 10, 2)->default(0);
      $table->string('prize')->nullable();
      $table->text('notes')->nullable();
      $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
      $table->timestamps();

      $table->index(['complex_id', 'sport_id', 'status']);
      $table->index(['complex_id', 'start_date', 'end_date']);
    });

    Schema::create('tournament_teams', function (Blueprint $table) {
      $table->id();
      $table->foreignId('tournament_id')->constrained('complex_tournaments')->cascadeOnDelete();
      $table->string('name');
      $table->unsignedSmallInteger('matches')->default(0);
      $table->unsignedSmallInteger('wins')->default(0);
      $table->unsignedSmallInteger('draws')->default(0);
      $table->unsignedSmallInteger('losses')->default(0);
      $table->integer('goal_diff')->default(0);
      $table->unsignedSmallInteger('points')->default(0);
      $table->unsignedSmallInteger('position')->nullable();
      $table->timestamps();

      $table->unique(['tournament_id', 'name']);
      $table->index(['tournament_id', 'points', 'goal_diff']);
    });

    Schema::create('complex_team_board_posts', function (Blueprint $table) {
      $table->id();
      $table->foreignId('complex_id')->constrained()->cascadeOnDelete();
      $table->foreignId('sport_id')->nullable()->constrained()->nullOnDelete();
      $table->enum('kind', ['falta_jugador', 'busco_rival', 'falta_equipo']);
      $table->string('title');
      $table->string('level')->nullable();
      $table->unsignedSmallInteger('needed_players')->nullable();
      $table->string('play_day')->nullable();
      $table->time('play_time')->nullable();
      $table->string('contact');
      $table->text('notes')->nullable();
      $table->enum('status', ['activo', 'cerrado'])->default('activo');
      $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
      $table->timestamps();

      $table->index(['complex_id', 'sport_id', 'status']);
      $table->index(['complex_id', 'kind']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('complex_team_board_posts');
    Schema::dropIfExists('tournament_teams');
    Schema::dropIfExists('complex_tournaments');
  }
};

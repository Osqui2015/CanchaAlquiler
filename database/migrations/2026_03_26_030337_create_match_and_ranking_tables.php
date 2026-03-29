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
        // 1. Matches: To record results of specific games linked to reservations
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_id')->constrained();
            $table->foreignId('complex_id')->constrained();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            
            // Format: JSON for flexibility (e.g. [[user1], [user2]] for pairs)
            $table->json('team_a_players'); 
            $table->json('team_b_players');
            
            $table->integer('score_a')->default(0);
            $table->integer('score_b')->default(0);
            
            // match_type: solo, pair, team
            $table->string('match_type')->default('solo'); 
            
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // 2. Player Stats: To track performance per sport and match type
        Schema::create('player_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained()->cascadeOnDelete();
            $table->string('match_type'); // solo, pair, team
            
            $table->integer('wins')->default(0);
            $table->integer('losses')->default(0);
            $table->integer('draws')->default(0);
            $table->integer('points')->default(0);
            $table->integer('rank')->default(0);
            
            $table->timestamps();
            
            $table->unique(['user_id', 'sport_id', 'match_type']);
        });

        // 3. Venue Stats: To track where a player plays/wins the most
        Schema::create('player_venue_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('complex_id')->constrained()->cascadeOnDelete();
            
            $table->integer('matches_played')->default(0);
            $table->integer('wins')->default(0);
            
            $table->timestamps();
            
            $table->unique(['user_id', 'complex_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_venue_stats');
        Schema::dropIfExists('player_stats');
        Schema::dropIfExists('matches');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('user_favorites', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('user_id');
      $table->unsignedBigInteger('complex_id');
      $table->timestamps();

      $table->unique(['user_id', 'complex_id']);

      $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
      $table->foreign('complex_id')->references('id')->on('complexes')->onDelete('cascade');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('user_favorites');
  }
};

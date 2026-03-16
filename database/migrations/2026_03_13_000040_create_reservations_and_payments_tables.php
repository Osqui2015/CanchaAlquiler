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
    Schema::create('reservations', function (Blueprint $table) {
      $table->id();
      $table->string('code')->unique();
      $table->foreignId('client_user_id')->constrained('users')->cascadeOnDelete();
      $table->foreignId('complex_id')->constrained()->cascadeOnDelete();
      $table->foreignId('court_id')->constrained()->cascadeOnDelete();
      $table->foreignId('sport_id')->constrained()->cascadeOnDelete();
      $table->dateTime('start_at');
      $table->dateTime('end_at');
      $table->decimal('total_amount', 10, 2);
      $table->decimal('deposit_amount', 10, 2);
      $table->char('currency', 3)->default('ARS');
      $table->enum('status', ['pendiente_pago', 'confirmada', 'cancelada', 'expirada', 'no_show'])->default('pendiente_pago');
      $table->dateTime('hold_expires_at')->nullable();
      $table->dateTime('canceled_at')->nullable();
      $table->foreignId('canceled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
      $table->text('cancel_reason')->nullable();
      $table->timestamps();

      $table->index(['court_id', 'start_at']);
      $table->index(['court_id', 'end_at']);
      $table->index(['client_user_id', 'created_at']);
      $table->index(['complex_id', 'start_at', 'status']);
    });

    Schema::create('reservation_status_histories', function (Blueprint $table) {
      $table->id();
      $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
      $table->string('from_status')->nullable();
      $table->string('to_status');
      $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
      $table->string('reason')->nullable();
      $table->timestamp('created_at')->useCurrent();

      $table->index(['reservation_id', 'created_at']);
    });

    Schema::create('payments', function (Blueprint $table) {
      $table->id();
      $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
      $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
      $table->enum('provider', ['mercadopago', 'stripe', 'otro'])->default('otro');
      $table->string('provider_payment_id')->unique();
      $table->enum('status', ['initiated', 'pending', 'approved', 'rejected', 'refunded'])->default('initiated');
      $table->decimal('amount', 10, 2);
      $table->char('currency', 3)->default('ARS');
      $table->string('payment_method')->nullable();
      $table->dateTime('paid_at')->nullable();
      $table->json('raw_response_json')->nullable();
      $table->timestamps();

      $table->index(['reservation_id', 'status']);
    });

    Schema::create('payment_events', function (Blueprint $table) {
      $table->id();
      $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
      $table->string('event_type');
      $table->json('payload_json')->nullable();
      $table->timestamp('received_at')->useCurrent();

      $table->index(['payment_id', 'received_at']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('payment_events');
    Schema::dropIfExists('payments');
    Schema::dropIfExists('reservation_status_histories');
    Schema::dropIfExists('reservations');
  }
};

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
  public function startCheckout(
    Request $request,
    Reservation $reservation,
    PaymentService $paymentService,
  ): JsonResponse {
    $validated = $request->validate([
      'provider' => ['nullable', 'in:mercadopago,stripe,otro'],
    ]);

    $checkout = $paymentService->startCheckout(
      $request->user(),
      $reservation,
      $validated['provider'] ?? 'mercadopago',
    );

    return response()->json([
      'message' => 'Checkout generado correctamente.',
      'data' => $checkout,
    ]);
  }

  public function webhook(Request $request, PaymentService $paymentService): JsonResponse
  {
    $validated = $request->validate([
      'provider_payment_id' => ['required', 'string', 'max:120'],
      'status' => ['required', 'in:approved,rejected,refunded,pending'],
    ]);

    $payment = $paymentService->processWebhook([
      'provider_payment_id' => $validated['provider_payment_id'],
      'status' => $validated['status'],
      'payload' => $request->all(),
    ]);

    return response()->json([
      'message' => 'Webhook procesado correctamente.',
      'data' => $payment,
    ]);
  }
}

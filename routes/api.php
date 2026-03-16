<?php

use App\Http\Controllers\Api\Admin\AdminComplexController;
use App\Http\Controllers\Api\Admin\AdminCourtController;
use App\Http\Controllers\Api\AvailabilitySearchController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\ClientReservationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\SuperAdmin\SuperAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/catalog', CatalogController::class);
Route::get('/availability', AvailabilitySearchController::class);
Route::post('/payments/webhook', [PaymentController::class, 'webhook']);

Route::middleware(['web', 'auth', 'role:cliente,admin_cancha,super_admin'])->group(function (): void {
  Route::get('/client/reservations', [ClientReservationController::class, 'index']);
  Route::post('/client/reservations', [ClientReservationController::class, 'store']);
  Route::post('/client/reservations/{reservation}/cancel', [ClientReservationController::class, 'cancel']);
  Route::post('/client/reservations/{reservation}/checkout', [PaymentController::class, 'startCheckout']);
});

Route::prefix('admin')
  ->middleware(['web', 'auth', 'role:admin_cancha,super_admin'])
  ->group(function (): void {
    Route::get('/complexes', [AdminComplexController::class, 'index']);
    Route::post('/complexes', [AdminComplexController::class, 'store']);
    Route::put('/complexes/{complex}', [AdminComplexController::class, 'update']);
    Route::put('/complexes/{complex}/opening-hours', [AdminComplexController::class, 'upsertOpeningHours']);
    Route::put('/complexes/{complex}/policy', [AdminComplexController::class, 'upsertPolicy']);
    Route::get('/complexes/{complex}/reservations-grid', [AdminComplexController::class, 'reservationsGrid']);
    Route::get('/complexes/{complex}/dashboard', [AdminComplexController::class, 'dashboard']);

    Route::post('/complexes/{complex}/courts', [AdminCourtController::class, 'store']);
    Route::put('/courts/{court}', [AdminCourtController::class, 'update']);
    Route::delete('/courts/{court}', [AdminCourtController::class, 'destroy']);
  });

Route::prefix('super-admin')
  ->middleware(['web', 'auth', 'role:super_admin'])
  ->group(function (): void {
    Route::get('/admin-cancha-users', [SuperAdminController::class, 'adminCanchaIndex']);
    Route::post('/admin-cancha-users', [SuperAdminController::class, 'adminCanchaStore']);
    Route::put('/admin-cancha-users/{user}', [SuperAdminController::class, 'adminCanchaUpdate']);
    Route::post('/admin-cancha-users/{user}/assign-complex', [SuperAdminController::class, 'assignComplex']);

    Route::get('/clients', [SuperAdminController::class, 'clientsIndex']);
    Route::post('/clients', [SuperAdminController::class, 'clientStore']);
    Route::put('/clients/{user}', [SuperAdminController::class, 'clientUpdate']);

    Route::get('/dashboard', [SuperAdminController::class, 'dashboard']);
  });

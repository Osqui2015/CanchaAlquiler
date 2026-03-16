<?php

use App\Http\Controllers\Auth\InertiaAuthController;
use App\Http\Controllers\Web\AdminCanchaPanelController;
use App\Http\Controllers\Web\ClientPanelController;
use App\Http\Controllers\Web\ComplexProfileController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\SuperAdminPanelController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/complejos/{slug}', [ComplexProfileController::class, 'show'])->name('complex.show');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [InertiaAuthController::class, 'showLogin'])->name('login');
    Route::get('/register', [InertiaAuthController::class, 'showRegister'])->name('register');

    Route::post('/login', [InertiaAuthController::class, 'login'])->name('login.store');
    Route::post('/register', [InertiaAuthController::class, 'register'])->name('register.store');
});

Route::post('/logout', [InertiaAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::view('/livewire-demo', 'livewire-demo')->name('livewire.demo');

Route::middleware('auth')->group(function (): void {
    Route::get('/panel', function () {
        $user = request()->user();

        return match ($user->role) {
            'super_admin' => redirect()->route('panel.superadmin'),
            'admin_cancha' => redirect()->route('panel.admincancha'),
            default => redirect()->route('panel.cliente'),
        };
    })->name('panel');

    Route::get('/panel/cliente', [ClientPanelController::class, 'index'])
        ->middleware('role:cliente')
        ->name('panel.cliente');

    Route::post('/panel/cliente/reservas', [ClientPanelController::class, 'storeReservation'])
        ->middleware('role:cliente')
        ->name('panel.cliente.reservas.store');

    Route::post('/panel/cliente/reservas/{reservation}/cancelar', [ClientPanelController::class, 'cancelReservation'])
        ->middleware('role:cliente')
        ->name('panel.cliente.reservas.cancel');

    Route::post('/panel/cliente/reservas/{reservation}/checkout', [ClientPanelController::class, 'startCheckout'])
        ->middleware('role:cliente')
        ->name('panel.cliente.reservas.checkout');

    Route::post('/panel/cliente/reservas/{reservation}/checkout/aprobar-demo', [ClientPanelController::class, 'approveCheckoutDemo'])
        ->middleware('role:cliente')
        ->name('panel.cliente.reservas.checkout.approve-demo');

    Route::get('/panel/admin-cancha', [AdminCanchaPanelController::class, 'index'])
        ->middleware('role:admin_cancha')
        ->name('panel.admincancha');

    Route::post('/panel/admin-cancha/complejos', [AdminCanchaPanelController::class, 'storeComplex'])
        ->middleware('role:admin_cancha')
        ->name('panel.admincancha.complejos.store');

    Route::post('/panel/admin-cancha/complejos/{complex}/canchas', [AdminCanchaPanelController::class, 'storeCourt'])
        ->middleware('role:admin_cancha')
        ->name('panel.admincancha.canchas.store');

    Route::put('/panel/admin-cancha/canchas/{court}', [AdminCanchaPanelController::class, 'updateCourt'])
        ->middleware('role:admin_cancha')
        ->name('panel.admincancha.canchas.update');

    Route::put('/panel/admin-cancha/complejos/{complex}/horarios', [AdminCanchaPanelController::class, 'upsertOpeningHours'])
        ->middleware('role:admin_cancha')
        ->name('panel.admincancha.horarios.update');

    Route::put('/panel/admin-cancha/complejos/{complex}/politica', [AdminCanchaPanelController::class, 'upsertPolicy'])
        ->middleware('role:admin_cancha')
        ->name('panel.admincancha.politica.update');

    Route::post('/panel/admin-cancha/complejos/{complex}/torneos', [AdminCanchaPanelController::class, 'storeTournament'])
        ->middleware('role:admin_cancha')
        ->name('panel.admincancha.torneos.store');

    Route::post('/panel/admin-cancha/complejos/{complex}/torneos/{tournament}/equipos', [AdminCanchaPanelController::class, 'storeTournamentTeam'])
        ->middleware('role:admin_cancha')
        ->name('panel.admincancha.torneos.equipos.store');

    Route::post('/panel/admin-cancha/complejos/{complex}/convocatorias', [AdminCanchaPanelController::class, 'storeTeamBoardPost'])
        ->middleware('role:admin_cancha')
        ->name('panel.admincancha.convocatorias.store');

    Route::get('/panel/super-admin', [SuperAdminPanelController::class, 'index'])
        ->middleware('role:super_admin')
        ->name('panel.superadmin');

    Route::post('/panel/super-admin/admin-cancha', [SuperAdminPanelController::class, 'storeAdmin'])
        ->middleware('role:super_admin')
        ->name('panel.superadmin.admins.store');

    Route::post('/panel/super-admin/admin-cancha/{user}/asignar-complejo', [SuperAdminPanelController::class, 'assignComplex'])
        ->middleware('role:super_admin')
        ->name('panel.superadmin.admins.assign-complex');

    Route::post('/panel/super-admin/clientes', [SuperAdminPanelController::class, 'storeClient'])
        ->middleware('role:super_admin')
        ->name('panel.superadmin.clientes.store');

    Route::put('/panel/super-admin/clientes/{user}', [SuperAdminPanelController::class, 'updateClient'])
        ->middleware('role:super_admin')
        ->name('panel.superadmin.clientes.update');
});

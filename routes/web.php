<?php

use App\Http\Controllers\Auth\InertiaAuthController;
use App\Http\Controllers\Web\AdminCanchaPanelController;
use App\Http\Controllers\Web\ClientPanelController;
use App\Http\Controllers\Web\ComplexProfileController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\SuperAdminPanelController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;

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

    Route::get('/panel/cliente/historial', [ClientPanelController::class, 'history'])
        ->middleware('role:cliente')
        ->name('panel.cliente.historial');

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

    Route::put('/panel/admin-cancha/complejos/{complex}', [AdminCanchaPanelController::class, 'updateComplex'])
        ->middleware('role:admin_cancha')
        ->name('panel.admincancha.complejos.update');

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

    Route::get('/panel/admin-cancha/complejos/{complex}/calendario/{date}', [AdminCanchaPanelController::class, 'getCalendarDayDetails'])
        ->middleware('role:admin_cancha')
        ->name('panel.admincancha.calendario.dia');

    Route::post('/panel/admin-cancha/complejos/{complex}/reservas-rapidas', [AdminCanchaPanelController::class, 'storeFastReservation'])
        ->middleware('role:admin_cancha')
        ->name('panel.admincancha.reservas-rapidas.store');

    Route::post('/panel/admin-cancha/complejos/{complex}/reservas/{reservation}/cancelar', [AdminCanchaPanelController::class, 'cancelReservation'])
        ->middleware('role:admin_cancha')
        ->name('panel.admincancha.reservas.cancelar');

    Route::put('/panel/admin-cancha/complejos/{complex}/reservas/{reservation}', [AdminCanchaPanelController::class, 'updateReservation'])
        ->middleware('role:admin_cancha')
        ->name('panel.admincancha.reservas.update');

    Route::post('/panel/admin-cancha/complejos/{complex}/partidos', [\App\Http\Controllers\Web\AdminCanchaMatchController::class, 'store'])
        ->middleware('role:admin_cancha')
        ->name('panel.admincancha.partidos.store');

    Route::post('/panel/admin-cancha/complejos/{complex}/turnos-fijos', [AdminCanchaPanelController::class, 'storeRecurringReservation'])
        ->middleware('role:admin_cancha')
        ->name('panel.admincancha.turnos-fijos.store');

    Route::delete('/panel/admin-cancha/complejos/{complex}/turnos-fijos/{recurringReservation}', [AdminCanchaPanelController::class, 'destroyRecurringReservation'])
        ->middleware('role:admin_cancha')
        ->name('panel.admincancha.turnos-fijos.destroy');

    Route::put('/panel/admin-cancha/complejos/{complex}/turnos-fijos/{recurringReservation}', [AdminCanchaPanelController::class, 'updateRecurringReservation'])
        ->middleware('role:admin_cancha')
        ->name('panel.admincancha.turnos-fijos.update');

    Route::get('/panel/admin-cancha/complejos/{complex}/reportes', [AdminCanchaPanelController::class, 'getAdvancedReports'])
        ->middleware('role:admin_cancha')
        ->name('panel.admincancha.reportes');

    Route::get('/panel/admin-cancha/clientes', [\App\Http\Controllers\Web\AdminCanchaClientController::class, 'index'])
        ->middleware('role:admin_cancha')
        ->name('panel.admincancha.clientes.index');

    Route::post('/panel/admin-cancha/clientes', [\App\Http\Controllers\Web\AdminCanchaClientController::class, 'store'])
        ->middleware('role:admin_cancha')
        ->name('panel.admincancha.clientes.store');

    Route::put('/panel/admin-cancha/clientes/{client}', [\App\Http\Controllers\Web\AdminCanchaClientController::class, 'update'])
        ->middleware('role:admin_cancha')
        ->name('panel.admincancha.clientes.update');

    Route::get('/panel/super-admin', [SuperAdminPanelController::class, 'index'])
        ->middleware('role:super_admin')
        ->name('panel.superadmin');

    Route::post('/panel/super-admin/admin-cancha', [SuperAdminPanelController::class, 'storeAdmin'])
        ->middleware('role:super_admin')
        ->name('panel.superadmin.admins.store');

    Route::post('/panel/super-admin/complejos', [SuperAdminPanelController::class, 'storeComplex'])
        ->middleware('role:super_admin')
        ->name('panel.superadmin.complexes.store');

    Route::put('/panel/super-admin/admin-cancha/{user}', [SuperAdminPanelController::class, 'updateAdminStatus'])
        ->middleware('role:super_admin')
        ->name('panel.superadmin.admins.update');

    Route::post('/panel/super-admin/admin-cancha/{user}/asignar-complejo', [SuperAdminPanelController::class, 'assignComplex'])
        ->middleware('role:super_admin')
        ->name('panel.superadmin.admins.assign-complex');

    Route::post('/panel/super-admin/clientes', [SuperAdminPanelController::class, 'storeClient'])
        ->middleware('role:super_admin')
        ->name('panel.superadmin.clientes.store');

    Route::put('/panel/super-admin/clientes/{user}', [SuperAdminPanelController::class, 'updateClient'])
        ->middleware('role:super_admin')
        ->name('panel.superadmin.clientes.update');

    Route::middleware(['auth'])->group(function () {
        Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');

        Route::get('/perfil/editar', [ProfileController::class, 'edit'])
            ->middleware('auth')
            ->name('profile.edit');

        Route::post('/perfil/editar', [ProfileController::class, 'update'])
            ->middleware('auth')
            ->name('profile.update');
    });
});

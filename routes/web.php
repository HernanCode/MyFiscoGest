<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FilingController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\MovementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecurringMovementController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Presentaciones: los 8 vencimientos (303/130) del año, con su estado.
    Route::get('/presentaciones', [FilingController::class, 'index'])->name('filings.index');
    Route::patch('/presentaciones/{filing}/presentar', [FilingController::class, 'markFiled'])->name('filings.mark-filed');
    Route::patch('/presentaciones/{filing}/reabrir', [FilingController::class, 'reopen'])->name('filings.reopen');

    // Movimientos
    Route::get('/movimientos', [MovementController::class, 'index'])->name('movements.index');
    Route::get('/movimientos/exportar', [ExportController::class, 'movements'])->name('movements.export');
    Route::get('/movimientos/nuevo', [MovementController::class, 'create'])->name('movements.create');
    Route::post('/movimientos', [MovementController::class, 'store'])->name('movements.store');
    // Recurrencias: pestaña "Recurrentes" dentro de Movimientos
    Route::get('/movimientos/recurrentes', [RecurringMovementController::class, 'index'])->name('movements.recurring.index');
    Route::post('/movimientos/recurrentes/generar', [RecurringMovementController::class, 'generate'])->name('movements.recurring.generate');
    Route::get('/movimientos/recurrentes/{recurringMovement}/editar', [RecurringMovementController::class, 'edit'])->name('movements.recurring.edit');
    Route::put('/movimientos/recurrentes/{recurringMovement}', [RecurringMovementController::class, 'update'])->name('movements.recurring.update');
    Route::patch('/movimientos/recurrentes/{recurringMovement}/pausar', [RecurringMovementController::class, 'toggle'])->name('movements.recurring.toggle');
    Route::delete('/movimientos/recurrentes/{recurringMovement}', [RecurringMovementController::class, 'destroy'])->name('movements.recurring.destroy');

    Route::get('/movimientos/{movement}/editar', [MovementController::class, 'edit'])->name('movements.edit');
    Route::put('/movimientos/{movement}', [MovementController::class, 'update'])->name('movements.update');
    Route::delete('/movimientos/{movement}', [MovementController::class, 'destroy'])->name('movements.destroy');
    Route::get('/movimientos/{movement}/factura', [MovementController::class, 'invoice'])->name('movements.invoice');

    // Copia de seguridad completa (CSV + facturas) en un ZIP
    Route::get('/exportar', [ExportController::class, 'all'])->name('export.all');

    // Usuarios e invitaciones (solo el dueño de la instancia)
    Route::get('/usuarios', [UserController::class, 'index'])->name('users.index');
    Route::patch('/usuarios/{user}', [UserController::class, 'update'])->name('users.update');
    Route::post('/usuarios/{user}/restablecer', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::patch('/usuarios/{user}/estado', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/usuarios/invitaciones', [InvitationController::class, 'store'])->name('invitations.store');
    Route::delete('/usuarios/invitaciones/{invitation}', [InvitationController::class, 'destroy'])->name('invitations.destroy');
});

// Alta por invitación (sin sesión)
Route::get('/invitacion/{token}', [InvitationController::class, 'show'])->name('invitations.show');
Route::post('/invitacion/{token}', [InvitationController::class, 'accept'])->name('invitations.accept');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

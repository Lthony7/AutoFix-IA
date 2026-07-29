<?php

use Illuminate\Support\Facades\Route;
use Src\Cita\Application\Controllers\CalendarioWebController;

Route::middleware(['auth', 'role:administrador,recepcionista,mecanico,cliente'])->group(function () {
    Route::get('calendario', [CalendarioWebController::class, 'index'])->name('calendario.index');
    Route::get('calendario/disponibilidad', [CalendarioWebController::class, 'disponibilidad'])
        ->name('calendario.disponibilidad');
});

Route::middleware(['auth', 'role:administrador,recepcionista'])->group(function () {
    Route::post('calendario/citas', [CalendarioWebController::class, 'store'])->name('calendario.store');
    Route::post('calendario/citas/{id}/crear-ot', [CalendarioWebController::class, 'crearOt'])
        ->name('calendario.crear-ot');
});

Route::middleware(['auth', 'role:cliente'])->group(function () {
    Route::post('calendario/agendar', [CalendarioWebController::class, 'agendar'])->name('calendario.agendar');
    Route::post('calendario/citas/{id}/cancelar', [CalendarioWebController::class, 'cancelar'])->name('calendario.cancelar');
    Route::put('calendario/citas/{id}/reagendar', [CalendarioWebController::class, 'reagendar'])->name('calendario.reagendar');
});

Route::middleware(['auth', 'role:administrador,recepcionista,mecanico'])->group(function () {
    Route::post('calendario/citas/{id}/completar', [CalendarioWebController::class, 'completar'])->name('calendario.completar');
});

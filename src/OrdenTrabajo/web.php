<?php

use Illuminate\Support\Facades\Route;
use Src\OrdenTrabajo\Application\Controllers\OrdenTrabajoWebController;

Route::middleware(['auth', 'role:administrador,recepcionista'])->group(function () {
    Route::get('ordenes/create', [OrdenTrabajoWebController::class, 'create'])->name('ordenes.create');
    Route::post('ordenes', [OrdenTrabajoWebController::class, 'store'])->name('ordenes.store');
    Route::delete('ordenes/{orden}', [OrdenTrabajoWebController::class, 'destroy'])->name('ordenes.destroy');
    Route::put('ordenes/{orden}/asignar-mecanico', [OrdenTrabajoWebController::class, 'asignarMecanico'])
        ->name('ordenes.asignar-mecanico');
    Route::put('ordenes/{orden}/cambiar-estado', [OrdenTrabajoWebController::class, 'cambiarEstado'])
        ->name('ordenes.cambiar-estado');
});

Route::middleware(['auth', 'role:administrador,recepcionista,mecanico'])->group(function () {
    Route::get('ordenes', [OrdenTrabajoWebController::class, 'index'])->name('ordenes.index');
    Route::get('ordenes/{orden}/edit', [OrdenTrabajoWebController::class, 'edit'])->name('ordenes.edit');
    Route::put('ordenes/{orden}', [OrdenTrabajoWebController::class, 'update'])->name('ordenes.update');
});

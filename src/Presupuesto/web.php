<?php

use Illuminate\Support\Facades\Route;
use Src\Presupuesto\Application\Controllers\PortalPresupuestoWebController;
use Src\Presupuesto\Application\Controllers\PresupuestoWebController;

Route::middleware(['auth', 'role:administrador,recepcionista'])->group(function () {
    Route::get('presupuestos', [PresupuestoWebController::class, 'index'])->name('presupuestos.index');
    Route::get('presupuestos/{id}', [PresupuestoWebController::class, 'show'])->name('presupuestos.show');
});

Route::middleware(['auth', 'role:cliente'])->group(function () {
    Route::get('portal/presupuestos', [PortalPresupuestoWebController::class, 'index'])->name('portal.presupuestos.index');
    Route::get('portal/presupuestos/create', [PortalPresupuestoWebController::class, 'create'])->name('portal.presupuestos.create');
    Route::post('portal/presupuestos', [PortalPresupuestoWebController::class, 'store'])->name('portal.presupuestos.store');
    Route::get('portal/presupuestos/{id}', [PortalPresupuestoWebController::class, 'show'])->name('portal.presupuestos.show');
    Route::get('portal/presupuestos/{id}/edit', [PortalPresupuestoWebController::class, 'edit'])->name('portal.presupuestos.edit');
    Route::put('portal/presupuestos/{id}', [PortalPresupuestoWebController::class, 'update'])->name('portal.presupuestos.update');
    Route::post('portal/presupuestos/{id}/cancelar', [PortalPresupuestoWebController::class, 'cancelar'])->name('portal.presupuestos.cancelar');
});

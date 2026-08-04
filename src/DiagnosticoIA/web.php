<?php

use Illuminate\Support\Facades\Route;
use Src\DiagnosticoIA\Application\Controllers\DiagnosticoIaWebController;

// Generar / listar / ver detalle (solo lectura): recepción, admin y mecánico
Route::middleware(['auth', 'role:administrador,recepcionista,mecanico'])->group(function () {
    Route::get('diagnosticos-ia', [DiagnosticoIaWebController::class, 'index'])->name('diagnosticos-ia.index');
    Route::get('diagnosticos-ia/create', [DiagnosticoIaWebController::class, 'create'])->name('diagnosticos-ia.create');
    Route::post('diagnosticos-ia', [DiagnosticoIaWebController::class, 'store'])->name('diagnosticos-ia.store');
    Route::get('diagnosticos-ia/{ordenTrabajoId}', [DiagnosticoIaWebController::class, 'show'])->name('diagnosticos-ia.show');
});

// Revisar, confirmar y ejecutar reparación: solo mecánico (admin puede supervisar)
Route::middleware(['auth', 'role:administrador,mecanico'])->group(function () {
    Route::get('diagnosticos-ia/{ordenTrabajoId}/review', [DiagnosticoIaWebController::class, 'review'])->name('diagnosticos-ia.review');
    Route::put('diagnosticos-ia/{ordenTrabajoId}/revisar', [DiagnosticoIaWebController::class, 'revisar'])->name('diagnosticos-ia.revisar');
});

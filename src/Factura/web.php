<?php

use Illuminate\Support\Facades\Route;
use Src\Factura\Application\Controllers\FacturaWebController;

Route::middleware(['auth', 'role:administrador,recepcionista'])->group(function () {
    Route::get('facturas/{factura}/imprimir', [FacturaWebController::class, 'imprimir'])->name('facturas.imprimir');
    Route::get('facturas/{factura}/pdf', [FacturaWebController::class, 'exportPdf'])->name('facturas.pdf');
    Route::resource('facturas', FacturaWebController::class);
});

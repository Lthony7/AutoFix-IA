<?php

use Illuminate\Support\Facades\Route;
use Src\Factura\Application\Controllers\FacturaWebController;

Route::middleware(['auth', 'role:administrador,recepcionista'])->group(function () {
    Route::resource('facturas', FacturaWebController::class);
});

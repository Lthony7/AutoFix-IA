<?php

use Illuminate\Support\Facades\Route;
use Src\Producto\Application\Controllers\ProductoWebController;

Route::middleware(['auth', 'role:administrador,recepcionista'])->group(function () {
    Route::patch('repuestos/{repuesto}/stock', [ProductoWebController::class, 'ajustarStock'])
        ->name('repuestos.stock');
    Route::resource('repuestos', ProductoWebController::class)->except(['show']);
});

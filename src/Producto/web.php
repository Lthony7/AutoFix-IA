<?php

use Illuminate\Support\Facades\Route;
use Src\Producto\Application\Controllers\ProductoWebController;

Route::middleware(['auth', 'role:administrador,recepcionista'])->group(function () {
    Route::resource('repuestos', ProductoWebController::class)->except(['show']);
});

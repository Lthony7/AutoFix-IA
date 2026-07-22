<?php

use Illuminate\Support\Facades\Route;
use Src\Pago\Application\Controllers\PagoWebController;

Route::middleware(['auth', 'role:administrador,recepcionista'])->group(function () {
    Route::resource('pagos', PagoWebController::class)->except(['show']);
});

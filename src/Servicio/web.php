<?php

use Illuminate\Support\Facades\Route;
use Src\Servicio\Application\Controllers\ServicioWebController;

Route::middleware(['auth', 'role:administrador,recepcionista'])->group(function () {
    Route::resource('servicios', ServicioWebController::class)->except(['show']);
});

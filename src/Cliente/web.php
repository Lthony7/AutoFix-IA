<?php

use Illuminate\Support\Facades\Route;
use Src\Cliente\Application\Controllers\ClienteWebController;

Route::middleware(['auth', 'role:administrador,recepcionista'])->group(function () {
    Route::resource('clientes', ClienteWebController::class)->except(['show']);
});

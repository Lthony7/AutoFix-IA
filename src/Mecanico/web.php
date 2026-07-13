<?php

use Illuminate\Support\Facades\Route;
use Src\Mecanico\Application\Controllers\MecanicoWebController;

Route::middleware(['auth', 'role:administrador'])->group(function () {
    Route::resource('mecanicos', MecanicoWebController::class)->except(['show']);
});

<?php

use Illuminate\Support\Facades\Route;
use Src\Producto\Application\Controllers\ProductoWebController;

Route::middleware(['auth', 'role:administrador,recepcionista'])->group(function () {
    Route::patch('inventario/{id}/stock', [ProductoWebController::class, 'ajustarStock'])
        ->name('inventario.stock');
    Route::resource('inventario', ProductoWebController::class)
        ->parameters(['inventario' => 'id'])
        ->except(['show']);

    // Compatibilidad con la ruta antigua /repuestos
    Route::redirect('repuestos', '/inventario', 301);
    Route::get('repuestos/create', fn () => redirect()->route('inventario.create', [], 301));
    Route::get('repuestos/{id}/edit', fn (string $id) => redirect()->route('inventario.edit', $id, 301));
});

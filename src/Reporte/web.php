<?php

use Illuminate\Support\Facades\Route;
use Src\Reporte\Application\Controllers\HistorialWebController;
use Src\Reporte\Application\Controllers\PortalClienteWebController;
use Src\Reporte\Application\Controllers\ReporteWebController;

Route::middleware(['auth', 'role:administrador,recepcionista'])->group(function () {
    Route::get('reportes', [ReporteWebController::class, 'index'])->name('reportes.index');
    Route::get('reportes/export/excel', [ReporteWebController::class, 'exportExcel'])->name('reportes.export.excel');
    Route::get('reportes/export/pdf', [ReporteWebController::class, 'exportPdf'])->name('reportes.export.pdf');
    Route::get('historial', [HistorialWebController::class, 'index'])->name('historial.index');
    Route::get('historial/vehiculos/{vehiculoId}', [HistorialWebController::class, 'show'])->name('historial.vehiculo');
});

Route::middleware(['auth', 'role:cliente'])->group(function () {
    Route::get('portal/mis-vehiculos', [PortalClienteWebController::class, 'misVehiculos'])->name('portal.mis-vehiculos');
    Route::get('portal/mis-vehiculos/crear', [PortalClienteWebController::class, 'crearVehiculo'])->name('portal.vehiculos.create');
    Route::post('portal/mis-vehiculos', [PortalClienteWebController::class, 'guardarVehiculo'])->name('portal.vehiculos.store');
    Route::get('portal/mis-ordenes', [PortalClienteWebController::class, 'misOrdenes'])->name('portal.mis-ordenes');
    Route::get('portal/mis-ordenes/{orden}', [PortalClienteWebController::class, 'mostrarOrden'])->name('portal.mis-ordenes.show');
    Route::get('portal/facturas/{factura}', [PortalClienteWebController::class, 'mostrarFactura'])->name('portal.facturas.show');
    Route::get('portal/historial', [PortalClienteWebController::class, 'historial'])->name('portal.historial');
    Route::get('portal/mis-datos', [PortalClienteWebController::class, 'misDatos'])->name('portal.mis-datos');
    Route::put('portal/mis-datos', [PortalClienteWebController::class, 'actualizarDatos'])->name('portal.mis-datos.update');
});

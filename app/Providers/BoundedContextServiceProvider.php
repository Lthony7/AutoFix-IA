<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class BoundedContextServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadBoundedContextRoutes();
        $this->loadBoundedContextMigrations();
    }

    protected function loadBoundedContextRoutes(): void
    {
        $boundedContexts = [
            'Auth',
            'Cliente',
            'Vehiculo',
            'Mecanico',
            'Producto',
            'Servicio',
            'OrdenTrabajo',
            'DiagnosticoIA',
            'Factura',
            'Pago',
            'Reporte',
        ];

        foreach ($boundedContexts as $context) {
            $apiRoutesPath = base_path("src/{$context}/api.php");
            if (file_exists($apiRoutesPath)) {
                Route::prefix('api/v1')
                    ->middleware('api')
                    ->group($apiRoutesPath);
            }

            $webRoutesPath = base_path("src/{$context}/web.php");
            if (file_exists($webRoutesPath)) {
                Route::middleware('web')
                    ->group($webRoutesPath);
            }
        }
    }

    protected function loadBoundedContextMigrations(): void
    {
        $boundedContexts = [
            'Auth',
            'Cliente',
            'Vehiculo',
            'Mecanico',
            'Producto',
            'Servicio',
            'OrdenTrabajo',
            'DiagnosticoIA',
            'Factura',
            'Pago',
            'Reporte',
        ];

        foreach ($boundedContexts as $context) {
            $migrationsPath = base_path("src/{$context}/Infrastructure/Migrations");

            if (is_dir($migrationsPath)) {
                $this->loadMigrationsFrom($migrationsPath);
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Enums\FacturaEstado;
use App\Enums\OrdenEstado;
use App\Enums\PagoEstado;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Src\Cliente\Infrastructure\Models\ClienteEloquentModel;
use Src\Factura\Infrastructure\Models\DetalleFacturaEloquentModel;
use Src\Factura\Infrastructure\Models\FacturaEloquentModel;
use Src\Mecanico\Infrastructure\Models\MecanicoEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenRepuestoEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenServicioEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;
use Src\Pago\Infrastructure\Models\PagoEloquentModel;
use Src\Producto\Infrastructure\Models\ProductoEloquentModel;
use Src\Servicio\Infrastructure\Models\ServicioEloquentModel;
use Src\Vehiculo\Infrastructure\Models\VehiculoEloquentModel;

class AutofixDemoSeeder extends Seeder
{
    public function run(): void
    {
        $cliente = ClienteEloquentModel::where('numero_documento', '1712345678')->first();
        $vehiculo = VehiculoEloquentModel::where('placa', 'ABC-1234')->first();
        $mecanico = MecanicoEloquentModel::where('documento', '1711122233')->first();
        $servicioAceite = ServicioEloquentModel::where('nombre', 'Cambio de aceite')->first();
        $servicioFrenos = ServicioEloquentModel::where('nombre', 'Revisión de frenos')->first();
        $pastillas = ProductoEloquentModel::where('codigo', 'PAST-001')->first();
        $aceite = ProductoEloquentModel::where('codigo', 'ACEI-001')->first();

        if (!$cliente || !$vehiculo || !$servicioAceite || !$pastillas) {
            $this->command?->warn('AutofixDemoSeeder: faltan datos de Week1. Ejecuta AutofixWeek1Seeder primero.');
            return;
        }

        DB::transaction(function () use (
            $cliente,
            $vehiculo,
            $mecanico,
            $servicioAceite,
            $servicioFrenos,
            $pastillas,
            $aceite
        ) {
            $ordenFacturable = OrdenTrabajoEloquentModel::firstOrCreate(
                ['numero' => 'OT-DEMO-0001'],
                [
                    'cliente_id' => $cliente->id,
                    'vehiculo_id' => $vehiculo->id,
                    'mecanico_id' => $mecanico?->id,
                    'estado' => OrdenEstado::Finalizada,
                    'tipo_falla' => 'Frenos',
                    'falla_reportada' => 'Chirrido al frenar y revisión de aceite',
                    'kilometraje_ingreso' => 145200,
                    'prioridad' => 'media',
                    'observaciones' => 'Orden demo con factura y pago',
                ]
            );

            if ($ordenFacturable->ordenServicios()->count() === 0) {
                OrdenServicioEloquentModel::create([
                    'orden_trabajo_id' => $ordenFacturable->id,
                    'servicio_id' => $servicioAceite->id,
                    'precio' => $servicioAceite->precio_base,
                ]);

                if ($servicioFrenos) {
                    OrdenServicioEloquentModel::create([
                        'orden_trabajo_id' => $ordenFacturable->id,
                        'servicio_id' => $servicioFrenos->id,
                        'precio' => $servicioFrenos->precio_base,
                    ]);
                }
            }

            if ($ordenFacturable->ordenRepuestos()->count() === 0) {
                OrdenRepuestoEloquentModel::create([
                    'orden_trabajo_id' => $ordenFacturable->id,
                    'producto_id' => $pastillas->id,
                    'cantidad' => 1,
                    'precio_unitario' => $pastillas->precio,
                ]);

                if ($aceite) {
                    OrdenRepuestoEloquentModel::create([
                        'orden_trabajo_id' => $ordenFacturable->id,
                        'producto_id' => $aceite->id,
                        'cantidad' => 1,
                        'precio_unitario' => $aceite->precio,
                    ]);
                }

                // Descontar stock solo la primera vez
                $pastillas->decrement('stock', 1);
                $aceite?->decrement('stock', 1);
            }

            $ordenFacturable->load(['ordenServicios.servicio', 'ordenRepuestos.producto', 'factura']);

            if (!$ordenFacturable->factura) {
                $calc = FacturaEloquentModel::calcularDesdeOrden($ordenFacturable);
                $factura = FacturaEloquentModel::create([
                    'numero' => 'F-DEMO-0001',
                    'serie' => config('autofix.serie_default', 'F001'),
                    'orden_trabajo_id' => $ordenFacturable->id,
                    'cliente_id' => $cliente->id,
                    'fecha_emision' => now()->toDateString(),
                    'subtotal' => $calc['subtotal'],
                    'iva' => $calc['iva'],
                    'descuento' => $calc['descuento'],
                    'total' => $calc['total'],
                    'estado' => FacturaEstado::Emitida,
                    'observaciones' => 'Factura demo AUTOFIX IA',
                ]);

                foreach ($calc['detalles'] as $detalle) {
                    DetalleFacturaEloquentModel::create([
                        'factura_id' => $factura->id,
                        ...$detalle,
                    ]);
                }

                if (!$ordenFacturable->pago()->exists()) {
                    PagoEloquentModel::create([
                        'orden_trabajo_id' => $ordenFacturable->id,
                        'factura_id' => $factura->id,
                        'valor_servicios' => (float) $ordenFacturable->ordenServicios->sum('precio'),
                        'valor_repuestos' => (float) $ordenFacturable->ordenRepuestos->sum(
                            fn ($r) => $r->cantidad * $r->precio_unitario
                        ),
                        'descuento' => 0,
                        'total' => $calc['total'],
                        'estado' => PagoEstado::Pendiente,
                        'metodo_pago' => null,
                    ]);
                }
            }

            OrdenTrabajoEloquentModel::firstOrCreate(
                ['numero' => 'OT-DEMO-0002'],
                [
                    'cliente_id' => $cliente->id,
                    'vehiculo_id' => $vehiculo->id,
                    'mecanico_id' => $mecanico?->id,
                    'estado' => OrdenEstado::Pendiente,
                    'tipo_falla' => 'Motor',
                    'falla_reportada' => 'Motor se apaga en ralentí (para demo de Diagnóstico IA)',
                    'kilometraje_ingreso' => 145500,
                    'prioridad' => 'alta',
                    'observaciones' => 'Orden pendiente sin factura — usar Diagnóstico IA',
                ]
            );
        });

        $this->command?->info('Demo Semanas 2-4: OT-DEMO-0001 (factura+pago) y OT-DEMO-0002 (IA).');
    }
}

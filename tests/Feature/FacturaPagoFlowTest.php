<?php

namespace Tests\Feature;

use App\Enums\FacturaEstado;
use App\Enums\OrdenEstado;
use App\Enums\PagoEstado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Auth\Infrastructure\Models\UserEloquentModel;
use Src\Cliente\Infrastructure\Models\ClienteEloquentModel;
use Src\Factura\Infrastructure\Models\FacturaEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenServicioEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;
use Src\Pago\Infrastructure\Models\PagoEloquentModel;
use Src\Producto\Infrastructure\Models\ProductoEloquentModel;
use Src\Servicio\Infrastructure\Models\ServicioEloquentModel;
use Src\Vehiculo\Infrastructure\Models\VehiculoEloquentModel;
use Tests\TestCase;

class FacturaPagoFlowTest extends TestCase
{
    use RefreshDatabase;

    private UserEloquentModel $admin;

    private ClienteEloquentModel $cliente;

    private VehiculoEloquentModel $vehiculo;

    private ServicioEloquentModel $servicio;

    private ProductoEloquentModel $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserEloquentModel::factory()->administrador()->create();
        $this->cliente = ClienteEloquentModel::create([
            'tipo_documento' => 'CEDULA',
            'numero_documento' => '1799999999',
            'razon_social' => 'Cliente Test',
            'nombres' => 'Cliente',
            'apellidos' => 'Test',
            'direccion' => 'Quito',
            'telefono' => '0990000000',
            'email' => 'cliente-test@autofix.test',
            'estado' => true,
        ]);
        $this->vehiculo = VehiculoEloquentModel::create([
            'cliente_id' => $this->cliente->id,
            'placa' => 'TST-9999',
            'marca' => 'Toyota',
            'modelo' => 'Yaris',
            'anio' => 2018,
            'color' => 'Blanco',
            'kilometraje' => 80000,
            'tipo_combustible' => 'gasolina',
            'activo' => true,
        ]);
        $this->servicio = ServicioEloquentModel::create([
            'nombre' => 'Servicio test',
            'descripcion' => 'Desc',
            'precio_base' => 50,
            'activo' => true,
        ]);
        $this->producto = ProductoEloquentModel::create([
            'codigo' => 'REP-TEST-1',
            'nombre' => 'Repuesto test',
            'descripcion' => 'Desc',
            'precio' => 20,
            'stock' => 10,
            'stock_minimo' => 1,
            'activo' => true,
            'tipo_producto' => 'repuesto',
        ]);
    }

    public function test_genera_factura_desde_ot_y_pago_marca_factura_pagada(): void
    {
        $orden = OrdenTrabajoEloquentModel::create([
            'numero' => 'OT-TEST-0001',
            'cliente_id' => $this->cliente->id,
            'vehiculo_id' => $this->vehiculo->id,
            'estado' => OrdenEstado::Finalizada,
            'falla_reportada' => 'Prueba',
            'prioridad' => 'media',
        ]);

        OrdenServicioEloquentModel::create([
            'orden_trabajo_id' => $orden->id,
            'servicio_id' => $this->servicio->id,
            'precio' => 50,
        ]);

        $this->actingAs($this->admin)
            ->post(route('facturas.store'), [
                'ordenTrabajoId' => $orden->id,
                'fechaEmision' => now()->toDateString(),
                'descuento' => 0,
                'estado' => 'emitida',
            ])
            ->assertRedirect();

        $factura = FacturaEloquentModel::where('orden_trabajo_id', $orden->id)->first();
        $this->assertNotNull($factura);
        $this->assertSame(FacturaEstado::Emitida, $factura->estado);
        $this->assertGreaterThan(0, (float) $factura->total);
        $this->assertTrue($factura->detalles()->exists());

        $this->actingAs($this->admin)
            ->post(route('pagos.store'), [
                'ordenTrabajoId' => $orden->id,
                'estado' => 'pagado',
                'metodoPago' => 'efectivo',
            ])
            ->assertRedirect(route('pagos.index'));

        $pago = PagoEloquentModel::where('orden_trabajo_id', $orden->id)->first();
        $this->assertNotNull($pago);
        $this->assertSame($factura->id, $pago->factura_id);
        $this->assertSame(PagoEstado::Pagado, $pago->estado);
        $this->assertSame(FacturaEstado::Pagada, $factura->fresh()->estado);
    }

    public function test_no_permite_dos_facturas_para_la_misma_ot(): void
    {
        $orden = OrdenTrabajoEloquentModel::create([
            'numero' => 'OT-TEST-0002',
            'cliente_id' => $this->cliente->id,
            'vehiculo_id' => $this->vehiculo->id,
            'estado' => OrdenEstado::Finalizada,
            'falla_reportada' => 'Prueba',
        ]);

        OrdenServicioEloquentModel::create([
            'orden_trabajo_id' => $orden->id,
            'servicio_id' => $this->servicio->id,
            'precio' => 40,
        ]);

        $this->actingAs($this->admin)->post(route('facturas.store'), [
            'ordenTrabajoId' => $orden->id,
            'fechaEmision' => now()->toDateString(),
        ])->assertRedirect();

        $this->actingAs($this->admin)
            ->from(route('facturas.create'))
            ->post(route('facturas.store'), [
                'ordenTrabajoId' => $orden->id,
                'fechaEmision' => now()->toDateString(),
            ])
            ->assertSessionHasErrors();

        $this->assertSame(1, FacturaEloquentModel::where('orden_trabajo_id', $orden->id)->count());
    }

    public function test_descuenta_stock_al_crear_orden_con_repuestos(): void
    {
        $this->actingAs($this->admin)
            ->post(route('ordenes.store'), [
                'clienteId' => $this->cliente->id,
                'vehiculoId' => $this->vehiculo->id,
                'fallaReportada' => 'Necesita repuesto',
                'estado' => 'pendiente',
                'servicios' => [],
                'repuestos' => [
                    [
                        'productoId' => $this->producto->id,
                        'cantidad' => 2,
                        'precioUnitario' => 20,
                    ],
                ],
            ])
            ->assertRedirect(route('ordenes.index'));

        $this->assertSame(8, (int) $this->producto->fresh()->stock);
        $this->assertDatabaseCount('orden_repuesto', 1);
    }
}

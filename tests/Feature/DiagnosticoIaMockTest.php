<?php

namespace Tests\Feature;

use App\Enums\OrdenEstado;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Auth\Infrastructure\Models\UserEloquentModel;
use Src\Cliente\Infrastructure\Models\ClienteEloquentModel;
use Src\DiagnosticoIA\Infrastructure\Models\DiagnosticoIaEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;
use Src\Vehiculo\Infrastructure\Models\VehiculoEloquentModel;
use Tests\TestCase;

class DiagnosticoIaMockTest extends TestCase
{
    use RefreshDatabase;

    public function test_genera_diagnostico_ia_en_modo_mock(): void
    {
        $admin = UserEloquentModel::factory()->administrador()->create([
            'role' => UserRole::Administrador,
        ]);
        $cliente = ClienteEloquentModel::create([
            'tipo_documento' => 'CEDULA',
            'numero_documento' => '1788888888',
            'razon_social' => 'Cliente IA',
            'nombres' => 'Cliente',
            'apellidos' => 'IA',
            'direccion' => 'Quito',
            'telefono' => '0991111111',
            'email' => 'ia@autofix.test',
            'estado' => true,
        ]);
        $vehiculo = VehiculoEloquentModel::create([
            'cliente_id' => $cliente->id,
            'placa' => 'IA-0001',
            'marca' => 'Kia',
            'modelo' => 'Rio',
            'anio' => 2020,
            'kilometraje' => 30000,
            'tipo_combustible' => 'gasolina',
            'activo' => true,
        ]);
        $orden = OrdenTrabajoEloquentModel::create([
            'numero' => 'OT-IA-0001',
            'cliente_id' => $cliente->id,
            'vehiculo_id' => $vehiculo->id,
            'estado' => OrdenEstado::Pendiente,
            'falla_reportada' => 'Motor se apaga',
            'tipo_falla' => 'Motor',
        ]);

        $this->actingAs($admin)
            ->post(route('diagnosticos-ia.store'), [
                'ordenTrabajoId' => $orden->id,
                'tipoFalla' => 'Motor',
                'descripcion' => 'Se apaga en ralentí',
                'momento' => 'Al detenerme',
                'puedeCircular' => true,
                'urgencia' => 'alta',
            ])
            ->assertRedirect();

        $diagnostico = DiagnosticoIaEloquentModel::where('orden_trabajo_id', $orden->id)->first();
        $this->assertNotNull($diagnostico);
        $this->assertTrue($diagnostico->es_simulado);
        $this->assertSame(OrdenEstado::EnDiagnostico, $orden->fresh()->estado);
    }
}

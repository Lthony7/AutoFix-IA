<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Src\Auth\Infrastructure\Models\UserEloquentModel;
use Src\Cliente\Infrastructure\Models\ClienteEloquentModel;
use Src\Mecanico\Infrastructure\Models\MecanicoEloquentModel;
use Src\Producto\Infrastructure\Models\ProductoEloquentModel;
use Src\Servicio\Infrastructure\Models\ServicioEloquentModel;
use Src\Vehiculo\Infrastructure\Models\VehiculoEloquentModel;

class AutofixWeek1Seeder extends Seeder
{
    public function run(): void
    {
        UserEloquentModel::updateOrCreate(
            ['email' => 'admin@autofix.test'],
            [
                'name' => 'Administrador Autofix',
                'password' => 'password',
                'role' => UserRole::Administrador,
                'activo' => true,
            ]
        );

        UserEloquentModel::updateOrCreate(
            ['email' => 'recepcion@autofix.test'],
            [
                'name' => 'Ana Recepción',
                'password' => 'password',
                'role' => UserRole::Recepcionista,
                'activo' => true,
            ]
        );

        $mecanicoUser = UserEloquentModel::updateOrCreate(
            ['email' => 'mecanico@autofix.test'],
            [
                'name' => 'Carlos Mecánico',
                'password' => 'password',
                'role' => UserRole::Mecanico,
                'activo' => true,
            ]
        );

        $clienteUser = UserEloquentModel::updateOrCreate(
            ['email' => 'cliente@autofix.test'],
            [
                'name' => 'Luis Cliente',
                'password' => 'password',
                'role' => UserRole::Cliente,
                'activo' => true,
            ]
        );

        $cliente = ClienteEloquentModel::updateOrCreate(
            ['numero_documento' => '1712345678'],
            [
                'tipo_documento' => 'CEDULA',
                'razon_social' => 'Luis Pérez García',
                'nombres' => 'Luis',
                'apellidos' => 'Pérez García',
                'direccion' => 'Av. Amazonas N12-34, Quito',
                'telefono' => '0991234567',
                'email' => 'cliente@autofix.test',
                'estado' => true,
                'user_id' => $clienteUser->id,
            ]
        );

        VehiculoEloquentModel::updateOrCreate(
            ['placa' => 'ABC-1234'],
            [
                'cliente_id' => $cliente->id,
                'marca' => 'Chevrolet',
                'modelo' => 'Aveo',
                'anio' => 2015,
                'color' => 'Gris',
                'kilometraje' => 145000,
                'tipo_combustible' => 'gasolina',
                'observaciones' => 'Vehículo de demostración',
                'activo' => true,
            ]
        );

        MecanicoEloquentModel::updateOrCreate(
            ['documento' => '1711122233'],
            [
                'user_id' => $mecanicoUser->id,
                'nombres' => 'Carlos',
                'apellidos' => 'Mendoza',
                'telefono' => '0987654321',
                'email' => 'mecanico@autofix.test',
                'especialidad' => 'Frenos y suspensión',
                'horario_disponible' => 'Lun-Vie 08:00-17:00',
                'activo' => true,
            ]
        );

        $servicios = [
            ['nombre' => 'Cambio de aceite', 'descripcion' => 'Cambio de aceite y filtro', 'precio_base' => 35.00],
            ['nombre' => 'Revisión de frenos', 'descripcion' => 'Inspección y ajuste de frenos', 'precio_base' => 45.00],
            ['nombre' => 'Alineación y balanceo', 'descripcion' => 'Alineación y balanceo de llantas', 'precio_base' => 40.00],
            ['nombre' => 'Sistema eléctrico', 'descripcion' => 'Diagnóstico eléctrico', 'precio_base' => 50.00],
            ['nombre' => 'Motor', 'descripcion' => 'Revisión de motor', 'precio_base' => 60.00],
            ['nombre' => 'Suspensión', 'descripcion' => 'Revisión de suspensión', 'precio_base' => 55.00],
            ['nombre' => 'Transmisión', 'descripcion' => 'Revisión de transmisión', 'precio_base' => 70.00],
            ['nombre' => 'Aire acondicionado', 'descripcion' => 'Revisión de A/C', 'precio_base' => 45.00],
        ];

        foreach ($servicios as $servicio) {
            ServicioEloquentModel::updateOrCreate(
                ['nombre' => $servicio['nombre']],
                array_merge($servicio, ['activo' => true])
            );
        }

        ProductoEloquentModel::updateOrCreate(
            ['codigo' => 'PAST-001'],
            [
                'nombre' => 'Pastillas de freno delanteras',
                'descripcion' => 'Juego de pastillas cerámicas',
                'precio' => 28.50,
                'stock' => 20,
                'stock_minimo' => 5,
                'activo' => true,
                'tipo_producto' => 'repuesto',
                'categoria' => 'Frenos',
                'proveedor' => 'AutoParts SA',
            ]
        );

        ProductoEloquentModel::updateOrCreate(
            ['codigo' => 'ACEI-001'],
            [
                'nombre' => 'Aceite 5W-30 sintético',
                'descripcion' => 'Aceite motor 4L',
                'precio' => 22.00,
                'stock' => 40,
                'stock_minimo' => 10,
                'activo' => true,
                'tipo_producto' => 'repuesto',
                'categoria' => 'Lubricantes',
                'proveedor' => 'LubriCenter',
            ]
        );

        $this->command?->info('Usuarios demo (password: password):');
        $this->command?->info('- admin@autofix.test / recepcion@autofix.test / mecanico@autofix.test / cliente@autofix.test');
        $this->command?->info('Catálogo: 8 servicios + 2 repuestos de ejemplo');
    }
}

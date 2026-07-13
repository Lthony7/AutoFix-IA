<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Src\Auth\Infrastructure\Models\UserEloquentModel;
use Src\Cliente\Infrastructure\Models\ClienteEloquentModel;
use Src\Mecanico\Infrastructure\Models\MecanicoEloquentModel;
use Src\Vehiculo\Infrastructure\Models\VehiculoEloquentModel;

class AutofixWeek1Seeder extends Seeder
{
    public function run(): void
    {
        $admin = UserEloquentModel::updateOrCreate(
            ['email' => 'admin@autofix.test'],
            [
                'name' => 'Administrador Autofix',
                'password' => 'password',
                'role' => UserRole::Administrador,
                'activo' => true,
            ]
        );

        $recepcionista = UserEloquentModel::updateOrCreate(
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
                'observaciones' => 'Vehículo de demostración Semana 1',
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

        $this->command?->info('Usuarios demo Semana 1 (password: password):');
        $this->command?->info('- admin@autofix.test (administrador)');
        $this->command?->info('- recepcion@autofix.test (recepcionista)');
        $this->command?->info('- mecanico@autofix.test (mecanico)');
        $this->command?->info('- cliente@autofix.test (cliente)');
        $this->command?->info("Admin ID: {$admin->id} / Recepción ID: {$recepcionista->id}");
    }
}

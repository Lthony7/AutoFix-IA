<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Mecanico\Infrastructure\Models\MecanicoEloquentModel;

class MecanicosTallerSeeder extends Seeder
{
    public function run(): void
    {
        $mecanicos = [
            [
                'documento' => '1701234567',
                'nombres' => 'Roberto',
                'apellidos' => 'Vásquez León',
                'telefono' => '0987123456',
                'email' => 'roberto.vasquez@autofix.ec',
                'especialidad' => 'Frenos (discos, pastillas, ABS y freno de mano)',
                'horario_disponible' => 'Lun-Vie 08:00-17:00',
            ],
            [
                'documento' => '1702345678',
                'nombres' => 'Miguel',
                'apellidos' => 'Andrade Cevallos',
                'telefono' => '0987234567',
                'email' => 'miguel.andrade@autofix.ec',
                'especialidad' => 'Suspensión y dirección (amortiguadores, rótulas, cremallera)',
                'horario_disponible' => 'Lun-Vie 08:00-17:00',
            ],
            [
                'documento' => '1703456789',
                'nombres' => 'Diego',
                'apellidos' => 'Morales Paredes',
                'telefono' => '0987345678',
                'email' => 'diego.morales@autofix.ec',
                'especialidad' => 'Motor (reparación, sincronización y sobrecalentamiento)',
                'horario_disponible' => 'Lun-Sáb 08:00-13:00',
            ],
            [
                'documento' => '1704567890',
                'nombres' => 'Andrés',
                'apellidos' => 'Salazar Mejía',
                'telefono' => '0987456789',
                'email' => 'andres.salazar@autofix.ec',
                'especialidad' => 'Transmisión manual y automática',
                'horario_disponible' => 'Lun-Vie 09:00-18:00',
            ],
            [
                'documento' => '1705678901',
                'nombres' => 'Jorge',
                'apellidos' => 'Castillo Ríos',
                'telefono' => '0987567890',
                'email' => 'jorge.castillo@autofix.ec',
                'especialidad' => 'Sistema eléctrico y baterías',
                'horario_disponible' => 'Lun-Vie 08:00-17:00',
            ],
            [
                'documento' => '1706789012',
                'nombres' => 'Fernando',
                'apellidos' => 'Guerrero Páez',
                'telefono' => '0987678901',
                'email' => 'fernando.guerrero@autofix.ec',
                'especialidad' => 'Inyección electrónica y sensores',
                'horario_disponible' => 'Lun-Vie 08:00-16:00',
            ],
            [
                'documento' => '1707890123',
                'nombres' => 'Luis',
                'apellidos' => 'Herrera Campos',
                'telefono' => '0987789012',
                'email' => 'luis.herrera@autofix.ec',
                'especialidad' => 'Aire acondicionado y clima',
                'horario_disponible' => 'Lun-Vie 08:00-17:00',
            ],
            [
                'documento' => '1708901234',
                'nombres' => 'Carlos',
                'apellidos' => 'Núñez Freire',
                'telefono' => '0987890123',
                'email' => 'carlos.nunez@autofix.ec',
                'especialidad' => 'Alineación, balanceo y llantas',
                'horario_disponible' => 'Lun-Sáb 08:00-14:00',
            ],
            [
                'documento' => '1709012345',
                'nombres' => 'Pedro',
                'apellidos' => 'Ramírez Ortiz',
                'telefono' => '0987901234',
                'email' => 'pedro.ramirez@autofix.ec',
                'especialidad' => 'Embrague y caja de cambios',
                'horario_disponible' => 'Lun-Vie 08:00-17:00',
            ],
            [
                'documento' => '1710123456',
                'nombres' => 'Esteban',
                'apellidos' => 'Torres Beltrán',
                'telefono' => '0987012345',
                'email' => 'esteban.torres@autofix.ec',
                'especialidad' => 'Diagnóstico computarizado (scanner OBD)',
                'horario_disponible' => 'Lun-Vie 08:00-17:00',
            ],
            [
                'documento' => '1711234567',
                'nombres' => 'Héctor',
                'apellidos' => 'López Villacís',
                'telefono' => '0987123450',
                'email' => 'hector.lopez@autofix.ec',
                'especialidad' => 'Mantenimiento general y lubricación',
                'horario_disponible' => 'Lun-Sáb 07:30-16:00',
            ],
            [
                'documento' => '1712345670',
                'nombres' => 'Manuel',
                'apellidos' => 'Cabrera Suárez',
                'telefono' => '0987234560',
                'email' => 'manuel.cabrera@autofix.ec',
                'especialidad' => 'Escape, catalizador y emisiones',
                'horario_disponible' => 'Lun-Vie 08:00-17:00',
            ],
        ];

        foreach ($mecanicos as $mecanico) {
            MecanicoEloquentModel::updateOrCreate(
                ['documento' => $mecanico['documento']],
                array_merge($mecanico, [
                    'user_id' => null,
                    'activo' => true,
                ])
            );
        }

        $this->command?->info('Se insertaron/actualizaron '.count($mecanicos).' mecánicos especialistas del taller.');
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Cliente\Infrastructure\Models\ClienteEloquentModel;
use Src\Vehiculo\Infrastructure\Models\VehiculoEloquentModel;

class ClientesVehiculosTallerSeeder extends Seeder
{
    public function run(): void
    {
        $clientes = [
            [
                'documento' => '1712345601',
                'nombres' => 'María Elena',
                'apellidos' => 'Rodríguez Páez',
                'direccion' => 'Av. 6 de Diciembre N34-120, Quito',
                'telefono' => '0998123401',
                'email' => 'maria.rodriguez@correo.ec',
                'vehiculos' => [
                    ['placa' => 'PBA-1234', 'marca' => 'Chevrolet', 'modelo' => 'Spark GT', 'anio' => 2018, 'color' => 'Rojo', 'km' => 72000, 'combustible' => 'gasolina'],
                    ['placa' => 'PBA-5678', 'marca' => 'Kia', 'modelo' => 'Rio', 'anio' => 2021, 'color' => 'Blanco', 'km' => 38000, 'combustible' => 'gasolina'],
                    ['placa' => 'PBA-9012', 'marca' => 'Hyundai', 'modelo' => 'Tucson', 'anio' => 2019, 'color' => 'Gris', 'km' => 91000, 'combustible' => 'gasolina'],
                ],
            ],
            [
                'documento' => '1712345602',
                'nombres' => 'Carlos Andrés',
                'apellidos' => 'Mendoza Villacís',
                'direccion' => 'Calle Guayas 8-45 y Av. Amazonas, Quito',
                'telefono' => '0987654321',
                'email' => 'carlos.mendoza@correo.ec',
                'vehiculos' => [
                    ['placa' => 'PCB-2345', 'marca' => 'Toyota', 'modelo' => 'Corolla', 'anio' => 2016, 'color' => 'Plata', 'km' => 145000, 'combustible' => 'gasolina'],
                ],
            ],
            [
                'documento' => '1712345603',
                'nombres' => 'Ana Lucía',
                'apellidos' => 'García Benítez',
                'direccion' => 'Cdla. Kennedy Norte Mz 12 Villa 4, Guayaquil',
                'telefono' => '0992233445',
                'email' => 'ana.garcia@correo.ec',
                'vehiculos' => [
                    ['placa' => 'GSE-3456', 'marca' => 'Nissan', 'modelo' => 'Versa', 'anio' => 2020, 'color' => 'Azul', 'km' => 54000, 'combustible' => 'gasolina'],
                    ['placa' => 'GSE-7890', 'marca' => 'Chevrolet', 'modelo' => 'D-Max', 'anio' => 2017, 'color' => 'Negro', 'km' => 128000, 'combustible' => 'diesel'],
                ],
            ],
            [
                'documento' => '1712345604',
                'nombres' => 'José Luis',
                'apellidos' => 'Herrera Campos',
                'direccion' => 'Av. Solano 12-30, Cuenca',
                'telefono' => '0988112233',
                'email' => 'jose.herrera@correo.ec',
                'vehiculos' => [
                    ['placa' => 'AZE-1122', 'marca' => 'Mazda', 'modelo' => 'CX-5', 'anio' => 2022, 'color' => 'Rojo', 'km' => 22000, 'combustible' => 'gasolina'],
                    ['placa' => 'AZE-3344', 'marca' => 'Suzuki', 'modelo' => 'Swift', 'anio' => 2015, 'color' => 'Blanco', 'km' => 156000, 'combustible' => 'gasolina'],
                    ['placa' => 'AZE-5566', 'marca' => 'Ford', 'modelo' => 'Escape', 'anio' => 2018, 'color' => 'Gris', 'km' => 98000, 'combustible' => 'gasolina'],
                ],
            ],
            [
                'documento' => '1712345605',
                'nombres' => 'Patricia',
                'apellidos' => 'Salazar Mejía',
                'direccion' => 'Av. Eloy Alfaro N48-200, Quito',
                'telefono' => '0993344556',
                'email' => 'patricia.salazar@correo.ec',
                'vehiculos' => [
                    ['placa' => 'PCC-6677', 'marca' => 'Volkswagen', 'modelo' => 'Gol', 'anio' => 2014, 'color' => 'Negro', 'km' => 178000, 'combustible' => 'gasolina'],
                ],
            ],
            [
                'documento' => '1712345606',
                'nombres' => 'Diego Fernando',
                'apellidos' => 'Castillo Ríos',
                'direccion' => 'Urdesa Central Calle Principal 214, Guayaquil',
                'telefono' => '0987001122',
                'email' => 'diego.castillo@correo.ec',
                'vehiculos' => [
                    ['placa' => 'GSF-7788', 'marca' => 'Chevrolet', 'modelo' => 'Sail', 'anio' => 2019, 'color' => 'Blanco', 'km' => 67000, 'combustible' => 'gasolina'],
                    ['placa' => 'GSF-8899', 'marca' => 'Toyota', 'modelo' => 'Hilux', 'anio' => 2021, 'color' => 'Plata', 'km' => 41000, 'combustible' => 'diesel'],
                    ['placa' => 'GSF-9900', 'marca' => 'Honda', 'modelo' => 'CR-V', 'anio' => 2017, 'color' => 'Azul', 'km' => 112000, 'combustible' => 'gasolina'],
                    ['placa' => 'GSF-1011', 'marca' => 'Kia', 'modelo' => 'Sportage', 'anio' => 2023, 'color' => 'Gris', 'km' => 15000, 'combustible' => 'gasolina'],
                ],
            ],
            [
                'documento' => '1712345607',
                'nombres' => 'Lucía Fernanda',
                'apellidos' => 'Torres Beltrán',
                'direccion' => 'Barrio El Batán Calle 3 N12-18, Cuenca',
                'telefono' => '0994455667',
                'email' => 'lucia.torres@correo.ec',
                'vehiculos' => [
                    ['placa' => 'AZF-2022', 'marca' => 'Hyundai', 'modelo' => 'Accent', 'anio' => 2016, 'color' => 'Rojo', 'km' => 134000, 'combustible' => 'gasolina'],
                ],
            ],
            [
                'documento' => '1712345608',
                'nombres' => 'Andrés Felipe',
                'apellidos' => 'Núñez Freire',
                'direccion' => 'Cumbayá Calle El Colegio s/n, Quito',
                'telefono' => '0985566778',
                'email' => 'andres.nunez@correo.ec',
                'vehiculos' => [
                    ['placa' => 'PCD-3033', 'marca' => 'BMW', 'modelo' => 'X1', 'anio' => 2020, 'color' => 'Negro', 'km' => 48000, 'combustible' => 'gasolina'],
                    ['placa' => 'PCD-4044', 'marca' => 'Chevrolet', 'modelo' => 'Tracker', 'anio' => 2022, 'color' => 'Blanco', 'km' => 29000, 'combustible' => 'gasolina'],
                ],
            ],
            [
                'documento' => '1712345609',
                'nombres' => 'Rosa María',
                'apellidos' => 'López Villacís',
                'direccion' => 'Av. Francisco de Orellana, Ed. Torres del Río, Guayaquil',
                'telefono' => '0996677889',
                'email' => 'rosa.lopez@correo.ec',
                'vehiculos' => [
                    ['placa' => 'GSG-5055', 'marca' => 'Renault', 'modelo' => 'Duster', 'anio' => 2018, 'color' => 'Café', 'km' => 89000, 'combustible' => 'gasolina'],
                ],
            ],
            [
                'documento' => '1712345610',
                'nombres' => 'Miguel Ángel',
                'apellidos' => 'Ramírez Ortiz',
                'direccion' => 'La Floresta Calle Lugo N24-50, Quito',
                'telefono' => '0987788990',
                'email' => 'miguel.ramirez@correo.ec',
                'vehiculos' => [
                    ['placa' => 'PCE-6066', 'marca' => 'Toyota', 'modelo' => 'RAV4', 'anio' => 2019, 'color' => 'Plata', 'km' => 76000, 'combustible' => 'gasolina'],
                    ['placa' => 'PCE-7077', 'marca' => 'Yamaha', 'modelo' => 'FZ25', 'anio' => 2021, 'color' => 'Negro', 'km' => 18000, 'combustible' => 'gasolina'],
                    ['placa' => 'PCE-8088', 'marca' => 'Chevrolet', 'modelo' => 'Aveo', 'anio' => 2013, 'color' => 'Gris', 'km' => 198000, 'combustible' => 'gasolina'],
                ],
            ],
            [
                'documento' => '1712345611',
                'nombres' => 'Elena',
                'apellidos' => 'Cabrera Suárez',
                'direccion' => 'Av. América N34-88, Quito',
                'telefono' => '0998899001',
                'email' => 'elena.cabrera@correo.ec',
                'vehiculos' => [
                    ['placa' => 'PCF-9099', 'marca' => 'Nissan', 'modelo' => 'X-Trail', 'anio' => 2020, 'color' => 'Blanco', 'km' => 61000, 'combustible' => 'gasolina'],
                ],
            ],
            [
                'documento' => '1712345612',
                'nombres' => 'Fernando',
                'apellidos' => 'Guerrero Páez',
                'direccion' => 'Sauces 9 Mz 240 Villa 12, Guayaquil',
                'telefono' => '0989900112',
                'email' => 'fernando.guerrero@correo.ec',
                'vehiculos' => [
                    ['placa' => 'GSH-1112', 'marca' => 'Mitsubishi', 'modelo' => 'L200', 'anio' => 2017, 'color' => 'Blanco', 'km' => 142000, 'combustible' => 'diesel'],
                    ['placa' => 'GSH-1314', 'marca' => 'Chevrolet', 'modelo' => 'Grand Vitara', 'anio' => 2015, 'color' => 'Verde', 'km' => 167000, 'combustible' => 'gasolina'],
                ],
            ],
            [
                'documento' => '1712345613',
                'nombres' => 'Valeria',
                'apellidos' => 'Andrade Cevallos',
                'direccion' => 'Av. Remigio Crespo 7-45, Cuenca',
                'telefono' => '0990011223',
                'email' => 'valeria.andrade@correo.ec',
                'vehiculos' => [
                    ['placa' => 'AZG-1516', 'marca' => 'Kia', 'modelo' => 'Picanto', 'anio' => 2021, 'color' => 'Rojo', 'km' => 34000, 'combustible' => 'gasolina'],
                    ['placa' => 'AZG-1718', 'marca' => 'Hyundai', 'modelo' => 'Creta', 'anio' => 2022, 'color' => 'Gris', 'km' => 27000, 'combustible' => 'gasolina'],
                    ['placa' => 'AZG-1920', 'marca' => 'Chevrolet', 'modelo' => 'Onix', 'anio' => 2020, 'color' => 'Negro', 'km' => 58000, 'combustible' => 'gasolina'],
                ],
            ],
            [
                'documento' => '1712345614',
                'nombres' => 'Ricardo',
                'apellidos' => 'Vásquez León',
                'direccion' => 'Carcelén Bajo Calle A N72-10, Quito',
                'telefono' => '0981122334',
                'email' => 'ricardo.vasquez@correo.ec',
                'vehiculos' => [
                    ['placa' => 'PCG-2122', 'marca' => 'Ford', 'modelo' => 'Ranger', 'anio' => 2019, 'color' => 'Azul', 'km' => 95000, 'combustible' => 'diesel'],
                ],
            ],
            [
                'documento' => '1712345615',
                'nombres' => 'Gabriela',
                'apellidos' => 'Morales Paredes',
                'direccion' => 'Miraflores Av. Principal 450, Guayaquil',
                'telefono' => '0992233440',
                'email' => 'gabriela.morales@correo.ec',
                'vehiculos' => [
                    ['placa' => 'GSI-2324', 'marca' => 'Toyota', 'modelo' => 'Yaris', 'anio' => 2018, 'color' => 'Blanco', 'km' => 82000, 'combustible' => 'gasolina'],
                    ['placa' => 'GSI-2526', 'marca' => 'Suzuki', 'modelo' => 'Vitara', 'anio' => 2021, 'color' => 'Naranja', 'km' => 39000, 'combustible' => 'gasolina'],
                ],
            ],
            [
                'documento' => '1712345616',
                'nombres' => 'Héctor',
                'apellidos' => 'Paredes Ortiz',
                'direccion' => 'Av. Patria y 10 de Agosto, Quito',
                'telefono' => '0983344550',
                'email' => 'hector.paredes@correo.ec',
                'vehiculos' => [
                    ['placa' => 'PCH-2728', 'marca' => 'Jeep', 'modelo' => 'Renegade', 'anio' => 2020, 'color' => 'Rojo', 'km' => 52000, 'combustible' => 'gasolina'],
                    ['placa' => 'PCH-2930', 'marca' => 'Chevrolet', 'modelo' => 'Captiva', 'anio' => 2016, 'color' => 'Plata', 'km' => 139000, 'combustible' => 'gasolina'],
                    ['placa' => 'PCH-3132', 'marca' => 'Nissan', 'modelo' => 'Frontier', 'anio' => 2022, 'color' => 'Negro', 'km' => 31000, 'combustible' => 'diesel'],
                ],
            ],
            [
                'documento' => '1712345617',
                'nombres' => 'Camila',
                'apellidos' => 'Ibáñez Cordero',
                'direccion' => 'Sector Yanuncay Calle de los Álamos, Cuenca',
                'telefono' => '0994455660',
                'email' => 'camila.ibanez@correo.ec',
                'vehiculos' => [
                    ['placa' => 'AZH-3334', 'marca' => 'Mazda', 'modelo' => '3', 'anio' => 2019, 'color' => 'Gris', 'km' => 71000, 'combustible' => 'gasolina'],
                ],
            ],
            [
                'documento' => '1712345618',
                'nombres' => 'Esteban',
                'apellidos' => 'Ríos Cevallos',
                'direccion' => 'Av. Juan Tanca Marengo km 2.5, Guayaquil',
                'telefono' => '0985566770',
                'email' => 'esteban.rios@correo.ec',
                'vehiculos' => [
                    ['placa' => 'GSJ-3536', 'marca' => 'Isuzu', 'modelo' => 'D-Max', 'anio' => 2018, 'color' => 'Blanco', 'km' => 118000, 'combustible' => 'diesel'],
                    ['placa' => 'GSJ-3738', 'marca' => 'Chevrolet', 'modelo' => 'Spark', 'anio' => 2015, 'color' => 'Amarillo', 'km' => 162000, 'combustible' => 'gasolina'],
                ],
            ],
            [
                'documento' => '1712345619',
                'nombres' => 'Daniela',
                'apellidos' => 'Peña Alarcón',
                'direccion' => 'Conocoto Calle Los Pinos E12-40, Quito',
                'telefono' => '0996677880',
                'email' => 'daniela.pena@correo.ec',
                'vehiculos' => [
                    ['placa' => 'PCI-3940', 'marca' => 'Honda', 'modelo' => 'Civic', 'anio' => 2017, 'color' => 'Azul', 'km' => 105000, 'combustible' => 'gasolina'],
                    ['placa' => 'PCI-4142', 'marca' => 'Hyundai', 'modelo' => 'Santa Fe', 'anio' => 2021, 'color' => 'Negro', 'km' => 44000, 'combustible' => 'gasolina'],
                    ['placa' => 'PCI-4344', 'marca' => 'Chevrolet', 'modelo' => 'Groove', 'anio' => 2023, 'color' => 'Blanco', 'km' => 12000, 'combustible' => 'gasolina'],
                ],
            ],
            [
                'documento' => '1712345620',
                'nombres' => 'Santiago',
                'apellidos' => 'León Espinoza',
                'direccion' => 'Av. de las Américas y Av. España, Cuenca',
                'telefono' => '0987788991',
                'email' => 'santiago.leon@correo.ec',
                'vehiculos' => [
                    ['placa' => 'AZI-4546', 'marca' => 'Toyota', 'modelo' => 'Fortuner', 'anio' => 2020, 'color' => 'Plata', 'km' => 68000, 'combustible' => 'diesel'],
                    ['placa' => 'AZI-4748', 'marca' => 'Kia', 'modelo' => 'Seltos', 'anio' => 2022, 'color' => 'Rojo', 'km' => 25000, 'combustible' => 'gasolina'],
                ],
            ],
        ];

        $totalVehiculos = 0;

        foreach ($clientes as $data) {
            $vehiculos = $data['vehiculos'];
            unset($data['vehiculos']);

            $razon = trim($data['nombres'].' '.$data['apellidos']);

            $cliente = ClienteEloquentModel::updateOrCreate(
                ['numero_documento' => $data['documento']],
                [
                    'tipo_documento' => 'CEDULA',
                    'razon_social' => $razon,
                    'nombres' => $data['nombres'],
                    'apellidos' => $data['apellidos'],
                    'direccion' => $data['direccion'],
                    'telefono' => $data['telefono'],
                    'email' => $data['email'],
                    'estado' => true,
                    'user_id' => null,
                ]
            );

            foreach ($vehiculos as $vehiculo) {
                VehiculoEloquentModel::updateOrCreate(
                    ['placa' => $vehiculo['placa']],
                    [
                        'cliente_id' => $cliente->id,
                        'marca' => $vehiculo['marca'],
                        'modelo' => $vehiculo['modelo'],
                        'anio' => $vehiculo['anio'],
                        'color' => $vehiculo['color'],
                        'kilometraje' => $vehiculo['km'],
                        'tipo_combustible' => $vehiculo['combustible'],
                        'observaciones' => null,
                        'activo' => true,
                    ]
                );
                $totalVehiculos++;
            }
        }

        $this->command?->info('Clientes: '.count($clientes).' | Vehículos: '.$totalVehiculos);
    }
}

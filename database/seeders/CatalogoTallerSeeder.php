<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Producto\Infrastructure\Models\ProductoEloquentModel;
use Src\Servicio\Infrastructure\Models\ServicioEloquentModel;

class CatalogoTallerSeeder extends Seeder
{
    public function run(): void
    {
        $servicios = [
            ['nombre' => 'Cambio de aceite', 'descripcion' => 'Cambio de aceite y filtro de motor', 'precio_base' => 35.00],
            ['nombre' => 'Revisión de frenos', 'descripcion' => 'Inspección y ajuste del sistema de frenos', 'precio_base' => 45.00],
            ['nombre' => 'Cambio de pastillas de freno', 'descripcion' => 'Suministro e instalación de pastillas', 'precio_base' => 55.00],
            ['nombre' => 'Alineación y balanceo', 'descripcion' => 'Alineación computarizada y balanceo de llantas', 'precio_base' => 40.00],
            ['nombre' => 'Sistema eléctrico', 'descripcion' => 'Diagnóstico de batería, alternador y cableado', 'precio_base' => 50.00],
            ['nombre' => 'Revisión de motor', 'descripcion' => 'Inspección general de motor y sincronización', 'precio_base' => 60.00],
            ['nombre' => 'Suspensión y dirección', 'descripcion' => 'Revisión de amortiguadores, rótulas y cremallera', 'precio_base' => 55.00],
            ['nombre' => 'Transmisión', 'descripcion' => 'Diagnóstico de caja manual o automática', 'precio_base' => 70.00],
            ['nombre' => 'Embrague', 'descripcion' => 'Revisión y ajuste de embrague', 'precio_base' => 65.00],
            ['nombre' => 'Aire acondicionado', 'descripcion' => 'Carga de gas y revisión de clima', 'precio_base' => 45.00],
            ['nombre' => 'Inyección electrónica', 'descripcion' => 'Limpieza y diagnóstico de sensores', 'precio_base' => 58.00],
            ['nombre' => 'Diagnóstico computarizado', 'descripcion' => 'Lectura de fallas con scanner OBD', 'precio_base' => 30.00],
            ['nombre' => 'Sistema de escape', 'descripcion' => 'Revisión de catalizador y escapes', 'precio_base' => 42.00],
            ['nombre' => 'Mantenimiento preventivo', 'descripcion' => 'Paquete de lubricación y revisión general', 'precio_base' => 80.00],
        ];

        foreach ($servicios as $servicio) {
            ServicioEloquentModel::updateOrCreate(
                ['nombre' => $servicio['nombre']],
                array_merge($servicio, ['activo' => true])
            );
        }

        $repuestos = [
            // Lubricantes — cambio de aceite / mantenimiento (un SKU por grado)
            ['codigo' => 'ACEI-0W20', 'nombre' => 'Aceite 0W-20 sintético 4L', 'descripcion' => 'Motor gasolina, bajo consumo', 'precio' => 26.00, 'stock' => 30, 'stock_minimo' => 8, 'categoria' => 'Lubricantes', 'proveedor' => 'LubriCenter'],
            ['codigo' => 'ACEI-5W20', 'nombre' => 'Aceite 5W-20 sintético 4L', 'descripcion' => 'Motor gasolina', 'precio' => 24.00, 'stock' => 28, 'stock_minimo' => 8, 'categoria' => 'Lubricantes', 'proveedor' => 'LubriCenter'],
            ['codigo' => 'ACEI-5W30', 'nombre' => 'Aceite 5W-30 sintético 4L', 'descripcion' => 'Uso general gasolina/diésel ligero', 'precio' => 22.00, 'stock' => 45, 'stock_minimo' => 12, 'categoria' => 'Lubricantes', 'proveedor' => 'LubriCenter'],
            ['codigo' => 'ACEI-5W40', 'nombre' => 'Aceite 5W-40 sintético 4L', 'descripcion' => 'Alto rendimiento / turbo', 'precio' => 25.00, 'stock' => 32, 'stock_minimo' => 8, 'categoria' => 'Lubricantes', 'proveedor' => 'LubriCenter'],
            ['codigo' => 'ACEI-10W30', 'nombre' => 'Aceite 10W-30 mineral 4L', 'descripcion' => 'Motores convencionales', 'precio' => 16.00, 'stock' => 40, 'stock_minimo' => 10, 'categoria' => 'Lubricantes', 'proveedor' => 'LubriCenter'],
            ['codigo' => 'ACEI-10W40', 'nombre' => 'Aceite 10W-40 semiséntetico 4L', 'descripcion' => 'Uso mixto ciudad/carretera', 'precio' => 18.50, 'stock' => 38, 'stock_minimo' => 10, 'categoria' => 'Lubricantes', 'proveedor' => 'LubriCenter'],
            ['codigo' => 'ACEI-15W40', 'nombre' => 'Aceite 15W-40 diésel 4L', 'descripcion' => 'Motores diésel', 'precio' => 19.00, 'stock' => 24, 'stock_minimo' => 6, 'categoria' => 'Lubricantes', 'proveedor' => 'LubriCenter'],
            ['codigo' => 'ACEI-20W50', 'nombre' => 'Aceite 20W-50 mineral 4L', 'descripcion' => 'Motores de alto kilometraje', 'precio' => 15.00, 'stock' => 22, 'stock_minimo' => 6, 'categoria' => 'Lubricantes', 'proveedor' => 'LubriCenter'],
            ['codigo' => 'ACEI-ATF', 'nombre' => 'Aceite transmisión ATF 1L', 'descripcion' => 'Caja automática', 'precio' => 12.00, 'stock' => 20, 'stock_minimo' => 5, 'categoria' => 'Lubricantes', 'proveedor' => 'TransAuto'],
            ['codigo' => 'ACEI-75W90', 'nombre' => 'Aceite diferencial 75W-90 1L', 'descripcion' => 'Engranajes / diferencial', 'precio' => 14.00, 'stock' => 16, 'stock_minimo' => 4, 'categoria' => 'Lubricantes', 'proveedor' => 'TransAuto'],

            // Filtros
            ['codigo' => 'FILT-ACE-001', 'nombre' => 'Filtro de aceite estándar', 'descripcion' => 'Filtro motor gasolina', 'precio' => 8.50, 'stock' => 60, 'stock_minimo' => 15, 'categoria' => 'Filtros', 'proveedor' => 'LubriCenter'],
            ['codigo' => 'FILT-ACE-DIE', 'nombre' => 'Filtro de aceite diésel', 'descripcion' => 'Filtro motor diésel', 'precio' => 11.00, 'stock' => 25, 'stock_minimo' => 6, 'categoria' => 'Filtros', 'proveedor' => 'LubriCenter'],
            ['codigo' => 'FILT-AIR-001', 'nombre' => 'Filtro de aire motor', 'descripcion' => 'Filtro de aire panel', 'precio' => 12.00, 'stock' => 35, 'stock_minimo' => 8, 'categoria' => 'Filtros', 'proveedor' => 'FiltrosAndes'],
            ['codigo' => 'FILT-CAB-001', 'nombre' => 'Filtro de cabina / polen', 'descripcion' => 'Filtro habitáculo A/C', 'precio' => 10.00, 'stock' => 28, 'stock_minimo' => 6, 'categoria' => 'Filtros', 'proveedor' => 'FiltrosAndes'],
            ['codigo' => 'FILT-COM-001', 'nombre' => 'Filtro de combustible', 'descripcion' => 'Filtro gasolina/diésel', 'precio' => 14.50, 'stock' => 22, 'stock_minimo' => 5, 'categoria' => 'Filtros', 'proveedor' => 'FiltrosAndes'],

            // Frenos
            ['codigo' => 'PAST-DEL-001', 'nombre' => 'Pastillas de freno delanteras', 'descripcion' => 'Juego cerámico eje delantero', 'precio' => 28.50, 'stock' => 25, 'stock_minimo' => 5, 'categoria' => 'Frenos', 'proveedor' => 'AutoParts SA'],
            ['codigo' => 'PAST-TRA-001', 'nombre' => 'Pastillas de freno traseras', 'descripcion' => 'Juego cerámico eje trasero', 'precio' => 24.00, 'stock' => 20, 'stock_minimo' => 5, 'categoria' => 'Frenos', 'proveedor' => 'AutoParts SA'],
            ['codigo' => 'DISC-DEL-001', 'nombre' => 'Disco de freno delantero', 'descripcion' => 'Disco ventilado unitario', 'precio' => 42.00, 'stock' => 12, 'stock_minimo' => 3, 'categoria' => 'Frenos', 'proveedor' => 'FrenosPro'],
            ['codigo' => 'DISC-TRA-001', 'nombre' => 'Disco de freno trasero', 'descripcion' => 'Disco sólido unitario', 'precio' => 36.00, 'stock' => 10, 'stock_minimo' => 3, 'categoria' => 'Frenos', 'proveedor' => 'FrenosPro'],
            ['codigo' => 'LIQ-FRE-DOT3', 'nombre' => 'Líquido de frenos DOT 3', 'descripcion' => 'Envase 500 ml', 'precio' => 7.50, 'stock' => 30, 'stock_minimo' => 8, 'categoria' => 'Frenos', 'proveedor' => 'AutoParts SA'],
            ['codigo' => 'LIQ-FRE-DOT4', 'nombre' => 'Líquido de frenos DOT 4', 'descripcion' => 'Envase 500 ml', 'precio' => 9.50, 'stock' => 35, 'stock_minimo' => 8, 'categoria' => 'Frenos', 'proveedor' => 'AutoParts SA'],
            ['codigo' => 'BAN-FRE-001', 'nombre' => 'Bandas de freno traseras', 'descripcion' => 'Juego zapatas traseras', 'precio' => 22.00, 'stock' => 14, 'stock_minimo' => 3, 'categoria' => 'Frenos', 'proveedor' => 'FrenosPro'],

            // Suspensión y dirección
            ['codigo' => 'AMOR-DEL-001', 'nombre' => 'Amortiguador delantero', 'descripcion' => 'Amortiguador hidráulico delantero', 'precio' => 65.00, 'stock' => 10, 'stock_minimo' => 2, 'categoria' => 'Suspensión', 'proveedor' => 'SuspensionMax'],
            ['codigo' => 'AMOR-TRA-001', 'nombre' => 'Amortiguador trasero', 'descripcion' => 'Amortiguador hidráulico trasero', 'precio' => 58.00, 'stock' => 10, 'stock_minimo' => 2, 'categoria' => 'Suspensión', 'proveedor' => 'SuspensionMax'],
            ['codigo' => 'ROT-SUP-001', 'nombre' => 'Rótula superior', 'descripcion' => 'Rótula de dirección/suspensión', 'precio' => 18.00, 'stock' => 16, 'stock_minimo' => 4, 'categoria' => 'Suspensión', 'proveedor' => 'SuspensionMax'],
            ['codigo' => 'ROT-INF-001', 'nombre' => 'Rótula inferior', 'descripcion' => 'Rótula inferior suspensión', 'precio' => 20.00, 'stock' => 14, 'stock_minimo' => 4, 'categoria' => 'Suspensión', 'proveedor' => 'SuspensionMax'],
            ['codigo' => 'TERM-DIR-001', 'nombre' => 'Terminal de dirección', 'descripcion' => 'Terminal externo', 'precio' => 15.00, 'stock' => 18, 'stock_minimo' => 4, 'categoria' => 'Suspensión', 'proveedor' => 'SuspensionMax'],
            ['codigo' => 'BUJE-BRA-001', 'nombre' => 'Buje de brazo', 'descripcion' => 'Buje suspensión delantera', 'precio' => 12.00, 'stock' => 20, 'stock_minimo' => 5, 'categoria' => 'Suspensión', 'proveedor' => 'SuspensionMax'],

            // Eléctrico
            ['codigo' => 'BAT-12V-45', 'nombre' => 'Batería 12V 45Ah', 'descripcion' => 'Compacta / ciudad', 'precio' => 75.00, 'stock' => 6, 'stock_minimo' => 2, 'categoria' => 'Eléctrico', 'proveedor' => 'PowerCell'],
            ['codigo' => 'BAT-12V-60', 'nombre' => 'Batería 12V 60Ah', 'descripcion' => 'Uso general', 'precio' => 95.00, 'stock' => 8, 'stock_minimo' => 2, 'categoria' => 'Eléctrico', 'proveedor' => 'PowerCell'],
            ['codigo' => 'BAT-12V-75', 'nombre' => 'Batería 12V 75Ah', 'descripcion' => 'SUV / diésel', 'precio' => 120.00, 'stock' => 5, 'stock_minimo' => 1, 'categoria' => 'Eléctrico', 'proveedor' => 'PowerCell'],
            ['codigo' => 'ALT-12V-001', 'nombre' => 'Alternador 12V remanufacturado', 'descripcion' => 'Alternador de carga', 'precio' => 140.00, 'stock' => 3, 'stock_minimo' => 1, 'categoria' => 'Eléctrico', 'proveedor' => 'PowerCell'],
            ['codigo' => 'ARR-12V-001', 'nombre' => 'Motor de arranque 12V', 'descripcion' => 'Arrancador remanufacturado', 'precio' => 130.00, 'stock' => 3, 'stock_minimo' => 1, 'categoria' => 'Eléctrico', 'proveedor' => 'PowerCell'],
            ['codigo' => 'FUS-KIT-001', 'nombre' => 'Kit de fusibles surtido', 'descripcion' => 'Fusibles blade surtidos', 'precio' => 6.00, 'stock' => 40, 'stock_minimo' => 10, 'categoria' => 'Eléctrico', 'proveedor' => 'PowerCell'],
            ['codigo' => 'REL-ARR-001', 'nombre' => 'Relé de arranque', 'descripcion' => 'Relé 12V arranque', 'precio' => 9.00, 'stock' => 15, 'stock_minimo' => 4, 'categoria' => 'Eléctrico', 'proveedor' => 'PowerCell'],

            // Motor / inyección
            ['codigo' => 'BUJ-IR-001', 'nombre' => 'Bujías iridio (juego x4)', 'descripcion' => 'Juego bujías iridio', 'precio' => 36.00, 'stock' => 18, 'stock_minimo' => 4, 'categoria' => 'Motor', 'proveedor' => 'MotorParts'],
            ['codigo' => 'BUJ-COP-001', 'nombre' => 'Bujías cobre (juego x4)', 'descripcion' => 'Juego bujías convencionales', 'precio' => 14.00, 'stock' => 22, 'stock_minimo' => 5, 'categoria' => 'Motor', 'proveedor' => 'MotorParts'],
            ['codigo' => 'CORR-DIST-001', 'nombre' => 'Correa de distribución', 'descripcion' => 'Kit correa distribución', 'precio' => 78.00, 'stock' => 6, 'stock_minimo' => 2, 'categoria' => 'Motor', 'proveedor' => 'MotorParts'],
            ['codigo' => 'CORR-ACC-001', 'nombre' => 'Correa de accesorios', 'descripcion' => 'Correa serpentina', 'precio' => 22.00, 'stock' => 12, 'stock_minimo' => 3, 'categoria' => 'Motor', 'proveedor' => 'MotorParts'],
            ['codigo' => 'TERM-MOT-001', 'nombre' => 'Termostato de motor', 'descripcion' => 'Termostato refrigerante', 'precio' => 16.00, 'stock' => 14, 'stock_minimo' => 3, 'categoria' => 'Motor', 'proveedor' => 'MotorParts'],
            ['codigo' => 'BOMB-AGU-001', 'nombre' => 'Bomba de agua', 'descripcion' => 'Bomba refrigerante', 'precio' => 48.00, 'stock' => 6, 'stock_minimo' => 2, 'categoria' => 'Motor', 'proveedor' => 'MotorParts'],
            ['codigo' => 'SENS-O2-001', 'nombre' => 'Sensor de oxígeno O2', 'descripcion' => 'Sonda lambda universal', 'precio' => 42.00, 'stock' => 8, 'stock_minimo' => 2, 'categoria' => 'Inyección', 'proveedor' => 'MotorParts'],
            ['codigo' => 'SENS-MAF-001', 'nombre' => 'Sensor MAF', 'descripcion' => 'Medidor flujo de aire', 'precio' => 55.00, 'stock' => 5, 'stock_minimo' => 1, 'categoria' => 'Inyección', 'proveedor' => 'MotorParts'],
            ['codigo' => 'LIM-INY-001', 'nombre' => 'Limpiador de inyectores', 'descripcion' => 'Aditivo limpieza inyección 300 ml', 'precio' => 8.00, 'stock' => 25, 'stock_minimo' => 6, 'categoria' => 'Inyección', 'proveedor' => 'MotorParts'],

            // Transmisión / embrague
            ['codigo' => 'EMB-KIT-001', 'nombre' => 'Kit de embrague', 'descripcion' => 'Disco, plato y collarín', 'precio' => 145.00, 'stock' => 4, 'stock_minimo' => 1, 'categoria' => 'Transmisión', 'proveedor' => 'TransAuto'],
            ['codigo' => 'COLL-EMB-001', 'nombre' => 'Collarín de embrague', 'descripcion' => 'Rodamiento embrague', 'precio' => 28.00, 'stock' => 8, 'stock_minimo' => 2, 'categoria' => 'Transmisión', 'proveedor' => 'TransAuto'],
            ['codigo' => 'ACEI-CAJA-MAN', 'nombre' => 'Aceite caja manual 75W-80 1L', 'descripcion' => 'Lubricante caja mecánica', 'precio' => 13.00, 'stock' => 12, 'stock_minimo' => 3, 'categoria' => 'Transmisión', 'proveedor' => 'TransAuto'],

            // Clima
            ['codigo' => 'GAS-AC-R134', 'nombre' => 'Gas refrigerante A/C R134a', 'descripcion' => 'Carga sistema clima', 'precio' => 28.00, 'stock' => 14, 'stock_minimo' => 3, 'categoria' => 'Clima', 'proveedor' => 'ClimaCar'],
            ['codigo' => 'GAS-AC-1234', 'nombre' => 'Gas refrigerante A/C 1234yf', 'descripcion' => 'Carga clima vehículos recientes', 'precio' => 55.00, 'stock' => 6, 'stock_minimo' => 2, 'categoria' => 'Clima', 'proveedor' => 'ClimaCar'],
            ['codigo' => 'COMP-AC-001', 'nombre' => 'Compresor A/C remanufacturado', 'descripcion' => 'Compresor clima', 'precio' => 220.00, 'stock' => 2, 'stock_minimo' => 1, 'categoria' => 'Clima', 'proveedor' => 'ClimaCar'],

            // Escape / alineación
            ['codigo' => 'CAT-UNI-001', 'nombre' => 'Catalizador universal', 'descripcion' => 'Catalizador escape', 'precio' => 95.00, 'stock' => 3, 'stock_minimo' => 1, 'categoria' => 'Escape', 'proveedor' => 'MotorParts'],
            ['codigo' => 'SIL-ESC-001', 'nombre' => 'Silenciador de escape', 'descripcion' => 'Mofle trasero', 'precio' => 48.00, 'stock' => 4, 'stock_minimo' => 1, 'categoria' => 'Escape', 'proveedor' => 'MotorParts'],
            ['codigo' => 'PES-BAL-001', 'nombre' => 'Pesas de balanceo surtido', 'descripcion' => 'Kit pesas clip llanta', 'precio' => 5.00, 'stock' => 50, 'stock_minimo' => 15, 'categoria' => 'Llantas', 'proveedor' => 'AutoParts SA'],
            ['codigo' => 'VALV-LLA-001', 'nombre' => 'Válvulas de llanta (x4)', 'descripcion' => 'Válvulas metal/goma', 'precio' => 4.00, 'stock' => 40, 'stock_minimo' => 10, 'categoria' => 'Llantas', 'proveedor' => 'AutoParts SA'],

            // Consumibles mantenimiento
            ['codigo' => 'REFRI-VERDE', 'nombre' => 'Refrigerante verde concentrado 1L', 'descripcion' => 'Anticongelante verde', 'precio' => 7.00, 'stock' => 30, 'stock_minimo' => 8, 'categoria' => 'Lubricantes', 'proveedor' => 'LubriCenter'],
            ['codigo' => 'REFRI-ROJO', 'nombre' => 'Refrigerante rojo OAT 1L', 'descripcion' => 'Anticongelante OAT largo plazo', 'precio' => 9.00, 'stock' => 25, 'stock_minimo' => 6, 'categoria' => 'Lubricantes', 'proveedor' => 'LubriCenter'],
            ['codigo' => 'LIQ-DIR-001', 'nombre' => 'Líquido dirección hidráulica 1L', 'descripcion' => 'Fluido power steering', 'precio' => 8.50, 'stock' => 18, 'stock_minimo' => 4, 'categoria' => 'Lubricantes', 'proveedor' => 'LubriCenter'],
            ['codigo' => 'LIM-FRE-001', 'nombre' => 'Limpiador de frenos spray', 'descripcion' => 'Spray desengrasante frenos', 'precio' => 5.50, 'stock' => 30, 'stock_minimo' => 8, 'categoria' => 'Frenos', 'proveedor' => 'AutoParts SA'],
        ];

        foreach ($repuestos as $repuesto) {
            ProductoEloquentModel::updateOrCreate(
                ['codigo' => $repuesto['codigo']],
                array_merge($repuesto, [
                    'activo' => true,
                    'tipo_producto' => 'repuesto',
                ])
            );
        }

        $this->command?->info('Catálogo del taller: '.count($servicios).' servicios y '.count($repuestos).' repuestos.');
    }
}

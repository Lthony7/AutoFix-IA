<?php

namespace App\Services;

use App\Enums\OrdenEstado;
use App\Enums\SugerenciaIaEstado;
use Src\DiagnosticoIA\Infrastructure\Models\DiagnosticoIaEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;
use Src\Producto\Infrastructure\Models\ProductoEloquentModel;
use Src\Servicio\Infrastructure\Models\ServicioEloquentModel;

class GenerarDiagnosticoIaService
{
    public function __construct(
        private readonly GroqDiagnosticService $groqService,
        private readonly AplicarSugerenciaIaAOrdenService $aplicarSugerencia,
        private readonly OrdenRepuestoStockService $stockService,
    ) {
    }

    /**
     * Genera el diagnóstico IA para la OT: consulta a Groq, guarda el
     * diagnóstico, actualiza la OT a EnDiagnostico y aplica servicios,
     * repuestos y mecánico sugeridos (si aún no tiene ítems).
     *
     * @param  array<string, mixed>  $datos  Campos del diagnóstico (tipo_falla, descripcion, urgencia, etc.)
     */
    public function generar(OrdenTrabajoEloquentModel $orden, array $datos = []): DiagnosticoIaEloquentModel
    {
        $orden->loadMissing(['cliente', 'vehiculo']);

        $vehiculo = $orden->vehiculo;
        $kilometraje = $orden->kilometraje_ingreso ?? $vehiculo?->kilometraje;

        $catalogoServicios = ServicioEloquentModel::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->limit(80)
            ->pluck('nombre')
            ->values()
            ->all();

        $catalogoRepuestos = ProductoEloquentModel::query()
            ->where('activo', true)
            ->where(function ($q) {
                $q->where('tipo_producto', 'repuesto')
                    ->orWhereNull('tipo_producto');
            })
            ->orderBy('nombre')
            ->limit(120)
            ->pluck('nombre')
            ->values()
            ->all();

        $descripcion = $datos['descripcion'] ?? $orden->falla_reportada ?? '';

        $inputData = [
            'orden_trabajo_id' => $orden->id,
            'orden_numero' => $orden->numero,
            'cliente' => $orden->cliente?->razon_social,
            'vehiculo_placa' => $vehiculo?->placa,
            'vehiculo_marca' => $vehiculo?->marca,
            'vehiculo_modelo' => $vehiculo?->modelo,
            'vehiculo_anio' => $vehiculo?->anio,
            'vehiculo_combustible' => $vehiculo?->tipo_combustible,
            'kilometraje' => $kilometraje,
            'tipo_falla' => $datos['tipo_falla'] ?? $orden->tipo_falla,
            'descripcion' => $descripcion,
            'momento' => $datos['momento'] ?? 'No especificado',
            'luces_tablero' => $datos['luces_tablero'] ?? null,
            'ruidos' => $datos['ruidos'] ?? null,
            'puede_circular' => $datos['puede_circular'] ?? true,
            'urgencia' => $datos['urgencia'] ?? $orden->prioridad ?? 'media',
            'observaciones' => $datos['observaciones'] ?? $orden->observaciones,
            'falla_reportada' => $descripcion,
            'catalogo_servicios' => $catalogoServicios,
            'catalogo_repuestos' => $catalogoRepuestos,
        ];

        $resultado = $this->groqService->analyze($inputData);

        $diagnostico = DiagnosticoIaEloquentModel::create([
            'orden_trabajo_id' => $orden->id,
            'input_data' => array_merge($inputData, [
                'servicios_sugeridos' => $resultado['servicios_sugeridos'] ?? [],
                'repuestos_sugeridos' => $resultado['repuestos_sugeridos'] ?? [],
            ]),
            'respuesta_completa' => $resultado['respuesta_completa'],
            'diagnostico_detalle' => $resultado['diagnostico_detalle'] ?? null,
            'posibles_causas' => $resultado['posibles_causas'],
            'servicio_recomendado' => $resultado['servicio_recomendado'],
            'especialidad_recomendada' => $resultado['especialidad_recomendada'] ?? null,
            'acciones_recomendadas' => $resultado['acciones_recomendadas'] ?? [],
            'mecanicos_sugeridos' => $resultado['mecanicos_sugeridos'] ?? [],
            'prioridad' => $resultado['prioridad'],
            'observacion_mecanico' => $resultado['observacion_mecanico'],
            'advertencia' => $resultado['advertencia'],
            'estado' => SugerenciaIaEstado::Generada,
            'es_simulado' => $resultado['es_simulado'],
        ]);

        $orden->update([
            'estado' => OrdenEstado::EnDiagnostico,
            'tipo_falla' => $datos['tipo_falla'] ?? $orden->tipo_falla,
            'falla_reportada' => $descripcion,
            'prioridad' => $resultado['prioridad'] ?? ($datos['urgencia'] ?? $orden->prioridad),
            'observaciones' => $datos['observaciones'] ?? $orden->observaciones,
        ]);

        $this->aplicarSugerencia->aplicar($orden->fresh(), $resultado, $this->stockService);

        return $diagnostico;
    }
}

<?php

namespace Src\DiagnosticoIA\Infrastructure\Models;

use App\Enums\SugerenciaIaEstado;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;

class DiagnosticoIaEloquentModel extends Model
{
    use HasUuid;

    protected $table = 'diagnosticos_ia';

    protected $fillable = [
        'id',
        'orden_trabajo_id',
        'input_data',
        'respuesta_completa',
        'diagnostico_detalle',
        'posibles_causas',
        'servicio_recomendado',
        'especialidad_recomendada',
        'acciones_recomendadas',
        'mecanicos_sugeridos',
        'prioridad',
        'observacion_mecanico',
        'advertencia',
        'estado',
        'es_simulado',
        'observaciones_revision',
        'coincide_analisis',
    ];

    protected $casts = [
        'input_data' => 'array',
        'posibles_causas' => 'array',
        'acciones_recomendadas' => 'array',
        'mecanicos_sugeridos' => 'array',
        'estado' => SugerenciaIaEstado::class,
        'es_simulado' => 'boolean',
        'coincide_analisis' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajoEloquentModel::class, 'orden_trabajo_id');
    }
}

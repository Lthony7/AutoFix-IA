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
        'posibles_causas',
        'servicio_recomendado',
        'prioridad',
        'observacion_mecanico',
        'advertencia',
        'estado',
        'es_simulado',
        'observaciones_revision',
    ];

    protected $casts = [
        'input_data' => 'array',
        'posibles_causas' => 'array',
        'estado' => SugerenciaIaEstado::class,
        'es_simulado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajoEloquentModel::class, 'orden_trabajo_id');
    }
}

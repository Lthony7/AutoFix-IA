<?php

namespace Src\Cita\Infrastructure\Models;

use App\Enums\CitaEstado;
use App\Enums\CitaTipo;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Cliente\Infrastructure\Models\ClienteEloquentModel;
use Src\Mecanico\Infrastructure\Models\MecanicoEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;
use Src\Presupuesto\Infrastructure\Models\PresupuestoEloquentModel;
use Src\Vehiculo\Infrastructure\Models\VehiculoEloquentModel;

class CitaEloquentModel extends Model
{
    use HasUuid;

    protected $table = 'citas_taller';

    protected $fillable = [
        'id',
        'cliente_id',
        'vehiculo_id',
        'mecanico_id',
        'orden_trabajo_id',
        'presupuesto_id',
        'fecha_hora',
        'duracion_minutos',
        'tipo',
        'estado',
        'notas',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'duracion_minutos' => 'integer',
        'tipo' => CitaTipo::class,
        'estado' => CitaEstado::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(ClienteEloquentModel::class, 'cliente_id');
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(VehiculoEloquentModel::class, 'vehiculo_id');
    }

    public function mecanico(): BelongsTo
    {
        return $this->belongsTo(MecanicoEloquentModel::class, 'mecanico_id');
    }

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajoEloquentModel::class, 'orden_trabajo_id');
    }

    public function presupuesto(): BelongsTo
    {
        return $this->belongsTo(PresupuestoEloquentModel::class, 'presupuesto_id');
    }
}

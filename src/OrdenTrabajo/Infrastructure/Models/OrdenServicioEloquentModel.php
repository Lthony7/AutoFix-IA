<?php

namespace Src\OrdenTrabajo\Infrastructure\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Servicio\Infrastructure\Models\ServicioEloquentModel;

class OrdenServicioEloquentModel extends Model
{
    use HasUuid;

    protected $table = 'orden_servicio';

    protected $fillable = [
        'id',
        'orden_trabajo_id',
        'servicio_id',
        'precio',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajoEloquentModel::class, 'orden_trabajo_id');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(ServicioEloquentModel::class, 'servicio_id');
    }
}

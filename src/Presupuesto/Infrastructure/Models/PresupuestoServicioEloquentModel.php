<?php

namespace Src\Presupuesto\Infrastructure\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Servicio\Infrastructure\Models\ServicioEloquentModel;

class PresupuestoServicioEloquentModel extends Model
{
    use HasUuid;

    protected $table = 'presupuesto_servicios';

    protected $fillable = [
        'id',
        'presupuesto_id',
        'servicio_id',
        'nombre',
        'precio',
        'cantidad',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'cantidad' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function presupuesto(): BelongsTo
    {
        return $this->belongsTo(PresupuestoEloquentModel::class, 'presupuesto_id');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(ServicioEloquentModel::class, 'servicio_id');
    }
}

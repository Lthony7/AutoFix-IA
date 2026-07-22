<?php

namespace Src\OrdenTrabajo\Infrastructure\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Producto\Infrastructure\Models\ProductoEloquentModel;

class OrdenRepuestoEloquentModel extends Model
{
    use HasUuid;

    protected $table = 'orden_repuesto';

    protected $fillable = [
        'id',
        'orden_trabajo_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajoEloquentModel::class, 'orden_trabajo_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(ProductoEloquentModel::class, 'producto_id');
    }
}

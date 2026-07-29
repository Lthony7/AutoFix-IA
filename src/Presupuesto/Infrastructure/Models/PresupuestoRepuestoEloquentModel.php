<?php

namespace Src\Presupuesto\Infrastructure\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Producto\Infrastructure\Models\ProductoEloquentModel;

class PresupuestoRepuestoEloquentModel extends Model
{
    use HasUuid;

    protected $table = 'presupuesto_repuestos';

    protected $fillable = [
        'id',
        'presupuesto_id',
        'producto_id',
        'codigo',
        'nombre',
        'precio_unitario',
        'cantidad',
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
        'cantidad' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function presupuesto(): BelongsTo
    {
        return $this->belongsTo(PresupuestoEloquentModel::class, 'presupuesto_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(ProductoEloquentModel::class, 'producto_id');
    }
}

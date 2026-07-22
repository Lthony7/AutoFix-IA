<?php

namespace Src\Pago\Infrastructure\Models;

use App\Enums\PagoEstado;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Auth\Infrastructure\Models\UserEloquentModel;
use Src\Factura\Infrastructure\Models\FacturaEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;

class PagoEloquentModel extends Model
{
    use HasUuid;

    protected $table = 'pagos';

    protected $fillable = [
        'id',
        'orden_trabajo_id',
        'factura_id',
        'valor_servicios',
        'valor_repuestos',
        'descuento',
        'total',
        'estado',
        'metodo_pago',
        'registrado_por',
    ];

    protected $casts = [
        'valor_servicios' => 'decimal:2',
        'valor_repuestos' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
        'estado' => PagoEstado::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajoEloquentModel::class, 'orden_trabajo_id');
    }

    public function factura(): BelongsTo
    {
        return $this->belongsTo(FacturaEloquentModel::class, 'factura_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(UserEloquentModel::class, 'registrado_por');
    }

    public static function calcularDesdeOrden(OrdenTrabajoEloquentModel $orden): array
    {
        $orden->loadMissing(['ordenServicios', 'ordenRepuestos']);

        $valorServicios = $orden->ordenServicios->sum('precio');
        $valorRepuestos = $orden->ordenRepuestos->sum(fn ($r) => $r->cantidad * $r->precio_unitario);

        return [
            'valor_servicios' => round((float) $valorServicios, 2),
            'valor_repuestos' => round((float) $valorRepuestos, 2),
        ];
    }
}

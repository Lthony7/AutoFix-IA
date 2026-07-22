<?php

namespace Src\Factura\Infrastructure\Models;

use App\Enums\FacturaEstado;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Src\Auth\Infrastructure\Models\UserEloquentModel;
use Src\Cliente\Infrastructure\Models\ClienteEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;
use Src\Pago\Infrastructure\Models\PagoEloquentModel;

class FacturaEloquentModel extends Model
{
    use HasUuid;

    protected $table = 'facturas';

    protected $fillable = [
        'id',
        'numero',
        'serie',
        'orden_trabajo_id',
        'cliente_id',
        'usuario_id',
        'fecha_emision',
        'subtotal',
        'iva',
        'descuento',
        'total',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'subtotal' => 'decimal:2',
        'iva' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
        'estado' => FacturaEstado::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajoEloquentModel::class, 'orden_trabajo_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(ClienteEloquentModel::class, 'cliente_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(UserEloquentModel::class, 'usuario_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleFacturaEloquentModel::class, 'factura_id');
    }

    public function pago(): HasOne
    {
        return $this->hasOne(PagoEloquentModel::class, 'factura_id');
    }

    public static function generarNumero(): string
    {
        $fecha = now()->format('Ymd');
        $prefijo = "F-{$fecha}-";

        $ultimo = static::query()
            ->where('numero', 'like', $prefijo . '%')
            ->orderByDesc('numero')
            ->value('numero');

        $secuencia = 1;
        if ($ultimo) {
            $partes = explode('-', $ultimo);
            $secuencia = ((int) end($partes)) + 1;
        }

        return $prefijo . str_pad((string) $secuencia, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return array{detalles: list<array<string, mixed>>, subtotal: float, iva: float, total: float}
     */
    public static function calcularDesdeOrden(OrdenTrabajoEloquentModel $orden, float $descuento = 0): array
    {
        $orden->loadMissing(['ordenServicios.servicio', 'ordenRepuestos.producto']);

        $detalles = [];
        $subtotal = 0.0;

        foreach ($orden->ordenServicios as $os) {
            $precio = (float) $os->precio;
            $lineSubtotal = $precio;
            $subtotal += $lineSubtotal;
            $detalles[] = [
                'descripcion' => $os->servicio?->nombre ?? 'Servicio',
                'tipo' => 'servicio',
                'referencia_id' => $os->servicio_id,
                'cantidad' => 1,
                'precio_unitario' => $precio,
                'subtotal' => round($lineSubtotal, 2),
            ];
        }

        foreach ($orden->ordenRepuestos as $or) {
            $precio = (float) $or->precio_unitario;
            $cantidad = (int) $or->cantidad;
            $lineSubtotal = $precio * $cantidad;
            $subtotal += $lineSubtotal;
            $detalles[] = [
                'descripcion' => $or->producto?->nombre ?? 'Repuesto',
                'tipo' => 'repuesto',
                'referencia_id' => $or->producto_id,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'subtotal' => round($lineSubtotal, 2),
            ];
        }

        $descuento = max(0, min($descuento, $subtotal));
        $base = max(0, $subtotal - $descuento);
        $ivaRate = (float) config('autofix.iva_rate', 0.15);
        $iva = round($base * $ivaRate, 2);
        $total = round($base + $iva, 2);

        return [
            'detalles' => $detalles,
            'subtotal' => round($subtotal, 2),
            'iva' => $iva,
            'descuento' => round($descuento, 2),
            'total' => $total,
            'iva_rate' => $ivaRate,
        ];
    }
}

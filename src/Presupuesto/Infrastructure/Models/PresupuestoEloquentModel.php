<?php

namespace Src\Presupuesto\Infrastructure\Models;

use App\Enums\PresupuestoEstado;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Src\Cliente\Infrastructure\Models\ClienteEloquentModel;
use Src\Vehiculo\Infrastructure\Models\VehiculoEloquentModel;

class PresupuestoEloquentModel extends Model
{
    use HasUuid;

    protected $table = 'presupuestos';

    protected $fillable = [
        'id',
        'numero',
        'cliente_id',
        'vehiculo_id',
        'estado',
        'subtotal_servicios',
        'subtotal_repuestos',
        'total',
        'notas',
        'valido_hasta',
    ];

    protected $casts = [
        'estado' => PresupuestoEstado::class,
        'subtotal_servicios' => 'decimal:2',
        'subtotal_repuestos' => 'decimal:2',
        'total' => 'decimal:2',
        'valido_hasta' => 'date',
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

    public function servicios(): HasMany
    {
        return $this->hasMany(PresupuestoServicioEloquentModel::class, 'presupuesto_id');
    }

    public function repuestos(): HasMany
    {
        return $this->hasMany(PresupuestoRepuestoEloquentModel::class, 'presupuesto_id');
    }

    public static function generarNumero(): string
    {
        $fecha = now()->format('Ymd');
        $prefijo = "PRE-{$fecha}-";

        $ultimo = static::query()
            ->where('numero', 'like', $prefijo . '%')
            ->orderByDesc('numero')
            ->value('numero');

        $secuencia = 1;
        if ($ultimo) {
            $secuencia = ((int) substr($ultimo, -3)) + 1;
        }

        return $prefijo . str_pad((string) $secuencia, 3, '0', STR_PAD_LEFT);
    }

    public function estaVencido(): bool
    {
        if ($this->estado === PresupuestoEstado::Vencido) {
            return true;
        }

        return $this->valido_hasta !== null && $this->valido_hasta->lt(now()->startOfDay());
    }

    public function esEditable(): bool
    {
        if ($this->estaVencido()) {
            return false;
        }

        return in_array($this->estado, [
            PresupuestoEstado::Borrador,
            PresupuestoEstado::Guardado,
        ], true);
    }

    public function esUsableEnCita(): bool
    {
        if ($this->estaVencido() || $this->estado === PresupuestoEstado::Cancelado) {
            return false;
        }

        return in_array($this->estado, [
            PresupuestoEstado::Guardado,
            PresupuestoEstado::Borrador,
            PresupuestoEstado::VinculadoCita,
        ], true);
    }

    public function recalcularTotales(): void
    {
        $subServicios = (float) $this->servicios()->get()->sum(
            fn (PresupuestoServicioEloquentModel $l) => (float) $l->precio * (int) $l->cantidad
        );
        $subRepuestos = (float) $this->repuestos()->get()->sum(
            fn (PresupuestoRepuestoEloquentModel $l) => (float) $l->precio_unitario * (int) $l->cantidad
        );

        $this->update([
            'subtotal_servicios' => round($subServicios, 2),
            'subtotal_repuestos' => round($subRepuestos, 2),
            'total' => round($subServicios + $subRepuestos, 2),
        ]);
    }
}

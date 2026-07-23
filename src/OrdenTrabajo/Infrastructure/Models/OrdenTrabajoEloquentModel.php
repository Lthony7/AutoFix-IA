<?php

namespace Src\OrdenTrabajo\Infrastructure\Models;

use App\Enums\OrdenEstado;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Src\Auth\Infrastructure\Models\UserEloquentModel;
use Src\Cliente\Infrastructure\Models\ClienteEloquentModel;
use Src\DiagnosticoIA\Infrastructure\Models\DiagnosticoIaEloquentModel;
use Src\Factura\Infrastructure\Models\FacturaEloquentModel;
use Src\Mecanico\Infrastructure\Models\MecanicoEloquentModel;
use Src\Pago\Infrastructure\Models\PagoEloquentModel;
use Src\Vehiculo\Infrastructure\Models\VehiculoEloquentModel;

class OrdenTrabajoEloquentModel extends Model
{
    use HasUuid;

    protected $table = 'ordenes_trabajo';

    protected $fillable = [
        'id',
        'numero',
        'cliente_id',
        'vehiculo_id',
        'mecanico_id',
        'created_by',
        'updated_by',
        'estado',
        'tipo_falla',
        'falla_reportada',
        'kilometraje_ingreso',
        'observaciones',
        'diagnostico_tecnico',
        'prioridad',
    ];

    protected $casts = [
        'estado' => OrdenEstado::class,
        'kilometraje_ingreso' => 'integer',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(UserEloquentModel::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(UserEloquentModel::class, 'updated_by');
    }

    public function ordenServicios(): HasMany
    {
        return $this->hasMany(OrdenServicioEloquentModel::class, 'orden_trabajo_id');
    }

    public function ordenRepuestos(): HasMany
    {
        return $this->hasMany(OrdenRepuestoEloquentModel::class, 'orden_trabajo_id');
    }

    public function sugerenciaIa(): HasOne
    {
        return $this->hasOne(DiagnosticoIaEloquentModel::class, 'orden_trabajo_id');
    }

    public function pago(): HasOne
    {
        return $this->hasOne(PagoEloquentModel::class, 'orden_trabajo_id');
    }

    public function factura(): HasOne
    {
        return $this->hasOne(FacturaEloquentModel::class, 'orden_trabajo_id');
    }

    public static function generarNumero(): string
    {
        $fecha = now()->format('Ymd');
        $prefijo = "OT-{$fecha}-";

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
}

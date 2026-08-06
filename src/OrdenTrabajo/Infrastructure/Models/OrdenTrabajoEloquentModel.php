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
use App\Enums\FacturaEstado;

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

    public function facturas(): HasMany
    {
        return $this->hasMany(FacturaEloquentModel::class, 'orden_trabajo_id');
    }

    /**
     * Factura vigente de la OT: la más reciente que NO esté anulada.
     * Si la única factura fue anulada, devuelve null para permitir re-emitir.
     *
     * No se usa ofMany/latestOfMany porque Laravel agrega MAX(id) como
     * tie-breaker y el id es uuid (Postgres no soporta max(uuid)).
     * En su lugar se usa DISTINCT ON (sintaxis Postgres): el id de la
     * factura no anulada más reciente, ordenada por created_at desc.
     */
    public function factura(): HasOne
    {
        $ultima = FacturaEloquentModel::query()
            ->selectRaw('DISTINCT ON (orden_trabajo_id) id')
            ->where('estado', '!=', FacturaEstado::Anulada->value)
            ->orderBy('orden_trabajo_id')
            ->orderByDesc('created_at');

        return $this->hasOne(FacturaEloquentModel::class, 'orden_trabajo_id')
            ->whereIn('id', $ultima);
    }

    public function avances(): HasMany
    {
        return $this->hasMany(OrdenAvanceEloquentModel::class, 'orden_trabajo_id')->orderByDesc('created_at');
    }

    public function cita(): HasOne
    {
        return $this->hasOne(\Src\Cita\Infrastructure\Models\CitaEloquentModel::class, 'orden_trabajo_id');
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

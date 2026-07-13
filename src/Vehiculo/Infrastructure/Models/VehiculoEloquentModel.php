<?php

namespace Src\Vehiculo\Infrastructure\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Cliente\Infrastructure\Models\ClienteEloquentModel;

class VehiculoEloquentModel extends Model
{
    use HasUuid;

    protected $table = 'vehiculos';

    protected $fillable = [
        'id',
        'cliente_id',
        'placa',
        'marca',
        'modelo',
        'anio',
        'color',
        'kilometraje',
        'tipo_combustible',
        'observaciones',
        'activo',
    ];

    protected $casts = [
        'anio' => 'integer',
        'kilometraje' => 'integer',
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(ClienteEloquentModel::class, 'cliente_id');
    }
}

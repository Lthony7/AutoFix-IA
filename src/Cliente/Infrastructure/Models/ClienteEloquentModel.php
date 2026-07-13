<?php

namespace Src\Cliente\Infrastructure\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Src\Auth\Infrastructure\Models\UserEloquentModel;
use Src\Vehiculo\Infrastructure\Models\VehiculoEloquentModel;

class ClienteEloquentModel extends Model
{
    use HasUuid;

    protected $table = 'clientes';

    protected $fillable = [
        'id',
        'tipo_documento',
        'numero_documento',
        'razon_social',
        'nombres',
        'apellidos',
        'direccion',
        'telefono',
        'email',
        'estado',
        'user_id',
    ];

    protected $casts = [
        'estado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserEloquentModel::class, 'user_id');
    }

    public function vehiculos(): HasMany
    {
        return $this->hasMany(VehiculoEloquentModel::class, 'cliente_id', 'id');
    }
}

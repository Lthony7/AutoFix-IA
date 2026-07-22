<?php

namespace Src\Servicio\Infrastructure\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class ServicioEloquentModel extends Model
{
    use HasUuid;

    protected $table = 'servicios';

    protected $fillable = [
        'id',
        'nombre',
        'descripcion',
        'precio_base',
        'activo',
    ];

    protected $casts = [
        'precio_base' => 'decimal:2',
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

<?php

namespace Src\Producto\Infrastructure\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class ProductoEloquentModel extends Model
{
    use HasUuid;

    protected $table = 'productos';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'precio',
        'stock',
        'activo',
        'tipo_producto'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

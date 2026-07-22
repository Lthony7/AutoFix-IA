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
        'stock_minimo',
        'activo',
        'tipo_producto',
        'categoria',
        'proveedor',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'stock' => 'integer',
        'stock_minimo' => 'integer',
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

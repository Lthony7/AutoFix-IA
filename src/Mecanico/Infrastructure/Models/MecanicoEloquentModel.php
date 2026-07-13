<?php

namespace Src\Mecanico\Infrastructure\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Auth\Infrastructure\Models\UserEloquentModel;

class MecanicoEloquentModel extends Model
{
    use HasUuid;

    protected $table = 'mecanicos';

    protected $fillable = [
        'id',
        'user_id',
        'nombres',
        'apellidos',
        'documento',
        'telefono',
        'email',
        'especialidad',
        'horario_disponible',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserEloquentModel::class, 'user_id');
    }
}

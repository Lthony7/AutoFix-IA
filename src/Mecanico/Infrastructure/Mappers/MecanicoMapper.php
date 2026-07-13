<?php

namespace Src\Mecanico\Infrastructure\Mappers;

use Src\Mecanico\Domain\Entities\Mecanico;
use Src\Mecanico\Infrastructure\Models\MecanicoEloquentModel;

class MecanicoMapper
{
    public static function toDomain(MecanicoEloquentModel $model): Mecanico
    {
        return new Mecanico(
            id: $model->id,
            userId: $model->user_id,
            nombres: $model->nombres,
            apellidos: $model->apellidos,
            documento: $model->documento,
            telefono: $model->telefono,
            email: $model->email,
            especialidad: $model->especialidad,
            horarioDisponible: $model->horario_disponible,
            activo: (bool) $model->activo,
            createdAt: new \DateTimeImmutable($model->created_at->toDateTimeString()),
            updatedAt: new \DateTimeImmutable($model->updated_at->toDateTimeString())
        );
    }
}

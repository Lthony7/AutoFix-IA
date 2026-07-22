<?php

namespace Src\Servicio\Infrastructure\Mappers;

use Src\Servicio\Domain\Entities\Servicio;
use Src\Servicio\Infrastructure\Models\ServicioEloquentModel;

class ServicioMapper
{
    public static function toDomain(ServicioEloquentModel $model): Servicio
    {
        return new Servicio(
            id: $model->id,
            nombre: $model->nombre,
            descripcion: $model->descripcion,
            precioBase: (float) $model->precio_base,
            activo: (bool) $model->activo,
            createdAt: new \DateTimeImmutable($model->created_at->toDateTimeString()),
            updatedAt: new \DateTimeImmutable($model->updated_at->toDateTimeString())
        );
    }
}

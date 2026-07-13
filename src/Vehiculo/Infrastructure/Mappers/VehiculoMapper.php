<?php

namespace Src\Vehiculo\Infrastructure\Mappers;

use Src\Vehiculo\Domain\Entities\Vehiculo;
use Src\Vehiculo\Infrastructure\Models\VehiculoEloquentModel;

class VehiculoMapper
{
    public static function toDomain(VehiculoEloquentModel $model): Vehiculo
    {
        $cliente = $model->relationLoaded('cliente') ? $model->cliente : null;
        $clienteNombre = null;

        if ($cliente) {
            $completo = trim(($cliente->nombres ?? '') . ' ' . ($cliente->apellidos ?? ''));
            $clienteNombre = $completo !== '' ? $completo : $cliente->razon_social;
        }

        return new Vehiculo(
            id: $model->id,
            clienteId: $model->cliente_id,
            placa: $model->placa,
            marca: $model->marca,
            modelo: $model->modelo,
            anio: (int) $model->anio,
            color: $model->color,
            kilometraje: (int) $model->kilometraje,
            tipoCombustible: $model->tipo_combustible,
            observaciones: $model->observaciones,
            activo: (bool) $model->activo,
            createdAt: new \DateTimeImmutable($model->created_at->toDateTimeString()),
            updatedAt: new \DateTimeImmutable($model->updated_at->toDateTimeString()),
            clienteNombre: $clienteNombre
        );
    }
}

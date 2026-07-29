<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Notifications\CitaActualizada;
use Src\Auth\Infrastructure\Models\UserEloquentModel;
use Src\Cita\Infrastructure\Models\CitaEloquentModel;

class CitaNotifier
{
    public function notifyTaller(CitaEloquentModel $cita, string $accion): void
    {
        $cita->loadMissing(['cliente', 'vehiculo']);

        $users = UserEloquentModel::query()
            ->where('activo', true)
            ->whereIn('role', [UserRole::Administrador->value, UserRole::Recepcionista->value])
            ->get();

        foreach ($users as $user) {
            $user->notify(new CitaActualizada($cita, $accion));
        }
    }
}

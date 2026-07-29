<?php

namespace App\Services;

use Src\Auth\Infrastructure\Models\UserEloquentModel;
use Src\Cliente\Infrastructure\Models\ClienteEloquentModel;

class ClienteCuentaService
{
    /**
     * Asegura que el usuario cliente tenga ficha en `clientes` (p. ej. tras registro público).
     */
    public function ensureForUser(UserEloquentModel $user): ClienteEloquentModel
    {
        $existing = ClienteEloquentModel::where('user_id', $user->id)->first();
        if ($existing) {
            return $existing;
        }

        $byEmail = ClienteEloquentModel::where('email', strtolower($user->email))->whereNull('user_id')->first();
        if ($byEmail) {
            $byEmail->update(['user_id' => $user->id]);

            return $byEmail->fresh();
        }

        $nombre = trim($user->name ?: 'Cliente');
        $partes = preg_split('/\s+/', $nombre, 2) ?: [$nombre];
        $nombres = $partes[0] ?? $nombre;
        $apellidos = $partes[1] ?? '';

        $docBase = 'TMP' . strtoupper(substr(str_replace('-', '', (string) $user->id), 0, 10));
        $numeroDocumento = $docBase;
        $i = 0;
        while (ClienteEloquentModel::where('numero_documento', $numeroDocumento)->exists()) {
            $i++;
            $numeroDocumento = $docBase . $i;
        }

        return ClienteEloquentModel::create([
            'tipo_documento' => 'CEDULA',
            'numero_documento' => $numeroDocumento,
            'razon_social' => $nombre,
            'nombres' => $nombres,
            'apellidos' => $apellidos !== '' ? $apellidos : $nombres,
            'direccion' => 'Por completar',
            'telefono' => '0000000000',
            'email' => strtolower($user->email),
            'estado' => true,
            'user_id' => $user->id,
        ]);
    }
}

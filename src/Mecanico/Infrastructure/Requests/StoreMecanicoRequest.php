<?php

namespace Src\Mecanico\Infrastructure\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMecanicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $userId = $this->userId ?? $this->user_id;

        $this->merge([
            'user_id' => $userId === '' ? null : $userId,
            'horario_disponible' => $this->horarioDisponible ?? $this->horario_disponible,
            'activo' => $this->has('activo')
                ? filter_var($this->activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true
                : true,
        ]);
    }

    public function rules(): array
    {
        return [
            'user_id' => 'nullable|uuid|exists:users,id|unique:mecanicos,user_id',
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'documento' => 'required|string|max:50|unique:mecanicos,documento',
            'telefono' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'especialidad' => 'required|string|max:255',
            'horario_disponible' => 'nullable|string|max:255',
            'activo' => 'sometimes|boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'usuario',
            'nombres' => 'nombres',
            'apellidos' => 'apellidos',
            'documento' => 'documento',
            'telefono' => 'teléfono',
            'email' => 'correo',
            'especialidad' => 'especialidad',
            'horario_disponible' => 'horario disponible',
            'activo' => 'estado',
        ];
    }
}

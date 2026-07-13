<?php

namespace Src\Mecanico\Infrastructure\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMecanicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('userId') || $this->has('user_id')) {
            $userId = $this->userId ?? $this->user_id;
            $data['user_id'] = $userId === '' ? null : $userId;
        }

        if ($this->has('horarioDisponible')) {
            $data['horario_disponible'] = $this->horarioDisponible;
        }

        if ($this->has('activo')) {
            $data['activo'] = filter_var($this->activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        $mecanicoId = $this->route('id') ?? $this->route('mecanico');

        return [
            'user_id' => [
                'nullable',
                'uuid',
                'exists:users,id',
                Rule::unique('mecanicos', 'user_id')->ignore($mecanicoId),
            ],
            'nombres' => 'sometimes|string|max:255',
            'apellidos' => 'sometimes|string|max:255',
            'documento' => 'sometimes|string|max:50|unique:mecanicos,documento,' . $mecanicoId . ',id',
            'telefono' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'especialidad' => 'sometimes|string|max:255',
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
            'especialidad' => 'especialidad',
            'horario_disponible' => 'horario disponible',
        ];
    }
}

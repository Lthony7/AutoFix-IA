<?php

namespace Src\Mecanico\Infrastructure\Requests;

use App\Support\FieldValidation;
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

        if ($this->has('nombres')) {
            $data['nombres'] = trim((string) $this->nombres);
        }

        if ($this->has('apellidos')) {
            $data['apellidos'] = trim((string) $this->apellidos);
        }

        if ($this->has('documento')) {
            $data['documento'] = FieldValidation::soloDigitos((string) $this->documento);
        }

        if ($this->has('telefono')) {
            $data['telefono'] = $this->telefono !== null && $this->telefono !== ''
                ? FieldValidation::soloDigitos((string) $this->telefono)
                : null;
        }

        if ($this->has('email')) {
            $data['email'] = $this->email ? strtolower(trim((string) $this->email)) : null;
        }

        if ($this->has('especialidad')) {
            $data['especialidad'] = trim((string) $this->especialidad);
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
            'nombres' => ['sometimes', ...array_slice(FieldValidation::nombre(true), 1)],
            'apellidos' => ['sometimes', ...array_slice(FieldValidation::nombre(true), 1)],
            'documento' => FieldValidation::documentoIdentidad(false, 'mecanicos', $mecanicoId),
            'telefono' => FieldValidation::telefono(false),
            'email' => FieldValidation::email(false),
            'especialidad' => 'sometimes|string|min:2|max:255',
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
        ];
    }

    public function messages(): array
    {
        return array_merge(FieldValidation::messages(), [
            'documento.unique' => 'Este documento ya está registrado',
        ]);
    }
}

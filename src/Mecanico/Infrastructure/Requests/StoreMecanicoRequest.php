<?php

namespace Src\Mecanico\Infrastructure\Requests;

use App\Support\FieldValidation;
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
            'nombres' => trim((string) $this->nombres),
            'apellidos' => trim((string) $this->apellidos),
            'documento' => FieldValidation::soloDigitos((string) $this->documento),
            'telefono' => $this->telefono !== null && $this->telefono !== ''
                ? FieldValidation::soloDigitos((string) $this->telefono)
                : null,
            'email' => $this->email ? strtolower(trim((string) $this->email)) : null,
            'especialidad' => trim((string) $this->especialidad),
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
            'nombres' => FieldValidation::nombre(true),
            'apellidos' => FieldValidation::nombre(true),
            'documento' => FieldValidation::documentoIdentidad(true, 'mecanicos'),
            'telefono' => FieldValidation::telefono(false),
            'email' => FieldValidation::email(false),
            'especialidad' => 'required|string|min:2|max:255',
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

    public function messages(): array
    {
        return array_merge(FieldValidation::messages(), [
            'documento.unique' => 'Este documento ya está registrado',
            'especialidad.required' => 'La especialidad es obligatoria',
        ]);
    }
}

<?php

namespace Src\Cliente\Infrastructure\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $nombres = $this->nombres;
        $apellidos = $this->apellidos;
        $razonSocial = $this->razonSocial;

        if (!$razonSocial && ($nombres || $apellidos)) {
            $razonSocial = trim(($nombres ?? '') . ' ' . ($apellidos ?? ''));
        }

        $this->merge([
            'tipo_documento' => $this->tipoDocumento ?? $this->tipo_documento,
            'numero_documento' => $this->numeroDocumento ?? $this->numero_documento,
            'razon_social' => $razonSocial ?? $this->razon_social,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'estado' => $this->has('estado') ? filter_var($this->estado, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true : true,
            'user_id' => $this->userId ?? $this->user_id,
        ]);
    }

    public function rules(): array
    {
        return [
            'tipo_documento' => 'required|string|in:DNI,RUC,CE,PASAPORTE,CEDULA',
            'numero_documento' => 'required|string|unique:clientes,numero_documento',
            'razon_social' => 'required|string|max:255',
            'nombres' => 'nullable|string|max:255',
            'apellidos' => 'nullable|string|max:255',
            'direccion' => 'required|string|max:255',
            'telefono' => 'required|string|max:255',
            'email' => 'required|email|unique:clientes,email',
            'estado' => 'sometimes|boolean',
            'user_id' => 'nullable|uuid|exists:users,id',
        ];
    }

    public function attributes(): array
    {
        return [
            'tipo_documento' => 'tipo de documento',
            'numero_documento' => 'número de documento',
            'razon_social' => 'razón social / nombre',
            'nombres' => 'nombres',
            'apellidos' => 'apellidos',
            'direccion' => 'dirección',
            'telefono' => 'teléfono',
            'email' => 'email',
            'estado' => 'estado',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_documento.required' => 'El tipo de documento es obligatorio',
            'tipo_documento.in' => 'El tipo de documento no es válido',
            'numero_documento.required' => 'El número de documento es obligatorio',
            'numero_documento.unique' => 'Este número de documento ya está registrado',
            'razon_social.required' => 'El nombre o razón social es obligatorio',
            'direccion.required' => 'La dirección es obligatoria',
            'telefono.required' => 'El teléfono es obligatorio',
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe ser válido',
            'email.unique' => 'Este email ya está registrado',
        ];
    }
}

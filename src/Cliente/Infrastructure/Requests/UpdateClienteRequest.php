<?php

namespace Src\Cliente\Infrastructure\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('tipoDocumento')) {
            $data['tipo_documento'] = $this->tipoDocumento;
        }

        if ($this->has('numeroDocumento')) {
            $data['numero_documento'] = $this->numeroDocumento;
        }

        if ($this->has('razonSocial')) {
            $data['razon_social'] = $this->razonSocial;
        }

        if ($this->has('nombres')) {
            $data['nombres'] = $this->nombres;
        }

        if ($this->has('apellidos')) {
            $data['apellidos'] = $this->apellidos;
        }

        if ($this->has('userId')) {
            $data['user_id'] = $this->userId;
        }

        if ($this->has('estado')) {
            $data['estado'] = filter_var($this->estado, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        $clienteId = $this->route('id') ?? $this->route('cliente');

        return [
            'tipo_documento' => 'sometimes|string|in:DNI,RUC,CE,PASAPORTE,CEDULA',
            'numero_documento' => 'sometimes|string|unique:clientes,numero_documento,' . $clienteId . ',id',
            'razon_social' => 'sometimes|string|max:255',
            'nombres' => 'nullable|string|max:255',
            'apellidos' => 'nullable|string|max:255',
            'direccion' => 'sometimes|string|max:255',
            'telefono' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:clientes,email,' . $clienteId . ',id',
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
            'tipo_documento.in' => 'El tipo de documento no es válido',
            'numero_documento.unique' => 'Este número de documento ya está registrado',
            'email.email' => 'El email debe ser válido',
            'email.unique' => 'Este email ya está registrado',
        ];
    }
}

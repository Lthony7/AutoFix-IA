<?php

namespace Src\Cliente\Infrastructure\Requests;

use App\Support\FieldValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('tipoDocumento') || $this->has('tipo_documento')) {
            $data['tipo_documento'] = strtoupper((string) ($this->tipoDocumento ?? $this->tipo_documento));
        }

        $tipo = $data['tipo_documento'] ?? strtoupper((string) ($this->tipo_documento ?? 'CEDULA'));

        if ($this->has('numeroDocumento') || $this->has('numero_documento')) {
            $numero = (string) ($this->numeroDocumento ?? $this->numero_documento);
            $data['numero_documento'] = in_array($tipo, ['CEDULA', 'DNI', 'RUC'], true)
                ? FieldValidation::soloDigitos($numero)
                : preg_replace('/\s+/', '', $numero);
        }

        if ($this->has('razonSocial') || $this->has('razon_social')) {
            $data['razon_social'] = trim((string) ($this->razonSocial ?? $this->razon_social));
        }

        if ($this->has('nombres')) {
            $data['nombres'] = trim((string) $this->nombres) ?: null;
        }

        if ($this->has('apellidos')) {
            $data['apellidos'] = trim((string) $this->apellidos) ?: null;
        }

        if ($this->has('direccion')) {
            $data['direccion'] = trim((string) $this->direccion);
        }

        if ($this->has('telefono')) {
            $data['telefono'] = FieldValidation::soloDigitos((string) $this->telefono);
        }

        if ($this->has('email')) {
            $data['email'] = strtolower(trim((string) $this->email));
        }

        if ($this->has('userId') || $this->has('user_id')) {
            $data['user_id'] = $this->userId ?? $this->user_id;
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
            'numero_documento' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('clientes', 'numero_documento')->ignore($clienteId),
                FieldValidation::documentoPorTipo('tipo_documento'),
            ],
            'razon_social' => 'sometimes|string|min:2|max:255',
            'nombres' => FieldValidation::nombre(false),
            'apellidos' => FieldValidation::nombre(false),
            'direccion' => 'sometimes|string|min:5|max:255',
            'telefono' => ['sometimes', 'string', 'regex:' . FieldValidation::TELEFONO_REGEX],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('clientes', 'email')->ignore($clienteId),
            ],
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
        return array_merge(FieldValidation::messages(), [
            'tipo_documento.in' => 'El tipo de documento no es válido',
            'numero_documento.unique' => 'Este número de documento ya está registrado',
            'email.unique' => 'Este email ya está registrado',
        ]);
    }
}

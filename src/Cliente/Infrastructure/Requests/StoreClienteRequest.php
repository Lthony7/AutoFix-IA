<?php

namespace Src\Cliente\Infrastructure\Requests;

use App\Support\FieldValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $nombres = $this->nombres ? trim((string) $this->nombres) : null;
        $apellidos = $this->apellidos ? trim((string) $this->apellidos) : null;
        $razonSocial = $this->razonSocial ?? $this->razon_social;

        if (!$razonSocial && ($nombres || $apellidos)) {
            $razonSocial = trim(($nombres ?? '') . ' ' . ($apellidos ?? ''));
        }

        $telefono = FieldValidation::soloDigitos($this->telefono);
        $numeroDocumento = $this->numeroDocumento ?? $this->numero_documento;
        $tipo = strtoupper((string) ($this->tipoDocumento ?? $this->tipo_documento ?? 'CEDULA'));

        if (in_array($tipo, ['CEDULA', 'DNI', 'RUC'], true)) {
            $numeroDocumento = FieldValidation::soloDigitos((string) $numeroDocumento);
        } else {
            $numeroDocumento = preg_replace('/\s+/', '', (string) $numeroDocumento);
        }

        $this->merge([
            'tipo_documento' => $tipo,
            'numero_documento' => $numeroDocumento,
            'razon_social' => $razonSocial ? trim((string) $razonSocial) : null,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'direccion' => $this->direccion ? trim((string) $this->direccion) : null,
            'telefono' => $telefono,
            'email' => $this->email ? strtolower(trim((string) $this->email)) : null,
            'estado' => $this->has('estado') ? filter_var($this->estado, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true : true,
            'user_id' => $this->userId ?? $this->user_id,
        ]);
    }

    public function rules(): array
    {
        return [
            'tipo_documento' => 'required|string|in:DNI,RUC,CE,PASAPORTE,CEDULA',
            'numero_documento' => [
                'required',
                'string',
                'max:20',
                Rule::unique('clientes', 'numero_documento'),
                FieldValidation::documentoPorTipo('tipo_documento'),
            ],
            'razon_social' => 'required|string|min:2|max:255',
            'nombres' => FieldValidation::nombre(false),
            'apellidos' => FieldValidation::nombre(false),
            'direccion' => 'required|string|min:5|max:255',
            'telefono' => FieldValidation::telefono(true),
            'email' => FieldValidation::email(true, 'clientes'),
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
            'tipo_documento.required' => 'El tipo de documento es obligatorio',
            'tipo_documento.in' => 'El tipo de documento no es válido',
            'numero_documento.required' => 'El número de documento es obligatorio',
            'numero_documento.unique' => 'Este número de documento ya está registrado',
            'razon_social.required' => 'El nombre o razón social es obligatorio',
            'direccion.required' => 'La dirección es obligatoria',
            'direccion.min' => 'La dirección debe tener al menos 5 caracteres',
            'telefono.required' => 'El teléfono es obligatorio',
            'email.required' => 'El email es obligatorio',
            'email.unique' => 'Este email ya está registrado',
        ]);
    }
}

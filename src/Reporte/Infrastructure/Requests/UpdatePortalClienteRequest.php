<?php

namespace Src\Reporte\Infrastructure\Requests;

use App\Support\FieldValidation;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Src\Cliente\Infrastructure\Models\ClienteEloquentModel;

class UpdatePortalClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $tipo = strtoupper(trim((string) ($this->tipoDocumento ?? $this->tipo_documento ?? 'CEDULA')));
        $numero = trim((string) ($this->numeroDocumento ?? $this->numero_documento ?? ''));

        if (in_array($tipo, ['CEDULA', 'DNI', 'RUC'], true)) {
            $numero = FieldValidation::soloDigitos($numero) ?? '';
        }

        $this->merge([
            'tipo_documento' => $tipo,
            'numero_documento' => $numero,
            'telefono' => $this->telefono !== null && $this->telefono !== ''
                ? FieldValidation::soloDigitos((string) $this->telefono)
                : null,
            'email' => $this->email ? strtolower(trim((string) $this->email)) : null,
            'direccion' => $this->direccion !== null ? trim((string) $this->direccion) : null,
            'nombres' => $this->nombres !== null ? trim((string) $this->nombres) : null,
            'apellidos' => $this->apellidos !== null ? trim((string) $this->apellidos) : null,
        ]);
    }

    public function rules(): array
    {
        $cliente = ClienteEloquentModel::where('user_id', $this->user()?->id)->first();
        $userId = $this->user()?->id;

        return [
            'tipo_documento' => 'required|string|in:CEDULA,DNI,RUC,CE,PASAPORTE',
            'numero_documento' => [
                'required',
                'string',
                'max:20',
                Rule::unique('clientes', 'numero_documento')->ignore($cliente?->id),
                FieldValidation::documentoPorTipo('tipo_documento'),
            ],
            'nombres' => array_merge(FieldValidation::nombre(true), [
                function (string $attribute, mixed $value, Closure $fail): void {
                    $parts = preg_split('/\s+/', trim((string) $value)) ?: [];
                    if (count(array_filter($parts)) < 1) {
                        $fail('Ingresa al menos un nombre.');
                    }
                },
            ]),
            'apellidos' => array_merge(FieldValidation::nombre(true), [
                function (string $attribute, mixed $value, Closure $fail): void {
                    $parts = preg_split('/\s+/', trim((string) $value)) ?: [];
                    if (count(array_filter($parts)) < 2) {
                        $fail('Ingresa tus dos apellidos (paterno y materno).');
                    }
                },
            ]),
            'telefono' => FieldValidation::telefono(true),
            'email' => array_merge(
                FieldValidation::email(true, 'clientes', $cliente?->id),
                [Rule::unique('users', 'email')->ignore($userId)]
            ),
            'direccion' => 'required|string|min:5|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'tipo_documento' => 'tipo de documento',
            'numero_documento' => 'número de documento',
            'nombres' => 'nombres',
            'apellidos' => 'apellidos',
            'telefono' => 'teléfono',
            'email' => 'correo',
            'direccion' => 'dirección',
        ];
    }

    public function messages(): array
    {
        return array_merge(FieldValidation::messages(), [
            'tipo_documento.required' => 'Selecciona el tipo de documento.',
            'numero_documento.required' => 'El número de documento es obligatorio.',
            'numero_documento.unique' => 'Ese documento ya está registrado en otra cuenta.',
            'direccion.required' => 'La dirección es obligatoria.',
        ]);
    }
}

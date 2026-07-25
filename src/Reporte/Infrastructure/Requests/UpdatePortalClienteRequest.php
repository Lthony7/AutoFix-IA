<?php

namespace Src\Reporte\Infrastructure\Requests;

use App\Support\FieldValidation;
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
        $this->merge([
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
            'nombres' => FieldValidation::nombre(true),
            'apellidos' => FieldValidation::nombre(true),
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
            'nombres' => 'nombres',
            'apellidos' => 'apellidos',
            'telefono' => 'teléfono',
            'email' => 'correo',
            'direccion' => 'dirección',
        ];
    }

    public function messages(): array
    {
        return FieldValidation::messages();
    }
}

<?php

namespace Src\Auth\Infrastructure\Requests;

use App\Support\FieldValidation;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->name),
            'email' => strtolower(trim((string) $this->email)),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => FieldValidation::nombre(true),
            'email' => FieldValidation::email(true, 'users'),
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo',
            'password' => 'contraseña',
        ];
    }

    public function messages(): array
    {
        return array_merge(FieldValidation::messages(), [
            'password.required' => 'La contraseña es obligatoria',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
        ]);
    }
}

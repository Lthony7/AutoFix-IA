<?php

namespace Src\Auth\Infrastructure\Requests;

use App\Enums\UserRole;
use App\Support\FieldValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::Administrador) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->name),
            'email' => strtolower(trim((string) $this->email)),
            'activo' => $this->has('activo')
                ? filter_var($this->activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true
                : true,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => FieldValidation::nombre(true),
            'email' => FieldValidation::email(true, 'users'),
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(UserRole::values())],
            'activo' => 'sometimes|boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo',
            'password' => 'contraseña',
            'role' => 'rol',
            'activo' => 'estado',
        ];
    }

    public function messages(): array
    {
        return array_merge(FieldValidation::messages(), [
            'password.required' => 'La contraseña es obligatoria',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'role.required' => 'El rol es obligatorio',
        ]);
    }
}

<?php

namespace Src\Auth\Infrastructure\Requests;

use App\Enums\UserRole;
use App\Support\FieldValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::Administrador) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('name')) {
            $data['name'] = trim((string) $this->name);
        }

        if ($this->has('email')) {
            $data['email'] = strtolower(trim((string) $this->email));
        }

        if ($this->has('activo')) {
            $data['activo'] = filter_var($this->activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        $userId = $this->route('id') ?? $this->route('usuario');

        return [
            'name' => ['sometimes', ...array_slice(FieldValidation::nombre(true), 1)],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['sometimes', Rule::in(UserRole::values())],
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
            'password.confirmed' => 'Las contraseñas no coinciden',
        ]);
    }
}

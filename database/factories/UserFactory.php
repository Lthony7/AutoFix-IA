<?php

namespace Database\Factories;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Src\Auth\Infrastructure\Models\UserEloquentModel;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Src\Auth\Infrastructure\Models\UserEloquentModel>
 */
class UserFactory extends Factory
{
    protected $model = UserEloquentModel::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::Cliente,
            'activo' => true,
        ];
    }

    public function administrador(): static
    {
        return $this->state(fn () => ['role' => UserRole::Administrador]);
    }

    public function recepcionista(): static
    {
        return $this->state(fn () => ['role' => UserRole::Recepcionista]);
    }

    public function mecanico(): static
    {
        return $this->state(fn () => ['role' => UserRole::Mecanico]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}

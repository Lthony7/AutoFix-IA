<?php

namespace Src\Auth\Infrastructure\Models;

use App\Enums\UserRole;
use App\Traits\HasUuid;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Src\Cliente\Infrastructure\Models\ClienteEloquentModel;
use Src\Mecanico\Infrastructure\Models\MecanicoEloquentModel;

class UserEloquentModel extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasUuid;

    protected $table = 'users';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'activo',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'activo' => 'boolean',
        ];
    }

    public function hasRole(UserRole|string ...$roles): bool
    {
        $values = array_map(
            fn (UserRole|string $role) => $role instanceof UserRole ? $role->value : $role,
            $roles
        );

        return in_array($this->role?->value ?? (string) $this->role, $values, true);
    }

    public function isActivo(): bool
    {
        return (bool) $this->activo;
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(ClienteEloquentModel::class, 'user_id');
    }

    public function mecanico(): HasOne
    {
        return $this->hasOne(MecanicoEloquentModel::class, 'user_id');
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}

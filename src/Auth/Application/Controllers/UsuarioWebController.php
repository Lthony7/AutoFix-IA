<?php

namespace Src\Auth\Application\Controllers;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Support\InertiaTablePaginator;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Auth\Infrastructure\Models\UserEloquentModel;
use Src\Auth\Infrastructure\Requests\StoreUserRequest;
use Src\Auth\Infrastructure\Requests\UpdateUserRequest;

class UsuarioWebController extends Controller
{
    public function index(Request $request): Response
    {
        $roleFilter = $request->query('role');
        $search = trim((string) $request->query('q', ''));

        $query = UserEloquentModel::query()->orderBy('name');

        if (is_string($roleFilter) && in_array($roleFilter, UserRole::values(), true)) {
            $query->where('role', $roleFilter);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        $paginator = $query
            ->paginate(50)
            ->withQueryString()
            ->through(fn (UserEloquentModel $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->value,
                'roleLabel' => $user->role?->label(),
                'activo' => (bool) $user->activo,
                'createdAt' => $user->created_at?->format('Y-m-d H:i:s'),
            ]);

        $counts = UserEloquentModel::query()
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        return Inertia::render('Usuario/index', [
            'users' => InertiaTablePaginator::make($paginator),
            'filters' => [
                'role' => $roleFilter,
                'q' => $search,
            ],
            'stats' => [
                'total' => (int) UserEloquentModel::count(),
                'administrador' => (int) ($counts[UserRole::Administrador->value] ?? 0),
                'recepcionista' => (int) ($counts[UserRole::Recepcionista->value] ?? 0),
                'mecanico' => (int) ($counts[UserRole::Mecanico->value] ?? 0),
                'cliente' => (int) ($counts[UserRole::Cliente->value] ?? 0),
            ],
            'roles' => collect(UserRole::cases())->map(fn (UserRole $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ])->values()->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Usuario/create', [
            'roles' => $this->rolesPayload(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();

            UserEloquentModel::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $data['role'],
                'activo' => $data['activo'] ?? true,
            ]);

            return redirect()
                ->route('usuarios.index')
                ->with('success', 'Usuario creado exitosamente');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear el usuario: ' . $e->getMessage());
        }
    }

    public function edit(string $id): Response
    {
        $user = UserEloquentModel::findOrFail($id);

        return Inertia::render('Usuario/edit', [
            'usuario' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->value,
                'activo' => (bool) $user->activo,
            ],
            'roles' => $this->rolesPayload(),
        ]);
    }

    public function update(UpdateUserRequest $request, string $id): RedirectResponse
    {
        try {
            $user = UserEloquentModel::findOrFail($id);
            $data = $request->validated();

            if (empty($data['password'])) {
                unset($data['password']);
            }

            $user->update($data);

            return redirect()
                ->route('usuarios.index')
                ->with('success', 'Usuario actualizado exitosamente');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar el usuario: ' . $e->getMessage());
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        $user = UserEloquentModel::find($id);

        if (!$user) {
            return redirect()->back()->with('error', 'Usuario no encontrado');
        }

        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'No puedes eliminar tu propio usuario');
        }

        $user->delete();

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario eliminado exitosamente');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function rolesPayload(): array
    {
        return collect(UserRole::cases())->map(fn (UserRole $role) => [
            'value' => $role->value,
            'label' => $role->label(),
        ])->values()->all();
    }
}

<?php

namespace Src\Auth\Application\Controllers;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Support\InertiaTablePaginator;
use Exception;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Src\Auth\Infrastructure\Models\UserEloquentModel;
use Src\Auth\Infrastructure\Requests\StoreUserRequest;
use Src\Auth\Infrastructure\Requests\UpdateUserRequest;

class UsuarioWebController extends Controller
{
    public function index(): Response
    {
        $paginator = UserEloquentModel::query()
            ->orderBy('name')
            ->paginate(InertiaTablePaginator::PER_PAGE)
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

        return Inertia::render('Usuario/index', [
            'users' => InertiaTablePaginator::make($paginator),
            'roles' => collect(UserRole::cases())->map(fn (UserRole $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Usuario/create', [
            'roles' => collect(UserRole::cases())->map(fn (UserRole $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ]),
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
            'roles' => collect(UserRole::cases())->map(fn (UserRole $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ]),
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
}

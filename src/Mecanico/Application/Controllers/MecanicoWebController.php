<?php

namespace Src\Mecanico\Application\Controllers;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Src\Auth\Infrastructure\Models\UserEloquentModel;
use Src\Mecanico\Infrastructure\Mappers\MecanicoMapper;
use Src\Mecanico\Infrastructure\Models\MecanicoEloquentModel;
use Src\Mecanico\Infrastructure\Requests\StoreMecanicoRequest;
use Src\Mecanico\Infrastructure\Requests\UpdateMecanicoRequest;

class MecanicoWebController extends Controller
{
    public function index(): Response
    {
        $mecanicos = MecanicoEloquentModel::orderBy('nombres')->get();

        $data = $mecanicos->map(
            fn ($model) => MecanicoMapper::toDomain($model)->toArray()
        )->toArray();

        return Inertia::render('Mecanico/index', [
            'mecanicos' => [
                'data' => $data,
                'meta' => ['total' => count($data)],
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Mecanico/create', [
            'usuarios' => $this->usuariosMecanicoOptions(),
        ]);
    }

    public function store(StoreMecanicoRequest $request): RedirectResponse
    {
        try {
            MecanicoEloquentModel::create($request->validated());

            return redirect()
                ->route('mecanicos.index')
                ->with('success', 'Mecánico registrado exitosamente');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al registrar el mecánico: ' . $e->getMessage());
        }
    }

    public function edit(string $id): Response
    {
        $mecanico = MecanicoEloquentModel::findOrFail($id);

        return Inertia::render('Mecanico/edit', [
            'mecanico' => MecanicoMapper::toDomain($mecanico)->toArray(),
            'usuarios' => $this->usuariosMecanicoOptions($mecanico->user_id),
        ]);
    }

    public function update(UpdateMecanicoRequest $request, string $id): RedirectResponse
    {
        try {
            $mecanico = MecanicoEloquentModel::findOrFail($id);
            $mecanico->update($request->validated());

            return redirect()
                ->route('mecanicos.index')
                ->with('success', 'Mecánico actualizado exitosamente');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar el mecánico: ' . $e->getMessage());
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        $mecanico = MecanicoEloquentModel::find($id);

        if (!$mecanico) {
            return redirect()->back()->with('error', 'Mecánico no encontrado');
        }

        $mecanico->delete();

        return redirect()
            ->route('mecanicos.index')
            ->with('success', 'Mecánico eliminado exitosamente');
    }

    private function usuariosMecanicoOptions(?string $currentUserId = null): array
    {
        $asignados = MecanicoEloquentModel::query()
            ->whereNotNull('user_id')
            ->when($currentUserId, fn ($q) => $q->where('user_id', '!=', $currentUserId))
            ->pluck('user_id');

        return UserEloquentModel::query()
            ->where('role', UserRole::Mecanico)
            ->where('activo', true)
            ->whereNotIn('id', $asignados)
            ->orderBy('name')
            ->get()
            ->map(fn (UserEloquentModel $user) => [
                'id' => $user->id,
                'label' => $user->name . ' — ' . $user->email,
            ])
            ->values()
            ->toArray();
    }
}

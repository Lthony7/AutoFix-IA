<?php

namespace Src\Presupuesto\Application\Controllers;

use App\Http\Controllers\Controller;
use App\Support\InertiaTablePaginator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Presupuesto\Application\Services\PresupuestoLineasService;
use Src\Presupuesto\Infrastructure\Models\PresupuestoEloquentModel;

class PresupuestoWebController extends Controller
{
    public function __construct(
        private readonly PresupuestoLineasService $lineas,
    ) {
    }

    public function index(Request $request): Response
    {
        $query = PresupuestoEloquentModel::with(['cliente', 'vehiculo'])
            ->orderByDesc('created_at');

        if ($estado = $request->query('estado')) {
            $query->where('estado', $estado);
        }

        if ($buscar = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($buscar) {
                $q->where('numero', 'ilike', "%{$buscar}%")
                    ->orWhereHas('cliente', fn ($c) => $c->where('razon_social', 'ilike', "%{$buscar}%"))
                    ->orWhereHas('vehiculo', fn ($v) => $v->where('placa', 'ilike', "%{$buscar}%"));
            });
        }

        $paginator = $query
            ->paginate(InertiaTablePaginator::PER_PAGE)
            ->withQueryString()
            ->through(fn (PresupuestoEloquentModel $p) => $this->lineas->mapPresupuesto($p, false));

        return Inertia::render('Presupuesto/index', [
            'presupuestos' => InertiaTablePaginator::make($paginator),
            'filters' => [
                'q' => $request->query('q', ''),
                'estado' => $request->query('estado', ''),
            ],
        ]);
    }

    public function show(string $id): Response
    {
        $presupuesto = PresupuestoEloquentModel::with(['cliente', 'vehiculo', 'servicios', 'repuestos'])
            ->findOrFail($id);

        return Inertia::render('Presupuesto/show', [
            'presupuesto' => $this->lineas->mapPresupuesto($presupuesto),
        ]);
    }
}

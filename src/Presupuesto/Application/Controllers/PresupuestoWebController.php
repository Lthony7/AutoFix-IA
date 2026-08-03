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

        $counts = PresupuestoEloquentModel::query()
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return Inertia::render('Presupuesto/index', [
            'presupuestos' => InertiaTablePaginator::make($paginator),
            'filters' => [
                'q' => $request->query('q', ''),
                'estado' => $request->query('estado', ''),
            ],
            'stats' => [
                'total' => (int) PresupuestoEloquentModel::count(),
                'guardado' => (int) ($counts['guardado'] ?? 0) + (int) ($counts['borrador'] ?? 0),
                'vinculado' => (int) ($counts['vinculado_cita'] ?? 0),
                'vencido' => (int) ($counts['vencido'] ?? 0),
                'cancelado' => (int) ($counts['cancelado'] ?? 0),
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

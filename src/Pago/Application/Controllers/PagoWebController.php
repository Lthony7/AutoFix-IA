<?php

namespace Src\Pago\Application\Controllers;

use App\Enums\OrdenEstado;
use App\Enums\PagoEstado;
use App\Enums\FacturaEstado;
use App\Http\Controllers\Controller;
use App\Services\OrdenEstadoNotifier;
use App\Services\SincronizarPagoFacturaService;
use App\Support\InertiaTablePaginator;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Factura\Infrastructure\Models\FacturaEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;
use Src\Pago\Infrastructure\Models\PagoEloquentModel;
use Src\Pago\Infrastructure\Requests\StorePagoRequest;
use Src\Pago\Infrastructure\Requests\UpdatePagoRequest;

class PagoWebController extends Controller
{
    public function __construct(
        private readonly SincronizarPagoFacturaService $syncFactura,
        private readonly OrdenEstadoNotifier $estadoNotifier,
    ) {
    }

    public function index(Request $request): Response
    {
        $estado = trim((string) $request->query('estado', ''));
        $facturaEstados = FacturaEstado::values();

        $query = FacturaEloquentModel::with(['ordenTrabajo.cliente', 'ordenTrabajo.vehiculo', 'pago'])
            ->orderByDesc('created_at');

        if ($estado !== '' && in_array($estado, $facturaEstados, true)) {
            $query->where('estado', $estado);
        }

        $paginator = $query
            ->paginate(InertiaTablePaginator::PER_PAGE)
            ->withQueryString()
            ->through(fn (FacturaEloquentModel $f) => $this->mapFactura($f));

        $counts = FacturaEloquentModel::query()
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $ingresosMes = (float) PagoEloquentModel::query()
            ->where('estado', PagoEstado::Pagado->value)
            ->where('created_at', '>=', now()->copy()->startOfMonth())
            ->sum('total');

        return Inertia::render('Pago/index', [
            'facturas' => InertiaTablePaginator::make($paginator),
            'filters' => ['estado' => $estado],
            'stats' => [
                'emitida' => (int) ($counts[FacturaEstado::Emitida->value] ?? 0),
                'pagada' => (int) ($counts[FacturaEstado::Pagada->value] ?? 0),
                'anulada' => (int) ($counts[FacturaEstado::Anulada->value] ?? 0),
                'ingresosMes' => $ingresosMes,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $ordenTrabajoId = $request->query('ordenTrabajoId');
        $aviso = null;

        if ($ordenTrabajoId) {
            $orden = OrdenTrabajoEloquentModel::find($ordenTrabajoId);
            if ($orden && !$this->esEstadoCobrable($orden)) {
                $label = $orden->estado instanceof OrdenEstado
                    ? $orden->estado->label()
                    : (string) $orden->estado;
                $aviso = "La orden {$orden->numero} aún no está lista para cobrar (estado: {$label}). El pago se habilita cuando la orden esté Finalizada o Entregada.";
            }
        }

        return Inertia::render('Pago/create', [
            'ordenes' => $this->ordenesPorCobrarOptions(),
            'ivaRate' => (float) config('autofix.iva_rate', 0.15),
            'ordenTrabajoId' => $ordenTrabajoId,
            'aviso' => $aviso,
        ]);
    }

    public function store(StorePagoRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            $orden = OrdenTrabajoEloquentModel::with(['ordenServicios', 'ordenRepuestos', 'factura', 'pago'])
                ->findOrFail($data['orden_trabajo_id']);

            if (!$this->esEstadoCobrable($orden)) {
                $label = $orden->estado instanceof OrdenEstado
                    ? $orden->estado->label()
                    : (string) $orden->estado;

                return redirect()->back()->withInput()
                    ->with('error', 'Solo se puede registrar el pago cuando la orden está Finalizada o Entregada. Estado actual: ' . $label . '.');
            }

            // Si ya hay pago pendiente/anulado, completar ese cobro en lugar de crear otro
            $pagoExistente = $orden->pago;
            if ($pagoExistente && $pagoExistente->estado !== PagoEstado::Pagado) {
                return redirect()
                    ->route('pagos.edit', $pagoExistente->id)
                    ->with('info', 'Esta orden ya tiene un pago por completar. Continúa el cobro desde aquí.');
            }

            if ($pagoExistente && $pagoExistente->estado === PagoEstado::Pagado) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Esta orden ya tiene un pago registrado como pagado.');
            }

            $montos = $this->resolverMontosPago(
                $orden,
                isset($data['descuento']) ? (float) $data['descuento'] : null
            );

            $estado = $data['estado'] ?? PagoEstado::Pendiente->value;

            $pago = PagoEloquentModel::create([
                'orden_trabajo_id' => $orden->id,
                'factura_id' => $orden->factura?->id,
                'valor_servicios' => $montos['valor_servicios'],
                'valor_repuestos' => $montos['valor_repuestos'],
                'descuento' => $montos['descuento'],
                'total' => $montos['total'],
                'estado' => $estado,
                'metodo_pago' => $data['metodo_pago'] ?? null,
                'registrado_por' => $data['registrado_por'] ?? $request->user()?->id,
            ]);

            $this->syncFactura->sincronizarMontos($pago, $montos);
            $this->syncFactura->sincronizarEstado($pago);

            if ($pago->estado === PagoEstado::Pagado) {
                $this->marcarEntregadaAlCobrar($orden);
            }

            return redirect()->route('pagos.index')->with('success', 'Pago registrado exitosamente');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Error al registrar pago: ' . $e->getMessage());
        }
    }

    public function edit(string $id): Response
    {
        $pago = PagoEloquentModel::with(['ordenTrabajo.cliente', 'ordenTrabajo.vehiculo', 'factura'])->findOrFail($id);

        return Inertia::render('Pago/edit', [
            'pago' => $this->mapPago($pago),
            'ivaRate' => (float) config('autofix.iva_rate', 0.15),
        ]);
    }

    public function update(UpdatePagoRequest $request, string $id): RedirectResponse
    {
        try {
            $pago = PagoEloquentModel::with([
                'ordenTrabajo.ordenServicios',
                'ordenTrabajo.ordenRepuestos',
                'ordenTrabajo.factura',
                'factura',
            ])->findOrFail($id);

            $data = $request->validated();

            if (
                ($data['estado'] ?? $pago->estado->value) === PagoEstado::Pagado->value
                && $pago->ordenTrabajo?->estado === OrdenEstado::Cancelada
            ) {
                return redirect()->back()->withInput()
                    ->with('error', 'No se puede cobrar una orden cancelada.');
            }

            $montos = $this->resolverMontosPago(
                $pago->ordenTrabajo,
                array_key_exists('descuento', $data) ? (float) $data['descuento'] : (float) $pago->descuento
            );

            if (!$pago->factura_id && $pago->ordenTrabajo?->factura) {
                $data['factura_id'] = $pago->ordenTrabajo->factura->id;
            }

            $pago->update(array_merge($data, [
                'valor_servicios' => $montos['valor_servicios'],
                'valor_repuestos' => $montos['valor_repuestos'],
                'descuento' => $montos['descuento'],
                'total' => $montos['total'],
            ]));

            $pago = $pago->fresh(['factura']);
            $this->syncFactura->sincronizarMontos($pago, $montos);
            $this->syncFactura->sincronizarEstado($pago);

            if ($pago->estado === PagoEstado::Pagado && $pago->ordenTrabajo) {
                $this->marcarEntregadaAlCobrar($pago->ordenTrabajo);
            }

            return redirect()->route('pagos.index')->with('success', 'Pago actualizado exitosamente');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Error al actualizar pago: ' . $e->getMessage());
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        $pago = PagoEloquentModel::with('factura')->find($id);

        if (!$pago) {
            return redirect()->back()->with('error', 'Pago no encontrado');
        }

        $factura = $pago->factura
            ?? ($pago->factura_id ? FacturaEloquentModel::find($pago->factura_id) : null);

        $pago->delete();
        $this->syncFactura->alEliminarPago($factura);

        return redirect()->route('pagos.index')->with('success', 'Pago eliminado exitosamente');
    }

    /**
     * @return array{valor_servicios: float, valor_repuestos: float, descuento: float, total: float, iva: float, subtotal: float}
     */
    private function resolverMontosPago(OrdenTrabajoEloquentModel $orden, ?float $descuentoSolicitado): array
    {
        $calculado = PagoEloquentModel::calcularDesdeOrden($orden);
        $valorServicios = $calculado['valor_servicios'];
        $valorRepuestos = $calculado['valor_repuestos'];
        $subtotal = $valorServicios + $valorRepuestos;

        $descuento = $descuentoSolicitado ?? (float) ($orden->factura?->descuento ?? 0);
        $descuento = max(0, min($descuento, $subtotal));

        if ($orden->factura) {
            $facturaCalc = FacturaEloquentModel::calcularDesdeOrden($orden, $descuento);

            return [
                'valor_servicios' => $valorServicios,
                'valor_repuestos' => $valorRepuestos,
                'descuento' => (float) $facturaCalc['descuento'],
                'total' => (float) $facturaCalc['total'],
                'iva' => (float) $facturaCalc['iva'],
                'subtotal' => (float) $facturaCalc['subtotal'],
            ];
        }

        $total = max(0, $subtotal - $descuento);

        return [
            'valor_servicios' => $valorServicios,
            'valor_repuestos' => $valorRepuestos,
            'descuento' => round($descuento, 2),
            'total' => round($total, 2),
            'iva' => 0.0,
            'subtotal' => round($subtotal, 2),
        ];
    }

    private function mapPago(PagoEloquentModel $pago): array
    {
        return [
            'id' => $pago->id,
            'ordenTrabajoId' => $pago->orden_trabajo_id,
            'facturaId' => $pago->factura_id,
            'tieneFactura' => (bool) $pago->factura_id,
            'ordenNumero' => $pago->ordenTrabajo?->numero,
            'clienteNombre' => $pago->ordenTrabajo?->cliente?->razon_social,
            'vehiculoPlaca' => $pago->ordenTrabajo?->vehiculo?->placa,
            'valorServicios' => (float) $pago->valor_servicios,
            'valorRepuestos' => (float) $pago->valor_repuestos,
            'descuento' => (float) $pago->descuento,
            'total' => (float) $pago->total,
            'estado' => $pago->estado->value,
            'estadoLabel' => $pago->estado->label(),
            'metodoPago' => $pago->metodo_pago,
            'createdAt' => $pago->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function mapFactura(FacturaEloquentModel $factura): array
    {
        $pago = $factura->pago;

        return [
            'id' => $factura->id,
            'numero' => $factura->numero,
            'ordenTrabajoId' => $factura->orden_trabajo_id,
            'ordenNumero' => $factura->ordenTrabajo?->numero,
            'clienteNombre' => $factura->cliente_razon_social
                ?? trim(($factura->cliente_nombres ?? '') . ' ' . ($factura->cliente_apellidos ?? '')),
            'vehiculoPlaca' => $factura->ordenTrabajo?->vehiculo?->placa,
            'subtotal' => (float) $factura->subtotal,
            'iva' => (float) $factura->iva,
            'descuento' => (float) $factura->descuento,
            'total' => (float) $factura->total,
            'estado' => $factura->estado instanceof \BackedEnum ? $factura->estado->value : (string) $factura->estado,
            'estadoLabel' => $factura->estado instanceof \BackedEnum ? $factura->estado->label() : (string) $factura->estado,
            'pagoId' => $pago?->id,
            'pagoEstado' => $pago
                ? ($pago->estado instanceof \BackedEnum ? $pago->estado->value : (string) $pago->estado)
                : null,
            'pagoEstadoLabel' => $pago
                ? ($pago->estado instanceof \BackedEnum ? $pago->estado->label() : (string) $pago->estado)
                : null,
            'createdAt' => $factura->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * OTs sin pago, o con pago pendiente/anulado (para completar cobro).
     * Solo se cobra cuando el trabajo terminó (finalizada) o el vehículo ya se entregó (entregada).
     * Excluye pagos ya pagados.
     *
     * @return list<array<string, mixed>>
     */
    private function ordenesPorCobrarOptions(): array
    {
        return OrdenTrabajoEloquentModel::with([
            'cliente',
            'vehiculo',
            'ordenServicios',
            'ordenRepuestos',
            'factura',
            'pago',
        ])
            ->whereIn('estado', [
                OrdenEstado::Finalizada->value,
                OrdenEstado::Entregada->value,
            ])
            ->where(function ($q) {
                $q->whereDoesntHave('pago')
                    ->orWhereHas('pago', function ($pago) {
                        $pago->whereIn('estado', [
                            PagoEstado::Pendiente->value,
                            PagoEstado::Anulado->value,
                        ]);
                    });
            })
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($o) {
                $calculado = PagoEloquentModel::calcularDesdeOrden($o);
                $descuento = (float) ($o->factura?->descuento ?? $o->pago?->descuento ?? 0);
                $total = $o->factura
                    ? (float) $o->factura->total
                    : ($o->pago
                        ? (float) $o->pago->total
                        : max(0, $calculado['valor_servicios'] + $calculado['valor_repuestos'] - $descuento));

                $pago = $o->pago;
                $pagoEstado = $pago?->estado instanceof PagoEstado
                    ? $pago->estado->value
                    : ($pago?->estado ? (string) $pago->estado : null);

                $sufijo = match ($pagoEstado) {
                    PagoEstado::Pendiente->value => ' · Completar cobro',
                    PagoEstado::Anulado->value => ' · Reabrir cobro',
                    default => '',
                };

                return [
                    'id' => $o->id,
                    'label' => $o->numero . ' — ' . ($o->vehiculo?->placa ?? '') . ' (' . ($o->cliente?->razon_social ?? '') . ')' . $sufijo,
                    'valorServicios' => $calculado['valor_servicios'],
                    'valorRepuestos' => $calculado['valor_repuestos'],
                    'descuento' => $descuento,
                    'total' => round($total, 2),
                    'tieneFactura' => (bool) $o->factura,
                    'facturaNumero' => $o->factura?->numero,
                    'pagoId' => $pago?->id,
                    'pagoEstado' => $pagoEstado,
                ];
            })->values()->toArray();
    }

    private function esEstadoCobrable(OrdenTrabajoEloquentModel $orden): bool
    {
        if ($orden->estado instanceof OrdenEstado) {
            return in_array($orden->estado, [OrdenEstado::Finalizada, OrdenEstado::Entregada], true);
        }

        return in_array((string) $orden->estado, [
            OrdenEstado::Finalizada->value,
            OrdenEstado::Entregada->value,
        ], true);
    }

    private function marcarEntregadaAlCobrar(OrdenTrabajoEloquentModel $orden): void
    {
        if (!$this->esEstadoCobrable($orden) || $orden->estado === OrdenEstado::Entregada) {
            return;
        }

        $estadoAnterior = $orden->estado instanceof \BackedEnum ? $orden->estado->value : (string) $orden->estado;
        $orden->update(['estado' => OrdenEstado::Entregada]);
        $this->estadoNotifier->notifyIfChanged($orden->fresh(['cliente', 'vehiculo']), $estadoAnterior);
    }
}

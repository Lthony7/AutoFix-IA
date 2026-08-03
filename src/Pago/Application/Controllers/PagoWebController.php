<?php

namespace Src\Pago\Application\Controllers;

use App\Enums\FacturaEstado;
use App\Enums\PagoEstado;
use App\Http\Controllers\Controller;
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
    public function index(Request $request): Response
    {
        $estado = trim((string) $request->query('estado', ''));
        $query = PagoEloquentModel::with(['ordenTrabajo.cliente', 'ordenTrabajo.vehiculo'])
            ->orderByDesc('created_at');

        if ($estado !== '' && in_array($estado, PagoEstado::values(), true)) {
            $query->where('estado', $estado);
        }

        $paginator = $query
            ->paginate(InertiaTablePaginator::PER_PAGE)
            ->withQueryString()
            ->through(fn (PagoEloquentModel $p) => $this->mapPago($p));

        $counts = PagoEloquentModel::query()
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $ingresosMes = (float) PagoEloquentModel::query()
            ->where('estado', PagoEstado::Pagado->value)
            ->where('created_at', '>=', now()->copy()->startOfMonth())
            ->sum('total');

        return Inertia::render('Pago/index', [
            'pagos' => InertiaTablePaginator::make($paginator),
            'filters' => ['estado' => $estado],
            'stats' => [
                'pagado' => (int) ($counts[PagoEstado::Pagado->value] ?? 0),
                'pendiente' => (int) ($counts[PagoEstado::Pendiente->value] ?? 0),
                'anulado' => (int) ($counts[PagoEstado::Anulado->value] ?? 0),
                'ingresosMes' => $ingresosMes,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Pago/create', [
            'ordenes' => $this->ordenesSinPagoOptions(),
            'ivaRate' => (float) config('autofix.iva_rate', 0.15),
            'ordenTrabajoId' => $request->query('ordenTrabajoId'),
        ]);
    }

    public function store(StorePagoRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            $orden = OrdenTrabajoEloquentModel::with(['ordenServicios', 'ordenRepuestos', 'factura'])
                ->findOrFail($data['orden_trabajo_id']);

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

            $this->sincronizarMontosFactura($pago, $montos);
            $this->sincronizarEstadoFactura($pago);

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
            $this->sincronizarMontosFactura($pago, $montos);
            $this->sincronizarEstadoFactura($pago);

            return redirect()->route('pagos.index')->with('success', 'Pago actualizado exitosamente');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Error al actualizar pago: ' . $e->getMessage());
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        $pago = PagoEloquentModel::find($id);

        if (!$pago) {
            return redirect()->back()->with('error', 'Pago no encontrado');
        }

        $pago->delete();

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

    /**
     * @param  array{descuento: float, total: float, iva: float, subtotal: float}  $montos
     */
    private function sincronizarMontosFactura(PagoEloquentModel $pago, array $montos): void
    {
        $factura = $pago->factura
            ?? ($pago->factura_id ? FacturaEloquentModel::find($pago->factura_id) : null);

        if (!$factura || $factura->estado === FacturaEstado::Anulada) {
            return;
        }

        $factura->update([
            'descuento' => $montos['descuento'],
            'subtotal' => $montos['subtotal'],
            'iva' => $montos['iva'],
            'total' => $montos['total'],
        ]);
    }

    private function sincronizarEstadoFactura(PagoEloquentModel $pago): void
    {
        $factura = $pago->factura
            ?? ($pago->factura_id ? FacturaEloquentModel::find($pago->factura_id) : null);

        if (!$factura || $factura->estado === FacturaEstado::Anulada) {
            return;
        }

        $estadoPago = $pago->estado instanceof PagoEstado
            ? $pago->estado
            : PagoEstado::tryFrom((string) $pago->estado);

        if ($estadoPago === PagoEstado::Pagado) {
            $factura->update(['estado' => FacturaEstado::Pagada]);
        } elseif (
            $estadoPago === PagoEstado::Pendiente
            && $factura->estado === FacturaEstado::Pagada
        ) {
            $factura->update(['estado' => FacturaEstado::Emitida]);
        }
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

    private function ordenesSinPagoOptions(): array
    {
        return OrdenTrabajoEloquentModel::with([
            'cliente',
            'vehiculo',
            'ordenServicios',
            'ordenRepuestos',
            'factura',
        ])
            ->whereDoesntHave('pago')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($o) {
                $calculado = PagoEloquentModel::calcularDesdeOrden($o);
                $descuento = (float) ($o->factura?->descuento ?? 0);
                $total = $o->factura
                    ? (float) $o->factura->total
                    : max(0, $calculado['valor_servicios'] + $calculado['valor_repuestos'] - $descuento);

                return [
                    'id' => $o->id,
                    'label' => $o->numero . ' — ' . ($o->vehiculo?->placa ?? '') . ' (' . ($o->cliente?->razon_social ?? '') . ')',
                    'valorServicios' => $calculado['valor_servicios'],
                    'valorRepuestos' => $calculado['valor_repuestos'],
                    'descuento' => $descuento,
                    'total' => round($total, 2),
                    'tieneFactura' => (bool) $o->factura,
                    'facturaNumero' => $o->factura?->numero,
                ];
            })->values()->toArray();
    }
}
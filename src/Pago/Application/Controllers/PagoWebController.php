<?php

namespace Src\Pago\Application\Controllers;

use App\Enums\FacturaEstado;
use App\Enums\PagoEstado;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Src\Factura\Infrastructure\Models\FacturaEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;
use Src\Pago\Infrastructure\Models\PagoEloquentModel;
use Src\Pago\Infrastructure\Requests\StorePagoRequest;
use Src\Pago\Infrastructure\Requests\UpdatePagoRequest;

class PagoWebController extends Controller
{
    public function index(): Response
    {
        $pagos = PagoEloquentModel::with(['ordenTrabajo.cliente', 'ordenTrabajo.vehiculo'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PagoEloquentModel $p) => $this->mapPago($p))
            ->toArray();

        return Inertia::render('Pago/index', [
            'pagos' => [
                'data' => $pagos,
                'meta' => ['total' => count($pagos)],
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Pago/create', [
            'ordenes' => $this->ordenesSinPagoOptions(),
        ]);
    }

    public function store(StorePagoRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            $orden = OrdenTrabajoEloquentModel::with(['ordenServicios', 'ordenRepuestos', 'factura'])
                ->findOrFail($data['orden_trabajo_id']);

            $calculado = PagoEloquentModel::calcularDesdeOrden($orden);
            $valorServicios = $data['valor_servicios'] ?? $calculado['valor_servicios'];
            $valorRepuestos = $data['valor_repuestos'] ?? $calculado['valor_repuestos'];
            $descuento = $data['descuento'] ?? ($orden->factura?->descuento ?? 0);
            $total = $data['total'] ?? ($orden->factura
                ? (float) $orden->factura->total
                : ($valorServicios + $valorRepuestos - $descuento));

            $estado = $data['estado'] ?? PagoEstado::Pendiente->value;

            $pago = PagoEloquentModel::create([
                'orden_trabajo_id' => $orden->id,
                'factura_id' => $orden->factura?->id,
                'valor_servicios' => $valorServicios,
                'valor_repuestos' => $valorRepuestos,
                'descuento' => $descuento,
                'total' => max(0, $total),
                'estado' => $estado,
                'metodo_pago' => $data['metodo_pago'] ?? null,
                'registrado_por' => $data['registrado_por'] ?? $request->user()?->id,
            ]);

            $this->sincronizarEstadoFactura($pago);

            return redirect()->route('pagos.index')->with('success', 'Pago registrado exitosamente');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Error al registrar pago: ' . $e->getMessage());
        }
    }

    public function edit(string $id): Response
    {
        $pago = PagoEloquentModel::with(['ordenTrabajo'])->findOrFail($id);

        return Inertia::render('Pago/edit', [
            'pago' => $this->mapPago($pago),
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
            $calculado = PagoEloquentModel::calcularDesdeOrden($pago->ordenTrabajo);

            $valorServicios = $data['valor_servicios'] ?? $calculado['valor_servicios'];
            $valorRepuestos = $data['valor_repuestos'] ?? $calculado['valor_repuestos'];
            $descuento = $data['descuento'] ?? $pago->descuento;
            $total = $data['total'] ?? ($valorServicios + $valorRepuestos - $descuento);

            if (!$pago->factura_id && $pago->ordenTrabajo?->factura) {
                $data['factura_id'] = $pago->ordenTrabajo->factura->id;
            }

            $pago->update(array_merge($data, [
                'valor_servicios' => $valorServicios,
                'valor_repuestos' => $valorRepuestos,
                'descuento' => $descuento,
                'total' => max(0, $total),
            ]));

            $this->sincronizarEstadoFactura($pago->fresh('factura'));

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
        return OrdenTrabajoEloquentModel::with(['cliente', 'vehiculo'])
            ->whereDoesntHave('pago')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'label' => $o->numero . ' — ' . ($o->vehiculo?->placa ?? '') . ' (' . ($o->cliente?->razon_social ?? '') . ')',
            ])->values()->toArray();
    }
}

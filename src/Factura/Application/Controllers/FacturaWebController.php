<?php

namespace Src\Factura\Application\Controllers;

use App\Enums\FacturaEstado;
use App\Http\Controllers\Controller;
use App\Support\InertiaTablePaginator;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Src\Factura\Infrastructure\Models\DetalleFacturaEloquentModel;
use Src\Factura\Infrastructure\Models\FacturaEloquentModel;
use Src\Factura\Infrastructure\Requests\StoreFacturaRequest;
use Src\Factura\Infrastructure\Requests\UpdateFacturaRequest;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;

class FacturaWebController extends Controller
{
    public function index(): Response
    {
        $paginator = FacturaEloquentModel::with(['cliente', 'ordenTrabajo'])
            ->orderByDesc('created_at')
            ->paginate(InertiaTablePaginator::PER_PAGE)
            ->withQueryString()
            ->through(fn (FacturaEloquentModel $f) => $this->mapFactura($f));

        return Inertia::render('Factura/index', [
            'facturas' => InertiaTablePaginator::make($paginator),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Factura/create', [
            'ordenes' => $this->ordenesSinFacturaOptions(),
            'ivaRate' => (float) config('autofix.iva_rate', 0.15),
            'serieDefault' => config('autofix.serie_default', 'F001'),
            'ordenTrabajoId' => $request->query('ordenTrabajoId'),
        ]);
    }

    public function store(StoreFacturaRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            $orden = OrdenTrabajoEloquentModel::with([
                'ordenServicios.servicio',
                'ordenRepuestos.producto',
            ])->findOrFail($data['orden_trabajo_id']);

            $calculado = FacturaEloquentModel::calcularDesdeOrden(
                $orden,
                (float) ($data['descuento'] ?? 0)
            );

            if ($calculado['detalles'] === []) {
                return redirect()->back()->withInput()
                    ->with('error', 'La orden no tiene ítems para facturar.');
            }

            $factura = DB::transaction(function () use ($request, $data, $orden, $calculado) {
                $factura = FacturaEloquentModel::create([
                    'numero' => FacturaEloquentModel::generarNumero(),
                    'serie' => $data['serie'] ?? config('autofix.serie_default', 'F001'),
                    'orden_trabajo_id' => $orden->id,
                    'cliente_id' => $orden->cliente_id,
                    'usuario_id' => $request->user()?->id,
                    'fecha_emision' => $data['fecha_emision'],
                    'subtotal' => $calculado['subtotal'],
                    'iva' => $calculado['iva'],
                    'descuento' => $calculado['descuento'],
                    'total' => $calculado['total'],
                    'estado' => $data['estado'] ?? FacturaEstado::Emitida->value,
                    'observaciones' => $data['observaciones'] ?? null,
                ]);

                foreach ($calculado['detalles'] as $detalle) {
                    DetalleFacturaEloquentModel::create([
                        'factura_id' => $factura->id,
                        ...$detalle,
                    ]);
                }

                return $factura;
            });

            return redirect()
                ->route('facturas.show', $factura->id)
                ->with('success', 'Factura generada exitosamente');
        } catch (Exception $e) {
            return redirect()->back()->withInput()
                ->with('error', 'Error al generar factura: ' . $e->getMessage());
        }
    }

    public function show(string $id): Response
    {
        $factura = FacturaEloquentModel::with([
            'cliente',
            'ordenTrabajo.vehiculo',
            'detalles',
            'pago',
        ])->findOrFail($id);

        return Inertia::render('Factura/show', [
            'factura' => $this->mapFactura($factura, true),
            'ivaRate' => (float) config('autofix.iva_rate', 0.15),
        ]);
    }

    public function edit(string $id): Response
    {
        $factura = FacturaEloquentModel::with(['cliente', 'ordenTrabajo', 'detalles'])
            ->findOrFail($id);

        return Inertia::render('Factura/edit', [
            'factura' => $this->mapFactura($factura, true),
            'estados' => collect(FacturaEstado::cases())->map(fn (FacturaEstado $e) => [
                'value' => $e->value,
                'label' => $e->label(),
            ]),
            'ivaRate' => (float) config('autofix.iva_rate', 0.15),
        ]);
    }

    public function update(UpdateFacturaRequest $request, string $id): RedirectResponse
    {
        try {
            $factura = FacturaEloquentModel::with(['ordenTrabajo.ordenServicios.servicio', 'ordenTrabajo.ordenRepuestos.producto'])
                ->findOrFail($id);

            if ($factura->estado === FacturaEstado::Anulada) {
                return redirect()->back()->with('error', 'No se puede editar una factura anulada.');
            }

            $data = $request->validated();

            if (array_key_exists('descuento', $data) && $factura->ordenTrabajo) {
                $calculado = FacturaEloquentModel::calcularDesdeOrden(
                    $factura->ordenTrabajo,
                    (float) $data['descuento']
                );
                $data['subtotal'] = $calculado['subtotal'];
                $data['iva'] = $calculado['iva'];
                $data['descuento'] = $calculado['descuento'];
                $data['total'] = $calculado['total'];
            }

            $factura->update($data);

            return redirect()
                ->route('facturas.show', $factura->id)
                ->with('success', 'Factura actualizada exitosamente');
        } catch (Exception $e) {
            return redirect()->back()->withInput()
                ->with('error', 'Error al actualizar factura: ' . $e->getMessage());
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        $factura = FacturaEloquentModel::with('pago')->find($id);

        if (!$factura) {
            return redirect()->back()->with('error', 'Factura no encontrada');
        }

        if ($factura->pago) {
            return redirect()->back()->with('error', 'No se puede eliminar una factura con pago asociado. Anúlala en su lugar.');
        }

        $factura->delete();

        return redirect()->route('facturas.index')->with('success', 'Factura eliminada exitosamente');
    }

    private function mapFactura(FacturaEloquentModel $factura, bool $detailed = false): array
    {
        $cliente = $factura->cliente;
        $nombreCliente = null;
        if ($cliente) {
            $completo = trim(($cliente->nombres ?? '') . ' ' . ($cliente->apellidos ?? ''));
            $nombreCliente = $completo !== '' ? $completo : $cliente->razon_social;
        }

        $data = [
            'id' => $factura->id,
            'numero' => $factura->numero,
            'serie' => $factura->serie,
            'ordenTrabajoId' => $factura->orden_trabajo_id,
            'ordenNumero' => $factura->ordenTrabajo?->numero,
            'clienteId' => $factura->cliente_id,
            'clienteNombre' => $nombreCliente,
            'vehiculoPlaca' => $factura->ordenTrabajo?->vehiculo?->placa,
            'fechaEmision' => $factura->fecha_emision?->format('Y-m-d'),
            'subtotal' => (float) $factura->subtotal,
            'iva' => (float) $factura->iva,
            'descuento' => (float) $factura->descuento,
            'total' => (float) $factura->total,
            'estado' => $factura->estado?->value ?? $factura->estado,
            'estadoLabel' => $factura->estado?->label(),
            'observaciones' => $factura->observaciones,
            'tienePago' => (bool) $factura->relationLoaded('pago')
                ? (bool) $factura->pago
                : $factura->pago()->exists(),
            'createdAt' => $factura->created_at?->format('Y-m-d H:i:s'),
        ];

        if ($detailed) {
            $data['detalles'] = $factura->detalles->map(fn ($d) => [
                'id' => $d->id,
                'descripcion' => $d->descripcion,
                'tipo' => $d->tipo,
                'referenciaId' => $d->referencia_id,
                'cantidad' => $d->cantidad,
                'precioUnitario' => (float) $d->precio_unitario,
                'subtotal' => (float) $d->subtotal,
            ])->toArray();
        }

        return $data;
    }

    private function ordenesSinFacturaOptions(): array
    {
        return OrdenTrabajoEloquentModel::with(['cliente', 'vehiculo', 'ordenServicios', 'ordenRepuestos'])
            ->whereDoesntHave('factura')
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn ($o) => $o->ordenServicios->isNotEmpty() || $o->ordenRepuestos->isNotEmpty())
            ->map(function ($o) {
                $calculado = FacturaEloquentModel::calcularDesdeOrden($o);

                return [
                    'id' => $o->id,
                    'label' => $o->numero . ' — ' . ($o->vehiculo?->placa ?? '') . ' (' . ($o->cliente?->razon_social ?? '') . ')',
                    'subtotal' => $calculado['subtotal'],
                    'iva' => $calculado['iva'],
                    'total' => $calculado['total'],
                    'detalles' => $calculado['detalles'],
                ];
            })
            ->values()
            ->toArray();
    }
}

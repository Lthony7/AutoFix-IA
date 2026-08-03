<?php

namespace Src\Factura\Application\Controllers;

use App\Enums\FacturaEstado;
use App\Http\Controllers\Controller;
use App\Services\FacturaClienteNotifier;
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
    public function __construct(
        private readonly FacturaClienteNotifier $facturaNotifier,
    ) {
    }

    public function index(Request $request): Response
    {
        $estado = trim((string) $request->query('estado', ''));
        $query = FacturaEloquentModel::with(['cliente', 'ordenTrabajo'])
            ->orderByDesc('created_at');

        if ($estado !== '' && in_array($estado, FacturaEstado::values(), true)) {
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

        return Inertia::render('Factura/index', [
            'facturas' => InertiaTablePaginator::make($paginator),
            'filters' => ['estado' => $estado],
            'stats' => [
                'total' => (int) FacturaEloquentModel::count(),
                'borrador' => (int) ($counts[FacturaEstado::Borrador->value] ?? 0),
                'emitida' => (int) ($counts[FacturaEstado::Emitida->value] ?? 0),
                'pagada' => (int) ($counts[FacturaEstado::Pagada->value] ?? 0),
                'anulada' => (int) ($counts[FacturaEstado::Anulada->value] ?? 0),
            ],
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
                'cliente',
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
                $razonSocial = trim(($data['cliente_nombres'] ?? '') . ' ' . ($data['cliente_apellidos'] ?? ''));

                $factura = FacturaEloquentModel::create([
                    'numero' => FacturaEloquentModel::generarNumero(),
                    'serie' => $data['serie'] ?? config('autofix.serie_default', 'F001'),
                    'orden_trabajo_id' => $orden->id,
                    'cliente_id' => $orden->cliente_id,
                    'cliente_tipo_documento' => $data['cliente_tipo_documento'],
                    'cliente_numero_documento' => $data['cliente_numero_documento'],
                    'cliente_nombres' => $data['cliente_nombres'],
                    'cliente_apellidos' => $data['cliente_apellidos'],
                    'cliente_razon_social' => $razonSocial,
                    'cliente_direccion' => $data['cliente_direccion'],
                    'cliente_telefono' => $data['cliente_telefono'],
                    'cliente_email' => $data['cliente_email'],
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

                if (!empty($data['actualizar_cliente']) && $orden->cliente) {
                    $orden->cliente->update([
                        'tipo_documento' => $data['cliente_tipo_documento'],
                        'numero_documento' => $data['cliente_numero_documento'],
                        'nombres' => $data['cliente_nombres'],
                        'apellidos' => $data['cliente_apellidos'],
                        'razon_social' => $razonSocial,
                        'direccion' => $data['cliente_direccion'],
                        'telefono' => $data['cliente_telefono'],
                        'email' => $data['cliente_email'],
                    ]);
                }

                return $factura;
            });

            $estadoFactura = $factura->estado instanceof \BackedEnum
                ? $factura->estado->value
                : (string) $factura->estado;

            if (in_array($estadoFactura, [FacturaEstado::Emitida->value, FacturaEstado::Pagada->value], true)) {
                $this->facturaNotifier->notifyEmitida($factura->fresh(['cliente.user', 'ordenTrabajo.vehiculo']));
            }

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
            $estadoAnteriorValue = $factura->estado instanceof \BackedEnum
                ? $factura->estado->value
                : (string) $factura->estado;

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

            $estadoNuevo = $factura->fresh()->estado instanceof \BackedEnum
                ? $factura->fresh()->estado->value
                : (string) $factura->fresh()->estado;

            if (
                $estadoAnteriorValue !== FacturaEstado::Emitida->value
                && $estadoNuevo === FacturaEstado::Emitida->value
            ) {
                $this->facturaNotifier->notifyEmitida($factura->fresh(['cliente.user', 'ordenTrabajo.vehiculo']));
            }

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
        $nombreSnapshot = trim(($factura->cliente_nombres ?? '') . ' ' . ($factura->cliente_apellidos ?? ''));
        if ($nombreSnapshot === '' && $factura->cliente_razon_social) {
            $nombreSnapshot = $factura->cliente_razon_social;
        }

        $nombreCliente = $nombreSnapshot !== ''
            ? $nombreSnapshot
            : ($cliente
                ? (trim(($cliente->nombres ?? '') . ' ' . ($cliente->apellidos ?? '')) ?: $cliente->razon_social)
                : null);

        $data = [
            'id' => $factura->id,
            'numero' => $factura->numero,
            'serie' => $factura->serie,
            'ordenTrabajoId' => $factura->orden_trabajo_id,
            'ordenNumero' => $factura->ordenTrabajo?->numero,
            'clienteId' => $factura->cliente_id,
            'clienteNombre' => $nombreCliente,
            'clienteTipoDocumento' => $factura->cliente_tipo_documento ?? $cliente?->tipo_documento,
            'clienteNumeroDocumento' => $factura->cliente_numero_documento ?? $cliente?->numero_documento,
            'clienteNombres' => $factura->cliente_nombres ?? $cliente?->nombres,
            'clienteApellidos' => $factura->cliente_apellidos ?? $cliente?->apellidos,
            'clienteDireccion' => $factura->cliente_direccion ?? $cliente?->direccion,
            'clienteTelefono' => $factura->cliente_telefono ?? $cliente?->telefono,
            'clienteEmail' => $factura->cliente_email ?? $cliente?->email,
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
                'tipoLabel' => match ($d->tipo) {
                    'servicio' => 'Servicio / reparación',
                    'repuesto' => 'Pieza',
                    default => ucfirst((string) $d->tipo),
                },
                'referenciaId' => $d->referencia_id,
                'cantidad' => $d->cantidad,
                'precioUnitario' => (float) $d->precio_unitario,
                'subtotal' => (float) $d->subtotal,
            ])->toArray();

            $data['totalServicios'] = (float) $factura->detalles
                ->where('tipo', 'servicio')
                ->sum('subtotal');
            $data['totalPiezas'] = (float) $factura->detalles
                ->where('tipo', 'repuesto')
                ->sum('subtotal');
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
                $c = $o->cliente;

                return [
                    'id' => $o->id,
                    'label' => $o->numero . ' — ' . ($o->vehiculo?->placa ?? '') . ' (' . ($c?->razon_social ?? '') . ')',
                    'subtotal' => $calculado['subtotal'],
                    'iva' => $calculado['iva'],
                    'total' => $calculado['total'],
                    'detalles' => $calculado['detalles'],
                    'cliente' => $c ? [
                        'tipoDocumento' => $c->tipo_documento,
                        'numeroDocumento' => $c->numero_documento,
                        'nombres' => $c->nombres ?? '',
                        'apellidos' => $c->apellidos ?? '',
                        'direccion' => $c->direccion ?? '',
                        'telefono' => $c->telefono ?? '',
                        'email' => $c->email ?? '',
                    ] : null,
                ];
            })
            ->values()
            ->toArray();
    }
}

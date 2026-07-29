<?php

namespace Src\Reporte\Application\Controllers;

use App\Enums\FacturaEstado;
use App\Http\Controllers\Controller;
use App\Services\ClienteCuentaService;
use App\Support\InertiaTablePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Src\Auth\Infrastructure\Models\UserEloquentModel;
use Src\Cliente\Infrastructure\Models\ClienteEloquentModel;
use Src\Factura\Infrastructure\Models\FacturaEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;
use Src\Reporte\Infrastructure\Requests\StorePortalVehiculoRequest;
use Src\Reporte\Infrastructure\Requests\UpdatePortalClienteRequest;
use Src\Vehiculo\Infrastructure\Models\VehiculoEloquentModel;

class PortalClienteWebController extends Controller
{
    public function __construct(
        private readonly ClienteCuentaService $clienteCuenta,
    ) {
    }

    public function misVehiculos(Request $request): Response
    {
        $cliente = $this->clienteActual($request);

        $paginator = VehiculoEloquentModel::where('cliente_id', $cliente->id)
            ->orderBy('placa')
            ->paginate(InertiaTablePaginator::PER_PAGE)
            ->withQueryString()
            ->through(fn ($v) => [
                'id' => $v->id,
                'placa' => $v->placa,
                'marca' => $v->marca,
                'modelo' => $v->modelo,
                'anio' => $v->anio,
                'color' => $v->color,
                'kilometraje' => $v->kilometraje,
                'activo' => (bool) $v->activo,
            ]);

        return Inertia::render('PortalCliente/vehiculos', [
            'vehiculos' => InertiaTablePaginator::make($paginator),
            'puedeRegistrar' => true,
        ]);
    }

    public function crearVehiculo(Request $request): Response
    {
        $this->clienteActual($request);

        return Inertia::render('PortalCliente/vehiculo-create', [
            'returnTo' => $request->query('return', 'presupuestos'),
        ]);
    }

    public function guardarVehiculo(StorePortalVehiculoRequest $request): RedirectResponse
    {
        $cliente = $this->clienteActual($request);
        $data = $request->validated();

        VehiculoEloquentModel::create([
            ...$data,
            'cliente_id' => $cliente->id,
            'activo' => true,
        ]);

        $return = $request->input('returnTo', $request->query('return', 'presupuestos'));

        if ($return === 'vehiculos') {
            return redirect()
                ->route('portal.mis-vehiculos')
                ->with('success', 'Vehículo registrado correctamente.');
        }

        return redirect()
            ->route('portal.presupuestos.create')
            ->with('success', 'Vehículo registrado. Ya puedes armar tu presupuesto.');
    }

    public function misOrdenes(): Response
    {
        $clienteIds = $this->clienteIdsDelUsuario();

        $paginator = OrdenTrabajoEloquentModel::with(['vehiculo', 'pago', 'factura', 'sugerenciaIa'])
            ->whereIn('cliente_id', $clienteIds)
            ->orderByDesc('created_at')
            ->paginate(InertiaTablePaginator::PER_PAGE)
            ->withQueryString()
            ->through(fn ($orden) => [
                'id' => $orden->id,
                'numero' => $orden->numero,
                'estado' => $orden->estado instanceof \BackedEnum ? $orden->estado->value : $orden->estado,
                'estadoLabel' => $orden->estado instanceof \BackedEnum ? $orden->estado->label() : $orden->estado,
                'vehiculoPlaca' => $orden->vehiculo?->placa,
                'tipoFalla' => $orden->tipo_falla,
                'prioridad' => $orden->prioridad,
                'totalPago' => $orden->pago ? (float) $orden->pago->total : null,
                'tieneReporteIa' => (bool) $orden->sugerenciaIa,
                'facturaId' => $orden->factura
                    && in_array(
                        $orden->factura->estado instanceof \BackedEnum
                            ? $orden->factura->estado->value
                            : (string) $orden->factura->estado,
                        [FacturaEstado::Emitida->value, FacturaEstado::Pagada->value],
                        true
                    )
                    ? $orden->factura->id
                    : null,
                'createdAt' => $orden->created_at?->format('Y-m-d H:i:s'),
            ]);

        return Inertia::render('PortalCliente/ordenes', [
            'ordenes' => InertiaTablePaginator::make($paginator),
            'notificaciones' => $this->notificacionesRecientes(),
        ]);
    }

    public function mostrarOrden(string $orden): Response
    {
        $ordenModel = $this->ordenDelCliente($orden);
        $ordenModel->load([
            'vehiculo',
            'avances.user',
            'sugerenciaIa',
            'factura.detalles',
            'pago',
        ]);

        $diagnostico = $ordenModel->sugerenciaIa;
        $factura = $ordenModel->factura;
        $facturaVisible = $factura && in_array(
            $factura->estado instanceof \BackedEnum ? $factura->estado->value : (string) $factura->estado,
            [FacturaEstado::Emitida->value, FacturaEstado::Pagada->value],
            true
        );

        return Inertia::render('PortalCliente/orden-show', [
            'orden' => [
                'id' => $ordenModel->id,
                'numero' => $ordenModel->numero,
                'estado' => $ordenModel->estado instanceof \BackedEnum ? $ordenModel->estado->value : $ordenModel->estado,
                'estadoLabel' => $ordenModel->estado instanceof \BackedEnum ? $ordenModel->estado->label() : $ordenModel->estado,
                'tipoFalla' => $ordenModel->tipo_falla,
                'fallaReportada' => $ordenModel->falla_reportada,
                'prioridad' => $ordenModel->prioridad,
                'vehiculoPlaca' => $ordenModel->vehiculo?->placa,
                'vehiculoDescripcion' => $ordenModel->vehiculo
                    ? trim(($ordenModel->vehiculo->marca ?? '') . ' ' . ($ordenModel->vehiculo->modelo ?? ''))
                    : null,
                'kilometrajeIngreso' => $ordenModel->kilometraje_ingreso,
                'createdAt' => $ordenModel->created_at?->format('Y-m-d H:i:s'),
            ],
            'reporteIa' => $diagnostico ? [
                'diagnosticoDetalle' => $diagnostico->diagnostico_detalle,
                'respuestaCompleta' => $diagnostico->respuesta_completa,
                'advertencia' => $diagnostico->advertencia,
                'prioridad' => $diagnostico->prioridad,
                'estadoLabel' => $diagnostico->estado?->label(),
                'createdAt' => $diagnostico->created_at?->format('Y-m-d H:i:s'),
            ] : null,
            'observacionesMecanico' => $diagnostico?->observaciones_revision,
            'coincideAnalisis' => $diagnostico?->coincide_analisis,
            'avances' => $ordenModel->avances
                ->sortByDesc('created_at')
                ->values()
                ->map(fn ($avance) => [
                    'id' => $avance->id,
                    'mensaje' => $avance->mensaje,
                    'usuarioNombre' => $avance->user?->name ?? 'Taller',
                    'createdAt' => $avance->created_at?->format('Y-m-d H:i:s'),
                ])->toArray(),
            'factura' => $facturaVisible ? [
                'id' => $factura->id,
                'numero' => $factura->numero,
                'total' => (float) $factura->total,
                'estadoLabel' => $factura->estado?->label(),
            ] : null,
        ]);
    }

    public function mostrarFactura(string $factura): Response
    {
        $clienteIds = $this->clienteIdsDelUsuario();

        $facturaModel = FacturaEloquentModel::with(['cliente', 'ordenTrabajo.vehiculo', 'detalles', 'pago'])
            ->whereIn('cliente_id', $clienteIds)
            ->whereIn('estado', [FacturaEstado::Emitida->value, FacturaEstado::Pagada->value])
            ->findOrFail($factura);

        $detalles = $facturaModel->detalles->map(fn ($d) => [
            'id' => $d->id,
            'descripcion' => $d->descripcion,
            'tipo' => $d->tipo,
            'tipoLabel' => match ($d->tipo) {
                'servicio' => 'Servicio / reparación',
                'repuesto' => 'Pieza',
                default => ucfirst((string) $d->tipo),
            },
            'cantidad' => $d->cantidad,
            'precioUnitario' => (float) $d->precio_unitario,
            'subtotal' => (float) $d->subtotal,
        ])->values()->toArray();

        $totalServicios = collect($detalles)->where('tipo', 'servicio')->sum('subtotal');
        $totalPiezas = collect($detalles)->where('tipo', 'repuesto')->sum('subtotal');

        return Inertia::render('PortalCliente/factura-show', [
            'factura' => [
                'id' => $facturaModel->id,
                'numero' => $facturaModel->numero,
                'serie' => $facturaModel->serie,
                'ordenNumero' => $facturaModel->ordenTrabajo?->numero,
                'ordenId' => $facturaModel->orden_trabajo_id,
                'vehiculoPlaca' => $facturaModel->ordenTrabajo?->vehiculo?->placa,
                'fechaEmision' => $facturaModel->fecha_emision?->format('Y-m-d'),
                'subtotal' => (float) $facturaModel->subtotal,
                'iva' => (float) $facturaModel->iva,
                'descuento' => (float) $facturaModel->descuento,
                'total' => (float) $facturaModel->total,
                'estado' => $facturaModel->estado?->value ?? $facturaModel->estado,
                'estadoLabel' => $facturaModel->estado?->label(),
                'observaciones' => $facturaModel->observaciones,
                'detalles' => $detalles,
                'totalServicios' => (float) $totalServicios,
                'totalPiezas' => (float) $totalPiezas,
            ],
            'ivaRate' => (float) config('autofix.iva_rate', 0.15),
        ]);
    }

    public function historial(): Response
    {
        $clienteIds = $this->clienteIdsDelUsuario();

        $paginator = OrdenTrabajoEloquentModel::with([
            'vehiculo',
            'ordenServicios.servicio',
            'avances',
        ])
            ->whereIn('cliente_id', $clienteIds)
            ->orderByDesc('created_at')
            ->paginate(InertiaTablePaginator::PER_PAGE)
            ->withQueryString()
            ->through(fn ($orden) => [
                'id' => $orden->id,
                'numero' => $orden->numero,
                'estado' => $orden->estado instanceof \BackedEnum ? $orden->estado->value : $orden->estado,
                'estadoLabel' => $orden->estado instanceof \BackedEnum ? $orden->estado->label() : $orden->estado,
                'vehiculoPlaca' => $orden->vehiculo?->placa,
                'vehiculoDescripcion' => $orden->vehiculo
                    ? trim(($orden->vehiculo->marca ?? '') . ' ' . ($orden->vehiculo->modelo ?? ''))
                    : null,
                'tipoFalla' => $orden->tipo_falla,
                'diagnosticoTecnico' => $orden->diagnostico_tecnico,
                'kilometrajeIngreso' => $orden->kilometraje_ingreso,
                'servicios' => $orden->ordenServicios->map(fn ($os) => [
                    'nombre' => $os->servicio?->nombre ?? 'Servicio',
                    'precio' => (float) $os->precio,
                ])->values()->toArray(),
                'avancesRecientes' => $orden->avances->take(3)->map(fn ($avance) => [
                    'mensaje' => $avance->mensaje,
                    'createdAt' => $avance->created_at?->format('Y-m-d H:i:s'),
                ])->values()->toArray(),
                'createdAt' => $orden->created_at?->format('Y-m-d H:i:s'),
            ]);

        return Inertia::render('PortalCliente/historial', [
            'historial' => InertiaTablePaginator::make($paginator),
        ]);
    }

    public function misDatos(Request $request): Response
    {
        $cliente = $this->clienteActual($request);

        return Inertia::render('PortalCliente/perfil', [
            'cliente' => [
                'id' => $cliente->id,
                'nombres' => $cliente->nombres ?? '',
                'apellidos' => $cliente->apellidos ?? '',
                'telefono' => $cliente->telefono ?? '',
                'email' => $cliente->email ?? '',
                'direccion' => $cliente->direccion ?? '',
                'numeroDocumento' => $cliente->numero_documento,
                'tipoDocumento' => $cliente->tipo_documento,
            ],
        ]);
    }

    public function actualizarDatos(UpdatePortalClienteRequest $request): RedirectResponse
    {
        $cliente = $this->clienteActual($request);

        $data = $request->validated();
        $razonSocial = trim(($data['nombres'] ?? '') . ' ' . ($data['apellidos'] ?? ''));

        DB::transaction(function () use ($cliente, $data, $razonSocial, $request) {
            $cliente->update([
                'tipo_documento' => $data['tipo_documento'],
                'numero_documento' => $data['numero_documento'],
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'razon_social' => $razonSocial !== '' ? $razonSocial : $cliente->razon_social,
                'telefono' => $data['telefono'],
                'email' => $data['email'],
                'direccion' => $data['direccion'],
            ]);

            /** @var UserEloquentModel|null $user */
            $user = $request->user();
            if ($user) {
                $user->update([
                    'name' => $razonSocial !== '' ? $razonSocial : $user->name,
                    'email' => $data['email'],
                ]);
            }
        });

        return redirect()
            ->route('portal.mis-datos')
            ->with('success', 'Datos actualizados correctamente');
    }

    private function clienteActual(Request $request): ClienteEloquentModel
    {
        /** @var UserEloquentModel $user */
        $user = $request->user();

        return $this->clienteCuenta->ensureForUser($user);
    }

    /** @return list<string> */
    private function clienteIdsDelUsuario(): array
    {
        $userId = auth()->id();

        return ClienteEloquentModel::where('user_id', $userId)
            ->pluck('id')
            ->toArray();
    }

    private function ordenDelCliente(string $ordenId): OrdenTrabajoEloquentModel
    {
        return OrdenTrabajoEloquentModel::whereIn('cliente_id', $this->clienteIdsDelUsuario())
            ->findOrFail($ordenId);
    }

    /** @return list<array<string, mixed>> */
    private function notificacionesRecientes(): array
    {
        /** @var UserEloquentModel|null $user */
        $user = auth()->user();
        if (!$user) {
            return [];
        }

        return $user->notifications()
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'mensaje' => $n->data['mensaje'] ?? 'Nueva notificación',
                'url' => $n->data['url'] ?? null,
                'readAt' => $n->read_at?->format('Y-m-d H:i:s'),
                'createdAt' => $n->created_at?->format('Y-m-d H:i:s'),
            ])
            ->values()
            ->all();
    }
}

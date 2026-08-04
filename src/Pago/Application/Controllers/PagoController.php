<?php

namespace Src\Pago\Application\Controllers;

use App\Enums\PagoEstado;
use App\Http\Controllers\Controller;
use App\Services\SincronizarPagoFacturaService;
use Illuminate\Http\JsonResponse;
use Src\Factura\Infrastructure\Models\FacturaEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;
use Src\Pago\Infrastructure\Models\PagoEloquentModel;
use Src\Pago\Infrastructure\Requests\StorePagoRequest;
use Src\Pago\Infrastructure\Requests\UpdatePagoRequest;
use Src\Pago\Infrastructure\Resources\PagoResource;

class PagoController extends Controller
{
    public function __construct(
        private readonly SincronizarPagoFacturaService $syncFactura,
    ) {
    }

    public function index()
    {
        $pagos = PagoEloquentModel::with(['ordenTrabajo', 'factura'])
            ->orderByDesc('created_at')
            ->get();

        return PagoResource::collection($pagos);
    }

    public function store(StorePagoRequest $request)
    {
        $data = $request->validated();
        $orden = OrdenTrabajoEloquentModel::with(['ordenServicios', 'ordenRepuestos', 'factura', 'pago'])
            ->findOrFail($data['orden_trabajo_id']);

        if ($orden->pago) {
            return response()->json([
                'success' => false,
                'message' => 'Esta orden ya tiene un pago. Actualízalo en lugar de crear otro.',
                'pagoId' => $orden->pago->id,
            ], 422);
        }

        $calculado = PagoEloquentModel::calcularDesdeOrden($orden);
        $valorServicios = $data['valor_servicios'] ?? $calculado['valor_servicios'];
        $valorRepuestos = $data['valor_repuestos'] ?? $calculado['valor_repuestos'];
        $descuento = $data['descuento'] ?? ($orden->factura?->descuento ?? 0);
        $total = $data['total'] ?? ($orden->factura
            ? (float) $orden->factura->total
            : ($valorServicios + $valorRepuestos - $descuento));

        $subtotal = (float) $valorServicios + (float) $valorRepuestos;
        $descuento = max(0, min((float) $descuento, $subtotal));
        $iva = 0.0;
        $subtotalFactura = $subtotal - $descuento;

        if ($orden->factura) {
            $facturaCalc = FacturaEloquentModel::calcularDesdeOrden($orden, $descuento);
            $descuento = (float) $facturaCalc['descuento'];
            $total = (float) $facturaCalc['total'];
            $iva = (float) $facturaCalc['iva'];
            $subtotalFactura = (float) $facturaCalc['subtotal'];
        } else {
            $total = max(0, $subtotal - $descuento);
        }

        $pago = PagoEloquentModel::create([
            'orden_trabajo_id' => $orden->id,
            'factura_id' => $orden->factura?->id,
            'valor_servicios' => $valorServicios,
            'valor_repuestos' => $valorRepuestos,
            'descuento' => $descuento,
            'total' => max(0, $total),
            'estado' => $data['estado'] ?? PagoEstado::Pendiente->value,
            'metodo_pago' => $data['metodo_pago'] ?? null,
            'registrado_por' => $data['registrado_por'] ?? $request->user()?->id,
        ]);

        $this->syncFactura->sincronizarMontos($pago, [
            'descuento' => $descuento,
            'subtotal' => $subtotalFactura,
            'iva' => $iva,
            'total' => max(0, (float) $total),
        ]);
        $this->syncFactura->sincronizarEstado($pago);

        return (new PagoResource($pago->load(['ordenTrabajo', 'factura'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $id)
    {
        $pago = PagoEloquentModel::with(['ordenTrabajo', 'factura'])->find($id);

        if (!$pago) {
            return response()->json(['success' => false, 'message' => 'Pago no encontrado'], 404);
        }

        return new PagoResource($pago);
    }

    public function update(UpdatePagoRequest $request, string $id)
    {
        $pago = PagoEloquentModel::with(['ordenTrabajo.factura', 'factura'])->find($id);

        if (!$pago) {
            return response()->json(['success' => false, 'message' => 'Pago no encontrado'], 404);
        }

        $data = $request->validated();

        if (!$pago->factura_id && $pago->ordenTrabajo?->factura) {
            $data['factura_id'] = $pago->ordenTrabajo->factura->id;
        }

        $pago->update($data);
        $pago = $pago->fresh(['factura']);
        $this->syncFactura->sincronizarEstado($pago);

        return new PagoResource($pago->fresh(['ordenTrabajo', 'factura']));
    }

    public function destroy(string $id): JsonResponse
    {
        $pago = PagoEloquentModel::with('factura')->find($id);

        if (!$pago) {
            return response()->json(['success' => false, 'message' => 'Pago no encontrado'], 404);
        }

        $factura = $pago->factura
            ?? ($pago->factura_id ? FacturaEloquentModel::find($pago->factura_id) : null);

        $pago->delete();
        $this->syncFactura->alEliminarPago($factura);

        return response()->json(['success' => true, 'message' => 'Pago eliminado exitosamente']);
    }
}

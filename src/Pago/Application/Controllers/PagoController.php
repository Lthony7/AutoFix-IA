<?php

namespace Src\Pago\Application\Controllers;

use App\Enums\FacturaEstado;
use App\Enums\PagoEstado;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\Factura\Infrastructure\Models\FacturaEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;
use Src\Pago\Infrastructure\Models\PagoEloquentModel;
use Src\Pago\Infrastructure\Requests\StorePagoRequest;
use Src\Pago\Infrastructure\Requests\UpdatePagoRequest;
use Src\Pago\Infrastructure\Resources\PagoResource;

class PagoController extends Controller
{
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
        $orden = OrdenTrabajoEloquentModel::with(['ordenServicios', 'ordenRepuestos', 'factura'])
            ->findOrFail($data['orden_trabajo_id']);

        $calculado = PagoEloquentModel::calcularDesdeOrden($orden);
        $valorServicios = $data['valor_servicios'] ?? $calculado['valor_servicios'];
        $valorRepuestos = $data['valor_repuestos'] ?? $calculado['valor_repuestos'];
        $descuento = $data['descuento'] ?? ($orden->factura?->descuento ?? 0);
        $total = $data['total'] ?? ($orden->factura
            ? (float) $orden->factura->total
            : ($valorServicios + $valorRepuestos - $descuento));

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

        $this->sincronizarEstadoFactura($pago);

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
        $this->sincronizarEstadoFactura($pago->fresh('factura'));

        return new PagoResource($pago->fresh(['ordenTrabajo', 'factura']));
    }

    public function destroy(string $id): JsonResponse
    {
        $pago = PagoEloquentModel::find($id);

        if (!$pago) {
            return response()->json(['success' => false, 'message' => 'Pago no encontrado'], 404);
        }

        $pago->delete();

        return response()->json(['success' => true, 'message' => 'Pago eliminado exitosamente']);
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
}

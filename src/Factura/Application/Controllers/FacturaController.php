<?php

namespace Src\Factura\Application\Controllers;

use App\Enums\FacturaEstado;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Src\Factura\Infrastructure\Models\DetalleFacturaEloquentModel;
use Src\Factura\Infrastructure\Models\FacturaEloquentModel;
use Src\Factura\Infrastructure\Requests\StoreFacturaRequest;
use Src\Factura\Infrastructure\Requests\UpdateFacturaRequest;
use Src\Factura\Infrastructure\Resources\FacturaResource;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;

class FacturaController extends Controller
{
    public function index()
    {
        $facturas = FacturaEloquentModel::with(['cliente', 'ordenTrabajo', 'detalles'])
            ->orderByDesc('created_at')
            ->get();

        return FacturaResource::collection($facturas);
    }

    public function store(StoreFacturaRequest $request)
    {
        $data = $request->validated();
        $orden = OrdenTrabajoEloquentModel::with([
            'ordenServicios.servicio',
            'ordenRepuestos.producto',
        ])->findOrFail($data['orden_trabajo_id']);

        $calculado = FacturaEloquentModel::calcularDesdeOrden($orden, (float) ($data['descuento'] ?? 0));

        if ($calculado['detalles'] === []) {
            return response()->json([
                'success' => false,
                'message' => 'La orden no tiene ítems para facturar.',
            ], 400);
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

            return $factura->load(['cliente', 'ordenTrabajo', 'detalles']);
        });

        return (new FacturaResource($factura))->response()->setStatusCode(201);
    }

    public function show(string $id)
    {
        $factura = FacturaEloquentModel::with(['cliente', 'ordenTrabajo', 'detalles'])->find($id);

        if (!$factura) {
            return response()->json(['success' => false, 'message' => 'Factura no encontrada'], 404);
        }

        return new FacturaResource($factura);
    }

    public function update(UpdateFacturaRequest $request, string $id)
    {
        $factura = FacturaEloquentModel::with([
            'ordenTrabajo.ordenServicios.servicio',
            'ordenTrabajo.ordenRepuestos.producto',
        ])->find($id);

        if (!$factura) {
            return response()->json(['success' => false, 'message' => 'Factura no encontrada'], 404);
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

        return new FacturaResource($factura->fresh(['cliente', 'ordenTrabajo', 'detalles']));
    }

    public function destroy(string $id): JsonResponse
    {
        $factura = FacturaEloquentModel::with('pago')->find($id);

        if (!$factura) {
            return response()->json(['success' => false, 'message' => 'Factura no encontrada'], 404);
        }

        if ($factura->pago) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una factura con pago asociado',
            ], 400);
        }

        $factura->delete();

        return response()->json(['success' => true, 'message' => 'Factura eliminada exitosamente']);
    }
}

<?php

namespace Src\Presupuesto\Application\Services;

use App\Enums\PresupuestoEstado;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Src\Presupuesto\Infrastructure\Models\PresupuestoEloquentModel;
use Src\Presupuesto\Infrastructure\Models\PresupuestoRepuestoEloquentModel;
use Src\Presupuesto\Infrastructure\Models\PresupuestoServicioEloquentModel;
use Src\Producto\Infrastructure\Models\ProductoEloquentModel;
use Src\Servicio\Infrastructure\Models\ServicioEloquentModel;

class PresupuestoLineasService
{
    /**
     * @param  list<array{servicio_id?: string, servicioId?: string, cantidad?: int}>  $servicios
     * @param  list<array{producto_id?: string, productoId?: string, cantidad?: int}>  $repuestos
     */
    public function syncLineas(
        PresupuestoEloquentModel $presupuesto,
        array $servicios,
        array $repuestos,
    ): void {
        DB::transaction(function () use ($presupuesto, $servicios, $repuestos) {
            $presupuesto->servicios()->delete();
            $presupuesto->repuestos()->delete();

            $subServicios = 0.0;
            $subRepuestos = 0.0;

            foreach ($servicios as $row) {
                $servicioId = $row['servicio_id'] ?? $row['servicioId'] ?? null;
                $cantidad = max(1, (int) ($row['cantidad'] ?? 1));
                $servicio = ServicioEloquentModel::where('activo', true)->find($servicioId);

                if (!$servicio) {
                    throw ValidationException::withMessages([
                        'servicios' => 'Uno de los servicios seleccionados no está disponible.',
                    ]);
                }

                $precio = (float) $servicio->precio_base;
                PresupuestoServicioEloquentModel::create([
                    'presupuesto_id' => $presupuesto->id,
                    'servicio_id' => $servicio->id,
                    'nombre' => $servicio->nombre,
                    'precio' => $precio,
                    'cantidad' => $cantidad,
                ]);
                $subServicios += $precio * $cantidad;
            }

            foreach ($repuestos as $row) {
                $productoId = $row['producto_id'] ?? $row['productoId'] ?? null;
                $cantidad = max(1, (int) ($row['cantidad'] ?? 1));
                $producto = ProductoEloquentModel::query()
                    ->where('tipo_producto', 'repuesto')
                    ->where('activo', true)
                    ->find($productoId);

                if (!$producto) {
                    throw ValidationException::withMessages([
                        'repuestos' => 'Uno de los repuestos seleccionados no está disponible.',
                    ]);
                }

                if ((int) $producto->stock < $cantidad) {
                    throw ValidationException::withMessages([
                        'repuestos' => "Stock insuficiente para {$producto->nombre} (disponible: {$producto->stock}).",
                    ]);
                }

                $precio = (float) $producto->precio;
                PresupuestoRepuestoEloquentModel::create([
                    'presupuesto_id' => $presupuesto->id,
                    'producto_id' => $producto->id,
                    'codigo' => $producto->codigo,
                    'nombre' => $producto->nombre,
                    'precio_unitario' => $precio,
                    'cantidad' => $cantidad,
                ]);
                $subRepuestos += $precio * $cantidad;
            }

            if ($subServicios <= 0 && $subRepuestos <= 0) {
                throw ValidationException::withMessages([
                    'servicios' => 'Agrega al menos un servicio o repuesto al presupuesto.',
                ]);
            }

            $presupuesto->update([
                'subtotal_servicios' => round($subServicios, 2),
                'subtotal_repuestos' => round($subRepuestos, 2),
                'total' => round($subServicios + $subRepuestos, 2),
            ]);
        });
    }

    /** @return array<string, mixed> */
    public function mapPresupuesto(PresupuestoEloquentModel $p, bool $withLineas = true): array
    {
        $estado = $p->estado instanceof PresupuestoEstado ? $p->estado : PresupuestoEstado::tryFrom((string) $p->estado);

        $data = [
            'id' => $p->id,
            'numero' => $p->numero,
            'clienteId' => $p->cliente_id,
            'clienteNombre' => $p->cliente?->razon_social,
            'vehiculoId' => $p->vehiculo_id,
            'vehiculoPlaca' => $p->vehiculo?->placa,
            'estado' => $estado?->value ?? (string) $p->estado,
            'estadoLabel' => $estado?->label() ?? (string) $p->estado,
            'subtotalServicios' => (float) $p->subtotal_servicios,
            'subtotalRepuestos' => (float) $p->subtotal_repuestos,
            'total' => (float) $p->total,
            'notas' => $p->notas,
            'validoHasta' => $p->valido_hasta?->toDateString(),
            'editable' => $p->esEditable(),
            'usableEnCita' => $p->esUsableEnCita(),
            'createdAt' => $p->created_at?->format('Y-m-d H:i'),
        ];

        if ($withLineas) {
            $data['servicios'] = $p->servicios->map(fn ($l) => [
                'id' => $l->id,
                'servicioId' => $l->servicio_id,
                'nombre' => $l->nombre,
                'precio' => (float) $l->precio,
                'cantidad' => (int) $l->cantidad,
                'subtotal' => round((float) $l->precio * (int) $l->cantidad, 2),
            ])->values()->all();

            $data['repuestos'] = $p->repuestos->map(fn ($l) => [
                'id' => $l->id,
                'productoId' => $l->producto_id,
                'codigo' => $l->codigo,
                'nombre' => $l->nombre,
                'precioUnitario' => (float) $l->precio_unitario,
                'cantidad' => (int) $l->cantidad,
                'subtotal' => round((float) $l->precio_unitario * (int) $l->cantidad, 2),
            ])->values()->all();
        }

        return $data;
    }
}

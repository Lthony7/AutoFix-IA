<?php

namespace App\Services;

use App\Enums\FacturaEstado;
use App\Enums\PagoEstado;
use Src\Factura\Infrastructure\Models\FacturaEloquentModel;
use Src\Pago\Infrastructure\Models\PagoEloquentModel;

class SincronizarPagoFacturaService
{
    /**
     * Alinea el estado de la factura con el del pago.
     *
     * - pagado → factura pagada
     * - pendiente / anulado → factura emitida (por cobrar de nuevo)
     * - factura anulada → no se toca
     */
    public function sincronizarEstado(PagoEloquentModel $pago): void
    {
        $factura = $this->resolverFactura($pago);
        if (!$factura || $factura->estado === FacturaEstado::Anulada) {
            return;
        }

        $estadoPago = $pago->estado instanceof PagoEstado
            ? $pago->estado
            : PagoEstado::tryFrom((string) $pago->estado);

        if ($estadoPago === PagoEstado::Pagado) {
            $factura->update(['estado' => FacturaEstado::Pagada]);

            return;
        }

        if (in_array($estadoPago, [PagoEstado::Pendiente, PagoEstado::Anulado], true)) {
            if ($factura->estado !== FacturaEstado::Emitida) {
                $factura->update(['estado' => FacturaEstado::Emitida]);
            }
        }
    }

    /**
     * Tras eliminar un pago, deja la factura cobrable (emitida) si no está anulada.
     */
    public function alEliminarPago(?FacturaEloquentModel $factura): void
    {
        if (!$factura || $factura->estado === FacturaEstado::Anulada) {
            return;
        }

        if ($factura->estado !== FacturaEstado::Emitida) {
            $factura->update(['estado' => FacturaEstado::Emitida]);
        }
    }

    /**
     * @param  array{descuento: float, total: float, iva: float, subtotal: float}  $montos
     */
    public function sincronizarMontos(PagoEloquentModel $pago, array $montos): void
    {
        $factura = $this->resolverFactura($pago);
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

    private function resolverFactura(PagoEloquentModel $pago): ?FacturaEloquentModel
    {
        if ($pago->relationLoaded('factura') && $pago->factura) {
            return $pago->factura;
        }

        if ($pago->factura_id) {
            return FacturaEloquentModel::find($pago->factura_id);
        }

        return null;
    }
}

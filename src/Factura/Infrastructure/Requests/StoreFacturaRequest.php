<?php

namespace Src\Factura\Infrastructure\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;

class StoreFacturaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'orden_trabajo_id' => $this->ordenTrabajoId ?? $this->orden_trabajo_id,
            'fecha_emision' => $this->fechaEmision ?? $this->fecha_emision ?? now()->toDateString(),
            'descuento' => $this->descuento ?? 0,
            'estado' => $this->estado ?? 'emitida',
            'serie' => $this->serie ?? config('autofix.serie_default', 'F001'),
        ]);
    }

    public function rules(): array
    {
        return [
            'orden_trabajo_id' => [
                'required',
                'uuid',
                'exists:ordenes_trabajo,id',
                Rule::unique('facturas', 'orden_trabajo_id'),
            ],
            'serie' => 'nullable|string|max:20',
            'fecha_emision' => 'required|date',
            'descuento' => 'nullable|numeric|min:0',
            'estado' => 'nullable|string|in:borrador,emitida,pagada,anulada',
            'observaciones' => 'nullable|string',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $ordenId = $this->input('orden_trabajo_id');
            if (!$ordenId) {
                return;
            }

            $orden = OrdenTrabajoEloquentModel::with(['ordenServicios', 'ordenRepuestos'])->find($ordenId);
            if (!$orden) {
                return;
            }

            if ($orden->ordenServicios->isEmpty() && $orden->ordenRepuestos->isEmpty()) {
                $validator->errors()->add(
                    'ordenTrabajoId',
                    'La orden no tiene servicios ni repuestos para facturar.'
                );
            }
        });
    }

    public function attributes(): array
    {
        return [
            'orden_trabajo_id' => 'orden de trabajo',
            'fecha_emision' => 'fecha de emisión',
            'descuento' => 'descuento',
        ];
    }
}

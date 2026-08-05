<?php

namespace Src\Factura\Infrastructure\Requests;

use App\Enums\FacturaEstado;
use App\Support\FieldValidation;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Src\Factura\Infrastructure\Models\FacturaEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;

class StoreFacturaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $tipo = strtoupper(trim((string) ($this->clienteTipoDocumento ?? $this->cliente_tipo_documento ?? 'CEDULA')));
        $numero = trim((string) ($this->clienteNumeroDocumento ?? $this->cliente_numero_documento ?? ''));
        if (in_array($tipo, ['CEDULA', 'DNI', 'RUC'], true)) {
            $numero = FieldValidation::soloDigitos($numero) ?? '';
        }

        $this->merge([
            'orden_trabajo_id' => $this->ordenTrabajoId ?? $this->orden_trabajo_id,
            'fecha_emision' => $this->fechaEmision ?? $this->fecha_emision ?? now()->toDateString(),
            'descuento' => $this->descuento ?? 0,
            'estado' => $this->estado ?? 'emitida',
            'serie' => $this->serie ?? config('autofix.serie_default', 'F001'),
            'cliente_tipo_documento' => $tipo,
            'cliente_numero_documento' => $numero,
            'cliente_nombres' => trim((string) ($this->clienteNombres ?? $this->cliente_nombres ?? '')),
            'cliente_apellidos' => trim((string) ($this->clienteApellidos ?? $this->cliente_apellidos ?? '')),
            'cliente_direccion' => trim((string) ($this->clienteDireccion ?? $this->cliente_direccion ?? '')),
            'cliente_telefono' => FieldValidation::soloDigitos((string) ($this->clienteTelefono ?? $this->cliente_telefono ?? '')),
            'cliente_email' => strtolower(trim((string) ($this->clienteEmail ?? $this->cliente_email ?? ''))),
            'actualizar_cliente' => filter_var(
                $this->actualizarCliente ?? $this->actualizar_cliente ?? false,
                FILTER_VALIDATE_BOOLEAN
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'orden_trabajo_id' => [
                'required',
                'uuid',
                'exists:ordenes_trabajo,id',
                function (string $attribute, mixed $value, Closure $fail): void {
                    // Solo puede existir UNA factura NO anulada por OT.
                    // Si la única factura de la OT está anulada, se permite
                    // generar una nueva (re-emisión).
                    $tieneVigente = FacturaEloquentModel::query()
                        ->where('orden_trabajo_id', $value)
                        ->where('estado', '!=', FacturaEstado::Anulada->value)
                        ->exists();

                    if ($tieneVigente) {
                        $fail('La orden de trabajo ya tiene una factura vigente. Anula la factura actual antes de generar otra.');
                    }
                },
            ],
            'serie' => 'nullable|string|max:20',
            'fecha_emision' => 'required|date',
            'descuento' => 'nullable|numeric|min:0',
            'estado' => 'nullable|string|in:borrador,emitida,pagada,anulada',
            'observaciones' => 'nullable|string',
            'cliente_tipo_documento' => 'required|string|in:CEDULA,DNI,RUC,CE,PASAPORTE',
            'cliente_numero_documento' => [
                'required',
                'string',
                'max:20',
                FieldValidation::documentoPorTipo('cliente_tipo_documento'),
            ],
            'cliente_nombres' => array_merge(FieldValidation::nombre(true), [
                function (string $attribute, mixed $value, Closure $fail): void {
                    $parts = preg_split('/\s+/', trim((string) $value)) ?: [];
                    if (count(array_filter($parts)) < 1) {
                        $fail('Ingresa al menos un nombre del cliente.');
                    }
                },
            ]),
            'cliente_apellidos' => array_merge(FieldValidation::nombre(true), [
                function (string $attribute, mixed $value, Closure $fail): void {
                    $parts = preg_split('/\s+/', trim((string) $value)) ?: [];
                    if (count(array_filter($parts)) < 2) {
                        $fail('Ingresa los dos apellidos del cliente.');
                    }
                },
            ]),
            'cliente_direccion' => 'required|string|min:5|max:255',
            'cliente_telefono' => FieldValidation::telefono(true),
            'cliente_email' => FieldValidation::email(true),
            'actualizar_cliente' => 'nullable|boolean',
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
            'cliente_tipo_documento' => 'tipo de documento',
            'cliente_numero_documento' => 'número de documento',
            'cliente_nombres' => 'nombres',
            'cliente_apellidos' => 'apellidos',
            'cliente_direccion' => 'dirección',
            'cliente_telefono' => 'teléfono',
            'cliente_email' => 'correo',
        ];
    }

    public function messages(): array
    {
        return FieldValidation::messages();
    }
}

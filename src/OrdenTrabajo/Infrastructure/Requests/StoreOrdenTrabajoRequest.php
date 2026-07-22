<?php

namespace Src\OrdenTrabajo\Infrastructure\Requests;

use App\Enums\OrdenEstado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrdenTrabajoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cliente_id' => $this->clienteId ?? $this->cliente_id,
            'vehiculo_id' => $this->vehiculoId ?? $this->vehiculo_id,
            'mecanico_id' => $this->mecanicoId ?? $this->mecanico_id,
            'tipo_falla' => $this->tipoFalla ?? $this->tipo_falla,
            'falla_reportada' => $this->fallaReportada ?? $this->falla_reportada,
            'kilometraje_ingreso' => $this->kilometrajeIngreso ?? $this->kilometraje_ingreso ?? 0,
            'diagnostico_tecnico' => $this->diagnosticoTecnico ?? $this->diagnostico_tecnico,
            'created_by' => $this->user()?->id,
        ]);
    }

    public function rules(): array
    {
        return [
            'cliente_id' => 'required|uuid|exists:clientes,id',
            'vehiculo_id' => 'required|uuid|exists:vehiculos,id',
            'mecanico_id' => 'nullable|uuid|exists:mecanicos,id',
            'estado' => ['sometimes', 'string', Rule::in(OrdenEstado::values())],
            'tipo_falla' => 'nullable|string|max:255',
            'falla_reportada' => 'required|string',
            'kilometraje_ingreso' => 'sometimes|integer|min:0',
            'observaciones' => 'nullable|string',
            'diagnostico_tecnico' => 'nullable|string',
            'prioridad' => 'nullable|string|in:baja,media,alta',
            'created_by' => 'nullable|uuid|exists:users,id',
            'servicios' => 'sometimes|array',
            'servicios.*.servicioId' => 'required_with:servicios|uuid|exists:servicios,id',
            'servicios.*.precio' => 'required_with:servicios|numeric|min:0',
            'repuestos' => 'sometimes|array',
            'repuestos.*.productoId' => 'required_with:repuestos|uuid|exists:productos,id',
            'repuestos.*.cantidad' => 'required_with:repuestos|integer|min:1',
            'repuestos.*.precioUnitario' => 'required_with:repuestos|numeric|min:0',
        ];
    }
}

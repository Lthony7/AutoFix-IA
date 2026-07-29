<?php

namespace Src\Cita\Infrastructure\Requests;

use App\Enums\CitaTipo;
use App\Enums\PresupuestoEstado;
use App\Services\DisponibilidadCitasService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Src\Cliente\Infrastructure\Models\ClienteEloquentModel;
use Src\Presupuesto\Infrastructure\Models\PresupuestoEloquentModel;
use Src\Vehiculo\Infrastructure\Models\VehiculoEloquentModel;

class AgendarCitaClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $fechaHora = $this->fechaHora ?? $this->fecha_hora;
        if (is_string($fechaHora) && str_contains($fechaHora, 'T')) {
            $fechaHora = str_replace('T', ' ', $fechaHora);
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $fechaHora)) {
                $fechaHora .= ':00';
            }
        }

        $presupuestoId = $this->presupuestoId ?? $this->presupuesto_id;
        if ($presupuestoId === '' || $presupuestoId === null) {
            $presupuestoId = null;
        }

        $this->merge([
            'vehiculo_id' => $this->vehiculoId ?? $this->vehiculo_id,
            'fecha_hora' => $fechaHora,
            'tipo' => $this->tipo ?? 'mantenimiento',
            'notas' => $this->notas,
            'presupuesto_id' => $presupuestoId,
            'servicios' => $this->servicios ?? null,
            'repuestos' => $this->repuestos ?? null,
            'ajustar_presupuesto' => filter_var(
                $this->ajustarPresupuesto ?? $this->ajustar_presupuesto ?? false,
                FILTER_VALIDATE_BOOLEAN
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'vehiculo_id' => 'required|uuid|exists:vehiculos,id',
            'fecha_hora' => 'required|date|after:now',
            'tipo' => ['required', 'string', Rule::in(CitaTipo::values())],
            'notas' => 'nullable|string|max:2000',
            'presupuesto_id' => 'nullable|uuid|exists:presupuestos,id',
            'ajustar_presupuesto' => 'nullable|boolean',
            'servicios' => 'nullable|array',
            'servicios.*.servicioId' => 'required_with:servicios|uuid|exists:servicios,id',
            'servicios.*.cantidad' => 'nullable|integer|min:1|max:20',
            'repuestos' => 'nullable|array',
            'repuestos.*.productoId' => 'required_with:repuestos|uuid|exists:productos,id',
            'repuestos.*.cantidad' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $clienteIds = ClienteEloquentModel::where('user_id', $this->user()?->id)->pluck('id')->all();
            $vehiculoId = $this->input('vehiculo_id');
            $vehiculo = $vehiculoId
                ? VehiculoEloquentModel::whereIn('cliente_id', $clienteIds)->find($vehiculoId)
                : null;

            if (!$vehiculo) {
                $v->errors()->add('vehiculo_id', 'El vehículo no pertenece a tu cuenta.');
            }

            $fechaHora = $this->input('fecha_hora');
            if ($fechaHora && !app(DisponibilidadCitasService::class)->esSlotDisponible((string) $fechaHora)) {
                $v->errors()->add('fecha_hora', 'Ese horario ya no está disponible. Elige otro cupo.');
            }

            $presupuestoId = $this->input('presupuesto_id');
            if ($presupuestoId) {
                $presupuesto = PresupuestoEloquentModel::whereIn('cliente_id', $clienteIds)->find($presupuestoId);
                if (!$presupuesto || !$presupuesto->esUsableEnCita()) {
                    $v->errors()->add('presupuesto_id', 'Ese presupuesto no está disponible para agendar.');
                } elseif ($presupuesto->estado === PresupuestoEstado::Vencido || $presupuesto->estaVencido()) {
                    $v->errors()->add('presupuesto_id', 'El presupuesto está vencido.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'vehiculo_id.required' => 'Selecciona un vehículo.',
            'fecha_hora.required' => 'Selecciona un horario disponible.',
            'fecha_hora.after' => 'La cita debe ser en un horario futuro.',
        ];
    }
}

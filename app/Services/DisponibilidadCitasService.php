<?php

namespace App\Services;

use App\Enums\CitaEstado;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Src\Cita\Infrastructure\Models\CitaEloquentModel;

class DisponibilidadCitasService
{
    /**
     * @return list<array{fecha: string, hora: string, fechaHora: string, label: string}>
     */
    public function slotsParaFecha(CarbonInterface|string $fecha, ?string $excluirCitaId = null): array
    {
        $cfg = config('autofix.agenda', []);
        $dia = Carbon::parse($fecha)->startOfDay();
        $diasHabiles = $cfg['dias_habiles'] ?? [1, 2, 3, 4, 5, 6];

        if (!in_array((int) $dia->dayOfWeekIso, $diasHabiles, true)) {
            return [];
        }

        $inicio = Carbon::parse($dia->toDateString() . ' ' . ($cfg['hora_inicio'] ?? '08:00'));
        $fin = Carbon::parse($dia->toDateString() . ' ' . ($cfg['hora_fin'] ?? '17:00'));
        $duracion = (int) ($cfg['duracion_slot_minutos'] ?? 60);
        $maxPorSlot = max(1, (int) ($cfg['max_citas_por_slot'] ?? 3));
        $minHoras = (int) ($cfg['antelacion_minima_horas'] ?? 12);
        $limiteMinimo = now()->addHours($minHoras);

        $ocupados = CitaEloquentModel::query()
            ->whereDate('fecha_hora', $dia->toDateString())
            ->whereIn('estado', [CitaEstado::Programada->value, CitaEstado::Reagendada->value])
            ->when($excluirCitaId, fn ($q) => $q->where('id', '!=', $excluirCitaId))
            ->get(['fecha_hora'])
            ->groupBy(fn ($c) => $c->fecha_hora?->format('Y-m-d H:i'))
            ->map->count();

        $slots = [];
        for ($cursor = $inicio->copy(); $cursor->lt($fin); $cursor->addMinutes($duracion)) {
            if ($cursor->lte($limiteMinimo)) {
                continue;
            }

            $key = $cursor->format('Y-m-d H:i');
            $usados = (int) ($ocupados[$key] ?? 0);
            if ($usados >= $maxPorSlot) {
                continue;
            }

            $slots[] = [
                'fecha' => $cursor->toDateString(),
                'hora' => $cursor->format('H:i'),
                'fechaHora' => $cursor->format('Y-m-d\TH:i'),
                'label' => $cursor->format('H:i') . ' · ' . ($maxPorSlot - $usados) . ' cupo(s)',
            ];
        }

        return $slots;
    }

    public function esSlotDisponible(string $fechaHora, ?string $excluirCitaId = null): bool
    {
        $dt = Carbon::parse(str_replace('T', ' ', $fechaHora));
        $slots = $this->slotsParaFecha($dt->toDateString(), $excluirCitaId);

        $needle = $dt->format('Y-m-d\TH:i');

        return collect($slots)->contains(fn ($s) => $s['fechaHora'] === $needle);
    }

    /**
     * @return list<string>
     */
    public function fechasHabilesProximas(): array
    {
        $cfg = config('autofix.agenda', []);
        $dias = (int) ($cfg['dias_adelante'] ?? 30);
        $diasHabiles = $cfg['dias_habiles'] ?? [1, 2, 3, 4, 5, 6];
        $out = [];

        for ($i = 0; $i <= $dias; $i++) {
            $d = now()->startOfDay()->addDays($i);
            if (in_array((int) $d->dayOfWeekIso, $diasHabiles, true)) {
                $out[] = $d->toDateString();
            }
        }

        return $out;
    }
}

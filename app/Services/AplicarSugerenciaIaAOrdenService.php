<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenServicioEloquentModel;
use Src\OrdenTrabajo\Infrastructure\Models\OrdenTrabajoEloquentModel;
use Src\Producto\Infrastructure\Models\ProductoEloquentModel;
use Src\Servicio\Infrastructure\Models\ServicioEloquentModel;

class AplicarSugerenciaIaAOrdenService
{
    /**
     * Aplica mecánico, servicios y repuestos sugeridos por IA a la OT (si aún no tiene ítems).
     *
     * @param  array<string, mixed>  $resultado
     * @return array{mecanico: bool, servicios: int, repuestos: int}
     */
    public function aplicar(OrdenTrabajoEloquentModel $orden, array $resultado, OrdenRepuestoStockService $stockService): array
    {
        $applied = ['mecanico' => false, 'servicios' => 0, 'repuestos' => 0];

        $mecanicos = $resultado['mecanicos_sugeridos'] ?? [];
        if (empty($orden->mecanico_id) && is_array($mecanicos) && $mecanicos !== []) {
            $primerId = $mecanicos[0]['id'] ?? null;
            if (is_string($primerId) && $primerId !== '') {
                $orden->update(['mecanico_id' => $primerId]);
                $orden->load('cita');
                if ($orden->cita) {
                    $orden->cita->update(['mecanico_id' => $primerId]);
                }
                $applied['mecanico'] = true;
            }
        }

        $orden->load(['ordenServicios', 'ordenRepuestos']);

        if ($orden->ordenServicios->isEmpty()) {
            $servicios = $this->resolverServicios(
                $resultado['servicios_sugeridos'] ?? [],
                $resultado['servicio_recomendado'] ?? null
            );

            // En cada sesión, el diagnóstico computarizado va primero
            $diagnostico = ServicioEloquentModel::query()
                ->where('activo', true)
                ->where(function ($q) {
                    $q->whereRaw('LOWER(nombre) LIKE ?', ['%diagnóstico computarizado%'])
                        ->orWhereRaw('LOWER(nombre) LIKE ?', ['%diagnostico computarizado%']);
                })
                ->first();

            if ($diagnostico) {
                $servicios = collect($servicios)
                    ->reject(fn ($s) => $s->id === $diagnostico->id)
                    ->prepend($diagnostico)
                    ->unique('id')
                    ->take(4)
                    ->values()
                    ->all();
            }

            foreach ($servicios as $servicio) {
                OrdenServicioEloquentModel::create([
                    'orden_trabajo_id' => $orden->id,
                    'servicio_id' => $servicio->id,
                    'precio' => (float) $servicio->precio_base,
                ]);
                $applied['servicios']++;
            }
        }

        if ($orden->ordenRepuestos->isEmpty()) {
            $sugeridos = is_array($resultado['repuestos_sugeridos'] ?? null)
                ? $resultado['repuestos_sugeridos']
                : [];
            $repuestos = $this->resolverRepuestos($sugeridos);
            $payload = $repuestos->map(fn (ProductoEloquentModel $p) => [
                'producto_id' => $p->id,
                'cantidad' => 1,
                'precio_unitario' => (float) $p->precio,
            ])->all();

            if ($payload !== []) {
                $stockService->aplicarNuevos($orden->fresh(), $payload);
                $applied['repuestos'] = count($payload);
            }

            // Si la IA sugiere algo que no está en inventario → observación: cliente aporta
            $encontradosNombres = $repuestos->map(fn ($p) => mb_strtolower($p->nombre))->all();
            $faltantes = [];
            foreach ($sugeridos as $nombre) {
                if (!is_string($nombre) || trim($nombre) === '') {
                    continue;
                }
                $needle = mb_strtolower(trim($nombre));
                $yaEsta = collect($encontradosNombres)->contains(
                    fn ($n) => $n === $needle || str_contains($n, $needle) || str_contains($needle, $n)
                );
                if (!$yaEsta) {
                    $faltantes[] = trim($nombre);
                }
            }

            if ($faltantes !== []) {
                $bloque = 'Repuestos que aporta el cliente: ' . implode('; ', array_unique($faltantes)) . '.';
                $obs = trim((string) ($orden->observaciones ?? ''));
                $obs = preg_replace('/\n?Repuestos que aporta el cliente:.*$/su', '', $obs) ?? $obs;
                $obs = trim($obs);
                $orden->update([
                    'observaciones' => $obs !== '' ? $obs . "\n" . $bloque : $bloque,
                ]);
            }
        }

        return $applied;
    }

    /**
     * @param  list<string>|mixed  $sugeridos
     * @return list<ServicioEloquentModel>
     */
    private function resolverServicios(mixed $sugeridos, ?string $servicioRecomendado): array
    {
        $terminos = [];
        if (is_array($sugeridos)) {
            foreach ($sugeridos as $item) {
                if (is_string($item) && trim($item) !== '') {
                    $terminos[] = trim($item);
                }
            }
        }
        if ($servicioRecomendado) {
            array_unshift($terminos, $servicioRecomendado);
        }

        $encontrados = [];
        $ids = [];

        foreach ($terminos as $termino) {
            $match = $this->buscarServicio($termino);
            if ($match && !isset($ids[$match->id])) {
                $ids[$match->id] = true;
                $encontrados[] = $match;
            }
            if (count($encontrados) >= 4) {
                break;
            }
        }

        return $encontrados;
    }

    /**
     * @param  list<string>|mixed  $sugeridos
     * @return Collection<int, ProductoEloquentModel>
     */
    private function resolverRepuestos(mixed $sugeridos): Collection
    {
        $terminos = [];
        if (is_array($sugeridos)) {
            foreach ($sugeridos as $item) {
                if (is_string($item) && trim($item) !== '') {
                    $terminos[] = trim($item);
                }
            }
        }

        $encontrados = collect();
        $ids = [];

        foreach ($terminos as $termino) {
            $match = $this->buscarRepuesto($termino);
            if ($match && !isset($ids[$match->id]) && $match->stock > 0) {
                $ids[$match->id] = true;
                $encontrados->push($match);
            }
            if ($encontrados->count() >= 3) {
                break;
            }
        }

        return $encontrados;
    }

    private function buscarServicio(string $termino): ?ServicioEloquentModel
    {
        $needle = mb_strtolower($termino);
        $servicios = ServicioEloquentModel::where('activo', true)->get();

        $exacto = $servicios->first(fn ($s) => mb_strtolower($s->nombre) === $needle);
        if ($exacto) {
            return $exacto;
        }

        $parcial = $servicios
            ->map(fn ($s) => ['s' => $s, 'score' => $this->score(mb_strtolower($s->nombre), $needle)])
            ->filter(fn ($row) => $row['score'] >= 0.35)
            ->sortByDesc('score')
            ->first();

        return $parcial['s'] ?? null;
    }

    private function buscarRepuesto(string $termino): ?ProductoEloquentModel
    {
        $needle = mb_strtolower($termino);
        $productos = ProductoEloquentModel::query()
            ->where('activo', true)
            ->where('tipo_producto', 'repuesto')
            ->get();

        $exacto = $productos->first(fn ($p) => mb_strtolower($p->nombre) === $needle
            || mb_strtolower((string) $p->codigo) === $needle);
        if ($exacto) {
            return $exacto;
        }

        $parcial = $productos
            ->map(fn ($p) => ['p' => $p, 'score' => max(
                $this->score(mb_strtolower($p->nombre), $needle),
                $this->score(mb_strtolower((string) $p->categoria), $needle) * 0.6
            )])
            ->filter(fn ($row) => $row['score'] >= 0.3)
            ->sortByDesc('score')
            ->first();

        return $parcial['p'] ?? null;
    }

    private function score(string $haystack, string $needle): float
    {
        if ($haystack === '' || $needle === '') {
            return 0.0;
        }
        if (str_contains($haystack, $needle) || str_contains($needle, $haystack)) {
            return 0.9;
        }

        $hWords = preg_split('/\s+/', $haystack) ?: [];
        $nWords = preg_split('/\s+/', $needle) ?: [];
        $hits = 0;
        foreach ($nWords as $w) {
            if (mb_strlen($w) < 3) {
                continue;
            }
            foreach ($hWords as $hw) {
                if (str_contains($hw, $w) || str_contains($w, $hw)) {
                    $hits++;
                    break;
                }
            }
        }

        $denom = max(1, count(array_filter($nWords, fn ($w) => mb_strlen($w) >= 3)));

        return $hits / $denom;
    }
}

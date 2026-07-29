<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Src\Mecanico\Infrastructure\Models\MecanicoEloquentModel;

class GroqDiagnosticService
{
    public function analyze(array $payload): array
    {
        $resultado = $this->shouldUseMock()
            ? $this->mockAnalyze($payload)
            : $this->callGroq($payload);

        $especialidad = $resultado['especialidad_recomendada'] ?? 'Mantenimiento general';
        $resultado['mecanicos_sugeridos'] = $this->sugerirMecanicos($especialidad);

        return $resultado;
    }

    private function callGroq(array $payload): array
    {
        try {
            $request = Http::withToken(config('services.groq.key'))->timeout(45);

            if (!config('services.groq.ssl_verify', true)) {
                $request = $request->withoutVerifying();
            }

            $system = <<<'PROMPT'
Eres un asistente de taller automotriz AUTOFIX IA. NO emitas un diagnóstico definitivo.
Analiza vehículo, tipo de falla, prioridad y reporte del cliente. Responde SOLO en JSON con:
- diagnostico_detalle (string): explicación clara del problema probable y por qué.
- posibles_causas (array de strings): causas de más a menos probable.
- acciones_recomendadas (array de strings): pasos concretos del taller.
- especialidad_recomendada (string): especialidad del mecánico, ej:
  "Sistema eléctrico y baterías", "Motor", "Frenos", "Suspensión y dirección",
  "Inyección electrónica y sensores", "Transmisión", "Diagnóstico computarizado (scanner OBD)",
  "Aire acondicionado y clima", "Embrague y caja de cambios", "Mantenimiento general y lubricación".
- servicio_recomendado (string): preferir un nombre exacto de catalogo_servicios si viene en el payload.
- servicios_sugeridos (array de strings): 2–4 nombres; el PRIMERO debe ser "Diagnóstico computarizado" cuando exista en el catálogo; luego servicios del caso (ej. "Limpieza de inyectores", "Cambio de filtro de aire", "Limpieza de cuerpo de aceleración"). Preferir coincidencias exactas de catalogo_servicios.
- repuestos_sugeridos (array de strings): 1–4 nombres; preferir coincidencias exactas de catalogo_repuestos.
- prioridad (baja|media|alta): respeta urgencia del cliente salvo riesgo de seguridad (entonces alta).
- observacion_mecanico (string): observaciones prácticas para el mecánico asignado.
- advertencia (string): riesgos o precauciones.
- respuesta_completa (string): resumen legible para el taller.

Si no enciende: prioriza eléctrico/arranque. Usa el catálogo cuando exista; si no hay match, sugiere nombres reales de taller.
PROMPT;

            $response = $request->post(rtrim(config('services.groq.url'), '/') . '/chat/completions', [
                'model' => config('services.groq.model'),
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => json_encode($payload, JSON_UNESCAPED_UNICODE)],
                ],
                'temperature' => 0.3,
                'response_format' => ['type' => 'json_object'],
            ]);

            if (!$response->successful()) {
                Log::warning('Groq API falló, usando mock', ['status' => $response->status()]);

                return $this->mockAnalyze($payload);
            }

            $content = $response->json('choices.0.message.content');
            $parsed = json_decode($content, true);

            if (!is_array($parsed)) {
                return $this->mockAnalyze($payload);
            }

            return [
                'diagnostico_detalle' => $parsed['diagnostico_detalle'] ?? null,
                'posibles_causas' => $parsed['posibles_causas'] ?? [],
                'acciones_recomendadas' => $parsed['acciones_recomendadas'] ?? [],
                'especialidad_recomendada' => $parsed['especialidad_recomendada'] ?? 'Mantenimiento general y lubricación',
                'servicio_recomendado' => $parsed['servicio_recomendado'] ?? null,
                'servicios_sugeridos' => $parsed['servicios_sugeridos'] ?? [],
                'repuestos_sugeridos' => $parsed['repuestos_sugeridos'] ?? [],
                'prioridad' => $parsed['prioridad'] ?? 'media',
                'observacion_mecanico' => $parsed['observacion_mecanico'] ?? null,
                'advertencia' => $parsed['advertencia'] ?? null,
                'respuesta_completa' => $parsed['respuesta_completa'] ?? $content,
                'es_simulado' => false,
            ];
        } catch (\Throwable $e) {
            Log::warning('Groq API excepción, usando mock', ['error' => $e->getMessage()]);

            return $this->mockAnalyze($payload);
        }
    }

    private function shouldUseMock(): bool
    {
        if (config('services.groq.mock', true)) {
            return true;
        }

        return empty(config('services.groq.key'));
    }

    private function mockAnalyze(array $payload): array
    {
        $texto = mb_strtolower(implode(' ', array_filter([
            $payload['tipo_falla'] ?? '',
            $payload['descripcion'] ?? '',
            $payload['falla_reportada'] ?? '',
            $payload['momento'] ?? '',
            $payload['ruidos'] ?? '',
            $payload['luces_tablero'] ?? '',
        ])));

        $urgencia = strtolower((string) ($payload['urgencia'] ?? 'media'));
        $noArranca = $this->containsAny($texto, [
            'no enciende', 'no arranca', 'no prende', 'no parte', 'no da marcha',
            'sin arranque', 'starter', 'arrancador', 'no prende el motor',
        ]);

        if ($noArranca || $this->containsAny($texto, ['bater', 'alternador', 'fusible', 'eléctrico', 'electrico', 'corto'])) {
            $rules = [
                'diagnostico_detalle' => 'El síntoma sugiere una falla en el sistema eléctrico de arranque o alimentación. '
                    . 'Cuando el vehículo no enciende, lo habitual es revisar batería, bornes, arrancador, relé de arranque y carga del alternador antes de abrir motor.',
                'posibles_causas' => [
                    'Batería descargada, sulfatada o con bajo amperaje de arranque',
                    'Bornes o cables de batería oxidados o flojos',
                    'Motor de arranque (marcha) defectuoso o relé de arranque fallando',
                    'Alternador que no carga y deja la batería sin energía',
                    'Fusible/relé principal o falla en interruptor de encendido',
                ],
                'acciones_recomendadas' => [
                    'Medir voltaje de batería en reposo (ideal ~12.4–12.7 V)',
                    'Probar arranque con carga y revisar caída de voltaje en bornes',
                    'Inspeccionar cables a masa y positivo del arrancador',
                    'Verificar si el alternador carga con motor en marcha (si logra encender)',
                    'Asignar a especialista en sistema eléctrico / electromecánico',
                ],
                'especialidad_recomendada' => 'Sistema eléctrico y baterías',
                'servicio_recomendado' => 'Sistema eléctrico',
                'servicios_sugeridos' => ['Diagnóstico computarizado', 'Sistema eléctrico', 'Carga / prueba de batería'],
                'repuestos_sugeridos' => ['Batería 12V 60Ah', 'Relé de arranque', 'Kit de fusibles surtido'],
                'prioridad' => $noArranca ? 'alta' : ($urgencia === 'alta' ? 'alta' : 'media'),
                'observacion_mecanico' => 'Confirmar si hay click al girar llave, luces débiles o tablero muerto. Eso orienta a batería vs arrancador.',
                'advertencia' => 'No forzar arranques prolongados: puede dañar el motor de arranque y descargar más la batería.',
            ];
        } elseif ($this->containsAny($texto, ['freno', 'chirri', 'vibra al frenar', 'pedal duro', 'abs'])) {
            $rules = [
                'diagnostico_detalle' => 'El reporte apunta a un problema del sistema de frenos. Se recomienda inspección de pastillas, discos, líquido y, si aplica, sensores ABS.',
                'posibles_causas' => [
                    'Pastillas de freno desgastadas',
                    'Discos deformados o rayados',
                    'Líquido de frenos bajo o contaminado',
                    'Cilindros o mordazas con fuga/falla',
                    'Sensor o módulo ABS con falla (si hay luz en tablero)',
                ],
                'acciones_recomendadas' => [
                    'Medir espesor de pastillas y estado de discos',
                    'Revisar nivel y estado del líquido DOT',
                    'Inspeccionar fugas en flexibles y calipers',
                    'Probar frenado a baja velocidad en zona segura',
                ],
                'especialidad_recomendada' => 'Frenos (discos, pastillas, ABS y freno de mano)',
                'servicio_recomendado' => 'Cambio de pastillas de freno',
                'servicios_sugeridos' => ['Diagnóstico computarizado', 'Cambio de pastillas de freno', 'Revisión de frenos'],
                'repuestos_sugeridos' => ['Pastillas de freno delanteras', 'Líquido de frenos DOT 4', 'Limpiador de frenos spray'],
                'prioridad' => 'alta',
                'observacion_mecanico' => 'Priorizar seguridad: no entregar el vehículo si hay pedal esponjoso o pérdida de frenado.',
                'advertencia' => 'No circular si hay pérdida total de frenado o pedal esponjoso.',
            ];
        } elseif ($this->containsAny($texto, ['suspens', 'amortigu', 'golpeteo', 'dirección', 'rueda'])) {
            $rules = [
                'diagnostico_detalle' => 'Los síntomas coinciden con desgaste o falla en suspensión/dirección. Conviene revisar amortiguadores, rótulas, bujes y alineación.',
                'posibles_causas' => [
                    'Amortiguadores desgastados',
                    'Bujes o rótulas con holgura',
                    'Resortes fatigados',
                    'Alineación fuera de especificación',
                ],
                'acciones_recomendadas' => [
                    'Inspección en elevador de suspensión delantera/trasera',
                    'Verificar holguras en rótulas y terminales',
                    'Revisar fugas en amortiguadores',
                    'Evaluar necesidad de alineación y balanceo',
                ],
                'especialidad_recomendada' => 'Suspensión y dirección (amortiguadores, rótulas, cremallera)',
                'servicio_recomendado' => 'Suspensión y dirección',
                'servicios_sugeridos' => ['Diagnóstico computarizado', 'Suspensión y dirección', 'Alineación y balanceo'],
                'repuestos_sugeridos' => ['Amortiguador delantero', 'Rótula superior', 'Pesas de balanceo surtido'],
                'prioridad' => $urgencia === 'alta' ? 'alta' : 'media',
                'observacion_mecanico' => 'Preguntar si el ruido aparece en baches, al girar o a cierta velocidad.',
                'advertencia' => 'Conducir con precaución si hay vibraciones o ruidos metálicos al girar.',
            ];
        } elseif ($this->containsAny($texto, ['inyecc', 'sensor', 'check engine', 'falla electr', 'scanner', 'obd'])) {
            $rules = [
                'diagnostico_detalle' => 'El caso sugiere falla electrónica o de inyección. Se recomienda lectura de códigos OBD y revisión de sensores/inyectores.',
                'posibles_causas' => [
                    'Sensor MAF/MAP o oxígeno defectuoso',
                    'Inyectores sucios o fallando',
                    'Bujías/cables de encendido en mal estado',
                    'Falla intermitente de cableado a ECU',
                ],
                'acciones_recomendadas' => [
                    'Lectura de códigos con scanner OBD',
                    'Revisar parámetros en vivo (combustible, sensores)',
                    'Inspeccionar conectores y masas',
                    'Confirmar si la luz check engine está activa',
                ],
                'especialidad_recomendada' => 'Inyección electrónica y sensores',
                'servicio_recomendado' => 'Inyección electrónica',
                'servicios_sugeridos' => ['Diagnóstico computarizado', 'Limpieza de inyectores', 'Limpieza de cuerpo de aceleración', 'Cambio de filtro de aire'],
                'repuestos_sugeridos' => ['Sensor de oxígeno O2', 'Limpiador de inyectores', 'Bujías iridio (juego x4)'],
                'prioridad' => $urgencia === 'alta' ? 'alta' : 'media',
                'observacion_mecanico' => 'Guardar captura de códigos antes de borrarlos.',
                'advertencia' => 'Si el motor falla en marcha, evitar autopista hasta confirmar causa.',
            ];
        } elseif ($this->containsAny($texto, ['motor', 'sobrecalent', 'humo', 'aceite', 'perdida de potencia'])) {
            $rules = [
                'diagnostico_detalle' => 'El reporte indica posible falla de motor o sistemas asociados (lubricación, refrigeración o encendido).',
                'posibles_causas' => [
                    'Falla en sistema de encendido',
                    'Sensor o falla de inyección asociada a motor',
                    'Problemas de compresión',
                    'Sobrecalentamiento por termostato/bomba/radiador',
                ],
                'acciones_recomendadas' => [
                    'Lectura OBD y revisión de bujías/filtros',
                    'Verificar nivel de aceite y refrigerante',
                    'Inspeccionar fugas y temperatura de operación',
                    'Prueba de compresión si aplica',
                ],
                'especialidad_recomendada' => 'Motor (reparación, sincronización y sobrecalentamiento)',
                'servicio_recomendado' => 'Revisión de motor',
                'servicios_sugeridos' => ['Diagnóstico computarizado', 'Revisión de motor', 'Cambio de aceite', 'Cambio de filtro de aceite'],
                'repuestos_sugeridos' => ['Aceite 5W-30 sintético 4L', 'Filtro de aceite estándar', 'Bujías iridio (juego x4)'],
                'prioridad' => $urgencia === 'alta' ? 'alta' : 'media',
                'observacion_mecanico' => 'No operar el motor si hay sobrecalentamiento o ruido metálico fuerte.',
                'advertencia' => 'Detener el vehículo si la temperatura sube a zona roja.',
            ];
        } elseif ($this->containsAny($texto, ['transm', 'caja', 'embrague', 'cambios'])) {
            $rules = [
                'diagnostico_detalle' => 'Los síntomas apuntan a transmisión o embrague. Requiere prueba de cambios y revisión de niveles/holguras.',
                'posibles_causas' => [
                    'Embrague desgastado o deslizando',
                    'Nivel/estado de aceite de transmisión inadecuado',
                    'Sincronizadores o solenoides con falla',
                    'Soportes de motor/caja deteriorados',
                ],
                'acciones_recomendadas' => [
                    'Prueba de embargue y cambios en frío/caliente',
                    'Revisar nivel y olor del aceite de caja',
                    'Inspeccionar fugas y soportes',
                    'Asignar a especialista en transmisión/embrague',
                ],
                'especialidad_recomendada' => 'Transmisión manual y automática',
                'servicio_recomendado' => 'Transmisión',
                'servicios_sugeridos' => ['Diagnóstico computarizado', 'Transmisión', 'Embrague'],
                'repuestos_sugeridos' => ['Kit de embrague', 'Aceite transmisión ATF 1L', 'Collarín de embrague'],
                'prioridad' => $urgencia === 'alta' ? 'alta' : 'media',
                'observacion_mecanico' => 'Documentar si hay patinaje, ruidos al cambiar o tirones.',
                'advertencia' => 'Evitar forzar cambios si hay bloqueo o ruidos graves.',
            ];
        } else {
            $rules = [
                'diagnostico_detalle' => 'Con la información disponible se sugiere un diagnóstico general orientado por la descripción del cliente. '
                    . 'El mecánico debe confirmar en taller con inspección y, si aplica, scanner.',
                'posibles_causas' => [
                    'Requiere inspección visual en taller',
                    'Posible falla intermitente no identificada',
                    'Componente desgastado por uso',
                ],
                'acciones_recomendadas' => [
                    'Entrevista detallada al cliente sobre cuándo ocurre la falla',
                    'Inspección visual general y prueba de ruta si es seguro',
                    'Lectura OBD si hay luces de tablero',
                    'Asignar según hallazgos al especialista correspondiente',
                ],
                'especialidad_recomendada' => 'Diagnóstico computarizado (scanner OBD)',
                'servicio_recomendado' => 'Diagnóstico computarizado',
                'servicios_sugeridos' => ['Diagnóstico computarizado', 'Mantenimiento preventivo', 'Cambio de filtro de aire'],
                'repuestos_sugeridos' => ['Filtro de aire motor', 'Filtro de aceite estándar'],
                'prioridad' => in_array($urgencia, ['baja', 'media', 'alta'], true) ? $urgencia : 'media',
                'observacion_mecanico' => 'Completar síntomas (ruidos, luces, momento exacto) antes de desarmar.',
                'advertencia' => 'Esta sugerencia es orientativa y requiere confirmación del mecánico.',
            ];
        }

        $descripcion = $payload['descripcion'] ?? $payload['falla_reportada'] ?? '';
        $respuesta = "Diagnóstico IA (simulado)\n"
            . "Detalle: {$rules['diagnostico_detalle']}\n"
            . "Especialista sugerido: {$rules['especialidad_recomendada']}\n"
            . 'Posibles causas: ' . implode('; ', $rules['posibles_causas']) . "\n"
            . 'Qué hacer: ' . implode('; ', $rules['acciones_recomendadas']) . "\n"
            . "Descripción del cliente: {$descripcion}";

        return array_merge($rules, [
            'respuesta_completa' => $respuesta,
            'es_simulado' => true,
        ]);
    }

    /** @param list<string> $needles */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Busca mecánicos activos cuya especialidad coincida con la recomendada.
     *
     * @return list<array{id: string, nombre: string, especialidad: string, telefono: ?string}>
     */
    private function sugerirMecanicos(string $especialidad): array
    {
        $keywords = $this->keywordsEspecialidad($especialidad);

        $query = MecanicoEloquentModel::query()
            ->where('activo', true)
            ->orderBy('nombres');

        if ($keywords !== []) {
            $query->where(function ($q) use ($keywords) {
                foreach ($keywords as $kw) {
                    $q->orWhereRaw('LOWER(especialidad) LIKE ?', ['%' . mb_strtolower($kw) . '%']);
                }
            });
        }

        $mecanicos = $query->limit(3)->get();

        if ($mecanicos->isEmpty()) {
            $mecanicos = MecanicoEloquentModel::query()
                ->where('activo', true)
                ->whereRaw('LOWER(especialidad) LIKE ?', ['%mantenimiento%'])
                ->orderBy('nombres')
                ->limit(2)
                ->get();
        }

        return $mecanicos->map(fn (MecanicoEloquentModel $m) => [
            'id' => $m->id,
            'nombre' => trim($m->nombres . ' ' . $m->apellidos),
            'especialidad' => $m->especialidad,
            'telefono' => $m->telefono,
        ])->values()->all();
    }

    /** @return list<string> */
    private function keywordsEspecialidad(string $especialidad): array
    {
        $e = mb_strtolower($especialidad);

        return match (true) {
            str_contains($e, 'eléct') || str_contains($e, 'elect') || str_contains($e, 'bater') => ['eléct', 'elect', 'bater', 'arranque'],
            str_contains($e, 'freno') => ['freno', 'abs'],
            str_contains($e, 'suspens') || str_contains($e, 'direcc') => ['suspens', 'direcc', 'amortigu'],
            str_contains($e, 'inyecc') || str_contains($e, 'sensor') => ['inyecc', 'sensor'],
            str_contains($e, 'motor') => ['motor'],
            str_contains($e, 'transm') => ['transm'],
            str_contains($e, 'embrague') || str_contains($e, 'caja') => ['embrague', 'caja'],
            str_contains($e, 'diagnóst') || str_contains($e, 'obd') || str_contains($e, 'scanner') => ['diagnóst', 'obd', 'scanner'],
            str_contains($e, 'aire') || str_contains($e, 'clima') => ['aire', 'clima'],
            str_contains($e, 'aline') || str_contains($e, 'balance') => ['aline', 'balance', 'llanta'],
            default => array_values(array_filter(preg_split('/\s+/', $e) ?: [], fn ($w) => mb_strlen($w) >= 5)),
        };
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqDiagnosticService
{
    public function analyze(array $payload): array
    {
        if ($this->shouldUseMock()) {
            return $this->mockAnalyze($payload);
        }

        try {
            $request = Http::withToken(config('services.groq.key'))->timeout(45);

            // En Windows/local suele fallar cURL 60 por CA; desactivar solo si se configura.
            if (!config('services.groq.ssl_verify', true)) {
                $request = $request->withoutVerifying();
            }

            $response = $request->post(rtrim(config('services.groq.url'), '/') . '/chat/completions', [
                'model' => config('services.groq.model'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Eres un asistente de taller automotriz. NO emitas diagnósticos definitivos. '
                            . 'Proporciona posibles causas, prioridad (baja/media/alta), servicio recomendado, '
                            . 'observación para el mecánico y advertencias de seguridad. Responde en JSON con claves: '
                            . 'posibles_causas (array), servicio_recomendado, prioridad, observacion_mecanico, advertencia, respuesta_completa.',
                    ],
                    [
                        'role' => 'user',
                        'content' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                    ],
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
                'posibles_causas' => $parsed['posibles_causas'] ?? [],
                'servicio_recomendado' => $parsed['servicio_recomendado'] ?? null,
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
        $tipoFalla = strtolower($payload['tipo_falla'] ?? $payload['tipoFalla'] ?? 'general');

        $rules = match (true) {
            str_contains($tipoFalla, 'freno') => [
                'posibles_causas' => [
                    'Pastillas de freno desgastadas',
                    'Discos deformados o rayados',
                    'Líquido de frenos bajo o contaminado',
                    'Cilindros o mordazas con falla',
                ],
                'servicio_recomendado' => 'Inspección y mantenimiento de sistema de frenos',
                'prioridad' => 'alta',
                'observacion_mecanico' => 'Verificar espesor de pastillas, estado de discos y nivel de líquido DOT.',
                'advertencia' => 'No circular si hay pérdida total de frenado o pedal esponjoso.',
            ],
            str_contains($tipoFalla, 'motor') => [
                'posibles_causas' => [
                    'Falla en sistema de encendido',
                    'Sensor MAP/MAF defectuoso',
                    'Problemas de compresión',
                    'Filtro de aire obstruido',
                ],
                'servicio_recomendado' => 'Diagnóstico computarizado de motor',
                'prioridad' => 'media',
                'observacion_mecanico' => 'Leer códigos OBD-II y revisar bujías, filtros y sensores.',
                'advertencia' => 'Evitar aceleraciones bruscas si hay pérdida de potencia severa.',
            ],
            str_contains($tipoFalla, 'electri') => [
                'posibles_causas' => [
                    'Batería descargada o sulfatada',
                    'Alternador con falla de carga',
                    'Fusible quemado o relé defectuoso',
                    'Cableado con cortocircuito',
                ],
                'servicio_recomendado' => 'Diagnóstico eléctrico automotriz',
                'prioridad' => 'media',
                'observacion_mecanico' => 'Medir voltaje de batería en reposo y con motor encendido.',
                'advertencia' => 'Desconectar batería antes de manipular cableado si hay olor a quemado.',
            ],
            str_contains($tipoFalla, 'suspens') => [
                'posibles_causas' => [
                    'Amortiguadores desgastados',
                    'Bujes o rótulas con holgura',
                    'Resortes fatigados',
                    'Alineación fuera de especificación',
                ],
                'servicio_recomendado' => 'Revisión de suspensión y dirección',
                'prioridad' => 'media',
                'observacion_mecanico' => 'Inspeccionar holguras en rótulas, bujes y estado de amortiguadores.',
                'advertencia' => 'Conducir con precaución si hay vibraciones o ruidos metálicos al girar.',
            ],
            default => [
                'posibles_causas' => [
                    'Requiere inspección visual en taller',
                    'Posible falla intermitente no identificada',
                    'Componente desgastado por uso',
                ],
                'servicio_recomendado' => 'Diagnóstico general de vehículo',
                'prioridad' => $payload['urgencia'] ?? 'media',
                'observacion_mecanico' => 'Realizar entrevista al cliente y prueba de ruta si es seguro.',
                'advertencia' => 'Esta sugerencia es orientativa y requiere confirmación del mecánico.',
            ],
        };

        $descripcion = $payload['descripcion'] ?? $payload['falla_reportada'] ?? '';
        $respuesta = "Sugerencia IA simulada para tipo de falla: {$tipoFalla}. "
            . "Descripción reportada: {$descripcion}. "
            . "Posibles causas: " . implode('; ', $rules['posibles_causas']);

        return array_merge($rules, [
            'respuesta_completa' => $respuesta,
            'es_simulado' => true,
        ]);
    }
}

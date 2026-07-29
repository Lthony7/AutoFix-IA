<?php

return [
    'iva_rate' => (float) env('FACTURA_IVA_RATE', 0.15),
    'serie_default' => env('FACTURA_SERIE', 'F001'),

    'agenda' => [
        // Lun=1 ... Dom=7
        'dias_habiles' => [1, 2, 3, 4, 5, 6],
        'hora_inicio' => '08:00',
        'hora_fin' => '17:00',
        'duracion_slot_minutos' => 60,
        'max_citas_por_slot' => (int) env('AGENDA_MAX_CITAS_SLOT', 3),
        'antelacion_minima_horas' => 12,
        'dias_adelante' => 30,
    ],
];

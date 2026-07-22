<?php

return [
    'iva_rate' => (float) env('FACTURA_IVA_RATE', 0.15),
    'serie_default' => env('FACTURA_SERIE', 'F001'),
];

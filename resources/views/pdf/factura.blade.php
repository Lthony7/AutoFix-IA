<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Factura {{ $f['numero'] }} — AUTOFIX IA</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; margin: 0; padding: 0; }
        .toolbar { background: #059669; color: #fff; padding: 10px 14px; border-radius: 6px; margin-bottom: 20px; }
        .toolbar a { color: #fff; text-decoration: none; font-weight: bold; margin-right: 16px; }
        .header { width: 100%; border-bottom: 3px solid #059669; padding-bottom: 12px; margin-bottom: 18px; }
        .header td { vertical-align: top; }
        .empresa { font-size: 20px; font-weight: bold; color: #059669; }
        .tagline { color: #6b7280; font-size: 10px; }
        .factura-num { text-align: right; }
        .factura-num .num { font-size: 18px; font-weight: bold; }
        .factura-num .serie { color: #6b7280; font-size: 11px; }
        .estado { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 11px; font-weight: bold; color: #fff; }
        .estado-emitida { background: #2563eb; }
        .estado-pagada { background: #059669; }
        .estado-borrador { background: #6b7280; }
        .estado-anulada { background: #dc2626; }
        .block-title { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; margin-bottom: 4px; }
        .datos { width: 100%; margin-bottom: 18px; }
        .datos td { vertical-align: top; padding: 8px; border: 1px solid #e5e7eb; }
        .datos .val { font-weight: bold; margin-top: 2px; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.data th, table.data td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; }
        table.data th { background: #f3f4f6; font-size: 11px; }
        table.data .num { text-align: right; }
        .totales { width: 42%; float: right; margin-bottom: 16px; }
        .totales td { padding: 4px 8px; }
        .totales .lbl { color: #6b7280; }
        .totales .amount { text-align: right; }
        .totales .grande td { border-top: 2px solid #059669; font-size: 14px; font-weight: bold; }
        .obs { border-top: 1px solid #e5e7eb; padding-top: 12px; }
        .footer { margin-top: 24px; color: #9ca3af; font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    @if ($autoPrint ?? false)
        <div class="toolbar">
            <a href="#" onclick="window.print()">Imprimir de nuevo</a>
            <a href="#" onclick="window.close()">Cerrar ventana</a>
        </div>
    @endif

    <table class="header">
        <tr>
            <td>
                <div class="empresa">AUTOFIX IA</div>
                <div class="tagline">Taller automotriz · Facturación</div>
            </td>
            <td class="factura-num">
                <div class="num">Factura {{ $f['numero'] }}</div>
                <div class="serie">Serie {{ $f['serie'] }} · {{ $f['fechaEmision'] }}</div>
                <div>
                    <span class="estado estado-{{ $f['estado'] }}">{{ $f['estadoLabel'] }}</span>
                </div>
            </td>
        </tr>
    </table>

    <table class="datos">
        <tr>
            <td style="width:50%">
                <div class="block-title">Cliente</div>
                <div class="val">{{ $f['clienteNombre'] ?: '—' }}</div>
                @if ($f['clienteTipoDocumento'] || $f['clienteNumeroDocumento'])
                    <div style="color:#6b7280">{{ $f['clienteTipoDocumento'] }} {{ $f['clienteNumeroDocumento'] }}</div>
                @endif
            </td>
            <td>
                <div class="block-title">Contacto</div>
                <div class="val">{{ $f['clienteTelefono'] ?: '—' }}</div>
                <div style="color:#6b7280">{{ $f['clienteEmail'] ?: '—' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="block-title">Dirección</div>
                <div class="val">{{ $f['clienteDireccion'] ?: '—' }}</div>
            </td>
            <td>
                <div class="block-title">Orden de trabajo / Vehículo</div>
                <div class="val">{{ $f['ordenNumero'] ?: '—' }} · {{ $f['vehiculoPlaca'] ?: '—' }}</div>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Descripción</th>
                <th>Tipo</th>
                <th style="width:12%">Cant.</th>
                <th style="width:18%" class="num">P. unit.</th>
                <th style="width:18%" class="num">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($f['detalles'] ?? [] as $d)
                <tr>
                    <td>{{ $d['descripcion'] }}</td>
                    <td>{{ $d['tipoLabel'] ?? $d['tipo'] }}</td>
                    <td>{{ $d['cantidad'] }}</td>
                    <td class="num">{{ number_format((float) $d['precioUnitario'], 2, '.', ',') }}</td>
                    <td class="num">{{ number_format((float) $d['subtotal'], 2, '.', ',') }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Sin ítems</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="totales">
        <tr>
            <td class="lbl">Subtotal</td>
            <td class="amount">{{ number_format((float) $f['subtotal'], 2, '.', ',') }}</td>
        </tr>
        <tr>
            <td class="lbl">Descuento</td>
            <td class="amount">{{ number_format((float) $f['descuento'], 2, '.', ',') }}</td>
        </tr>
        <tr>
            <td class="lbl">IVA ({{ number_format(((float) ($ivaRate ?? 0.15)) * 100, 0) }}%)</td>
            <td class="amount">{{ number_format((float) $f['iva'], 2, '.', ',') }}</td>
        </tr>
        <tr class="grande">
            <td>Total</td>
            <td class="amount">{{ number_format((float) $f['total'], 2, '.', ',') }}</td>
        </tr>
    </table>

    @if ($f['observaciones'])
        <div class="obs">
            <div class="block-title">Observaciones</div>
            <div>{{ $f['observaciones'] }}</div>
        </div>
    @endif

    <div class="footer">AUTOFIX IA — Documento generado el {{ now()->format('Y-m-d H:i') }}.</div>
</body>
</html>

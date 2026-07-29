<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte AUTOFIX IA</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 18px 0 8px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        .meta { color: #555; margin-bottom: 16px; }
        .cards { width: 100%; margin-bottom: 12px; }
        .cards td { width: 25%; vertical-align: top; padding: 6px; border: 1px solid #ddd; }
        .label { color: #666; font-size: 10px; }
        .value { font-size: 14px; font-weight: bold; margin-top: 2px; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.data th, table.data td { border: 1px solid #ddd; padding: 5px 6px; text-align: left; }
        table.data th { background: #f3f3f3; }
        .footer { margin-top: 24px; color: #777; font-size: 9px; }
    </style>
</head>
<body>
    <h1>AUTOFIX IA — Reportes del taller</h1>
    <p class="meta">Generado: {{ $stats['generadoEn'] ?? now()->format('Y-m-d H:i:s') }}</p>

    <table class="cards">
        <tr>
            <td>
                <div class="label">Total órdenes</div>
                <div class="value">{{ $stats['totalOrdenes'] ?? 0 }}</div>
            </td>
            <td>
                <div class="label">Ingresos pagados</div>
                <div class="value">{{ number_format((float) ($stats['totalIngresos'] ?? 0), 2) }}</div>
            </td>
            <td>
                <div class="label">Inventario activos</div>
                <div class="value">{{ $stats['inventarioResumen']['activos'] ?? 0 }}</div>
            </td>
            <td>
                <div class="label">Stock bajo / sin stock</div>
                <div class="value">
                    {{ $stats['inventarioResumen']['stockBajo'] ?? 0 }} /
                    {{ $stats['inventarioResumen']['sinStock'] ?? 0 }}
                </div>
            </td>
        </tr>
    </table>

    <h2>Inventario — valor y alertas</h2>
    <table class="data">
        <thead><tr><th>Métrica</th><th>Valor</th></tr></thead>
        <tbody>
            <tr><td>Ítems totales</td><td>{{ $stats['inventarioResumen']['totalItems'] ?? 0 }}</td></tr>
            <tr><td>Activos</td><td>{{ $stats['inventarioResumen']['activos'] ?? 0 }}</td></tr>
            <tr><td>Valor de stock</td><td>{{ number_format((float) ($stats['inventarioResumen']['valorStock'] ?? 0), 2) }}</td></tr>
            <tr><td>Stock bajo</td><td>{{ $stats['inventarioResumen']['stockBajo'] ?? 0 }}</td></tr>
            <tr><td>Sin stock</td><td>{{ $stats['inventarioResumen']['sinStock'] ?? 0 }}</td></tr>
        </tbody>
    </table>

    <h2>Inventario con stock bajo</h2>
    <table class="data">
        <thead><tr><th>Código</th><th>Ítem</th><th>Categoría</th><th>Stock</th><th>Mínimo</th></tr></thead>
        <tbody>
            @forelse($stats['stockBajo'] ?? [] as $row)
                <tr>
                    <td>{{ $row['codigo'] }}</td>
                    <td>{{ $row['nombre'] }}</td>
                    <td>{{ $row['categoria'] ?? '—' }}</td>
                    <td>{{ $row['stock'] }}</td>
                    <td>{{ $row['stockMinimo'] }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Sin alertas de stock</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Órdenes por estado</h2>
    <table class="data">
        <thead><tr><th>Estado</th><th>Total</th></tr></thead>
        <tbody>
            @forelse($stats['ordenesPorEstado'] ?? [] as $row)
                <tr><td>{{ $row['label'] }}</td><td>{{ $row['total'] }}</td></tr>
            @empty
                <tr><td colspan="2">Sin datos</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Sugerencias IA por estado</h2>
    <table class="data">
        <thead><tr><th>Estado</th><th>Total</th></tr></thead>
        <tbody>
            @forelse($stats['sugerenciasIa'] ?? [] as $row)
                <tr><td>{{ $row['label'] }}</td><td>{{ $row['total'] }}</td></tr>
            @empty
                <tr><td colspan="2">Sin datos</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Ingresos por fecha</h2>
    <table class="data">
        <thead><tr><th>Fecha</th><th>Total</th></tr></thead>
        <tbody>
            @forelse($stats['ingresosPorFecha'] ?? [] as $row)
                <tr><td>{{ $row['fecha'] }}</td><td>{{ number_format((float) $row['total'], 2) }}</td></tr>
            @empty
                <tr><td colspan="2">Sin datos</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Servicios más solicitados</h2>
    <table class="data">
        <thead><tr><th>Servicio</th><th>Cantidad</th><th>Ingresos</th></tr></thead>
        <tbody>
            @forelse($stats['serviciosTop'] ?? [] as $row)
                <tr>
                    <td>{{ $row['nombre'] }}</td>
                    <td>{{ $row['total'] }}</td>
                    <td>{{ number_format((float) $row['ingresos'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Sin datos</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Inventario más usado en órdenes</h2>
    <table class="data">
        <thead><tr><th>Ítem</th><th>Cantidad</th><th>Órdenes</th><th>Ingresos</th></tr></thead>
        <tbody>
            @forelse($stats['repuestosTop'] ?? [] as $row)
                <tr>
                    <td>{{ $row['nombre'] }}</td>
                    <td>{{ $row['cantidad'] }}</td>
                    <td>{{ $row['ordenes'] }}</td>
                    <td>{{ number_format((float) $row['ingresos'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Sin datos</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Vehículos atendidos por cliente</h2>
    <table class="data">
        <thead><tr><th>Cliente</th><th>Vehículos</th><th>Órdenes</th></tr></thead>
        <tbody>
            @forelse($stats['vehiculosPorCliente'] ?? [] as $row)
                <tr>
                    <td>{{ $row['cliente'] }}</td>
                    <td>{{ $row['vehiculos'] }}</td>
                    <td>{{ $row['ordenes'] }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Sin datos</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">AUTOFIX IA — Solo administradores y recepción pueden exportar este reporte.</p>
</body>
</html>

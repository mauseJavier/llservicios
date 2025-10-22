<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Cierre de Caja</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007BFF;
            padding-bottom: 15px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #007BFF;
            margin-bottom: 5px;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .report-info {
            font-size: 11px;
            color: #666;
        }
        .info-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .calculation-section {
            border: 2px solid #007BFF;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .result-box {
            background: #e9f7ff;
            border: 1px solid #007BFF;
            padding: 15px;
            text-align: center;
            border-radius: 5px;
            margin-top: 15px;
        }
        .result-positive {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .result-negative {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .movements-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .movements-table th,
        .movements-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .movements-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .amount-positive {
            color: #28a745;
            font-weight: bold;
        }
        .amount-negative {
            color: #dc3545;
            font-weight: bold;
        }
        .formula {
            background: #f0f0f0;
            padding: 10px;
            text-align: center;
            font-family: monospace;
            border-radius: 3px;
            margin: 10px 0;
        }
        .summary-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .summary-row {
            display: table-row;
        }
        .summary-cell {
            display: table-cell;
            padding: 8px;
            border: 1px solid #ddd;
            background: #f9f9f9;
        }
        .summary-cell.label {
            background: #e9ecef;
            font-weight: bold;
            width: 40%;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">
            {{ $empresa->nombre ?? 'Empresa' }}
        </div>
        <div class="report-title">REPORTE DE CIERRE DE CAJA</div>
        <div class="report-info">
            Fecha: {{ $fecha->format('d/m/Y') }} | 
            Usuario: {{ $usuario->name }} | 
            Generado: {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <!-- Información general -->
    <div class="info-section">
        <h3 style="margin-top: 0; color: #007BFF;">📋 Resumen del Día</h3>
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-cell label">Total Movimientos de Inicio:</div>
                <div class="summary-cell">{{ $resumenDia['cantidad_inicios'] }} registro(s)</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell label">Total Movimientos de Cierre:</div>
                <div class="summary-cell">{{ $resumenDia['cantidad_cierres'] }} registro(s)</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell label">Total Pagos Registrados:</div>
                <div class="summary-cell">{{ $resumenDia['total_movimientos_pagos'] }} pago(s)</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell label">Total Gastos Registrados:</div>
                <div class="summary-cell">{{ $resumenDia['total_movimientos_gastos'] }} gasto(s)</div>
            </div>
        </div>
    </div>

    <!-- Cálculo principal -->
    <div class="calculation-section">
        <h3 style="margin-top: 0; color: #007BFF;">💰 Cálculo de Cierre</h3>
        
        <div class="formula">
            (-Suma Inicios) + (-Suma Pagos) + (Suma Cierres) + (Suma Gastos)
        </div>

        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-cell label">Total Inicio de Caja:</div>
                <div class="summary-cell amount-negative">-${{ number_format($calculoCaja['inicio_caja'], 2, ',', '.') }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell label">Total Pagos:</div>
                <div class="summary-cell amount-negative">-${{ number_format($calculoCaja['total_pagos'], 2, ',', '.') }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell label">Total Cierre de Caja:</div>
                <div class="summary-cell amount-positive">+${{ number_format($calculoCaja['cierre_caja'], 2, ',', '.') }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell label">Total Gastos:</div>
                <div class="summary-cell amount-positive">+${{ number_format($calculoCaja['total_gastos'], 2, ',', '.') }}</div>
            </div>
        </div>

        <div class="result-box {{ $calculoCaja['calculo_final'] >= 0 ? 'result-positive' : 'result-negative' }}">
            <h2 style="margin: 0;">
                {{ $calculoCaja['calculo_final'] >= 0 ? ' RESULTADO POSITIVO' : ' RESULTADO NEGATIVO' }}
            </h2>
            <div style="font-size: 24px; font-weight: bold; margin-top: 10px;">
                ${{ number_format($calculoCaja['calculo_final'], 2, ',', '.') }}
            </div>
        </div>
    </div>

    <!-- Detalle de movimientos de inicio -->
    @if($movimientosInicio->count() > 0)
    <div>
        <h3 style="color: #dc3545;"> Movimientos de Inicio de Caja</h3>
        <table class="movements-table">
            <thead>
                <tr>
                    <th>Hora</th>
                    <th>Importe</th>
                    <th>Comentario</th>
                </tr>
            </thead>
            <tbody>
                @foreach($movimientosInicio as $movimiento)
                <tr>
                    <td>{{ $movimiento->created_at->format('H:i') }}</td>
                    <td class="amount-negative">${{ number_format($movimiento->importe, 2, ',', '.') }}</td>
                    <td>{{ $movimiento->comentario ?: '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Detalle de movimientos de cierre -->
    @if($movimientosCierre->count() > 0)
    <div>
        <h3 style="color: #28a745;"> Movimientos de Cierre de Caja</h3>
        <table class="movements-table">
            <thead>
                <tr>
                    <th>Hora</th>
                    <th>Importe</th>
                    <th>Comentario</th>
                </tr>
            </thead>
            <tbody>
                @foreach($movimientosCierre as $movimiento)
                <tr>
                    <td>{{ $movimiento->created_at->format('H:i') }}</td>
                    <td class="amount-positive">${{ number_format($movimiento->importe, 2, ',', '.') }}</td>
                    <td>{{ $movimiento->comentario ?: '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Detalle de pagos -->
    @if($pagosDia->count() > 0)
    <div>
        <h3 style="color: #dc3545;"> Pagos del Día</h3>
        <table class="movements-table">
            <thead>
                <tr>
                    <th>Hora</th>
                    <th>Importe</th>
                    <th>Comentario</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pagosDia as $pago)
                <tr>
                    <td>{{ $pago->created_at->format('H:i') }}</td>
                    <td class="amount-negative">${{ number_format($pago->importe, 2, ',', '.') }}</td>
                    <td>{{ $pago->comentario ?: '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Detalle de gastos -->
    @if($gastosDia->count() > 0)
    <div>
        <h3 style="color: #28a745;"> Gastos del Día</h3>
        <table class="movements-table">
            <thead>
                <tr>
                    <th>Hora</th>
                    <th>Detalle</th>
                    <th>Importe</th>
                    <th>Comentario</th>
                </tr>
            </thead>
            <tbody>
                @foreach($gastosDia as $gasto)
                <tr>
                    <td>{{ $gasto->created_at->format('H:i') }}</td>
                    <td>{{ $gasto->detalle }}</td>
                    <td class="amount-positive">${{ number_format($gasto->importe, 2, ',', '.') }}</td>
                    <td>{{ $gasto->comentario ?: '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        Reporte generado automáticamente por el sistema de gestión de caja<br>
        {{ $empresa->nombre ?? 'Sistema' }} - {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
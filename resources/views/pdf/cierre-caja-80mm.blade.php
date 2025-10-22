<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Cierre de Caja</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            font-size: 10px;
            margin: 5px;
            color: #000;
            line-height: 1.3;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        .company-name {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .report-title {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .report-info {
            font-size: 8px;
        }
        .section {
            margin-bottom: 10px;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 8px;
        }
        .section:last-child {
            border-bottom: none;
        }
        .section-title {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 5px;
            text-align: center;
        }
        .line-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
            font-size: 9px;
        }
        .line-item.total {
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 3px;
            margin-top: 5px;
        }
        .result-box {
            text-align: center;
            padding: 8px;
            margin: 10px 0;
            border: 1px solid #000;
        }
        .result-positive {
            background: #f0f0f0;
        }
        .result-negative {
            background: #f0f0f0;
        }
        .amount-positive {
            color: #000;
        }
        .amount-negative {
            color: #000;
        }
        .formula {
            text-align: center;
            font-size: 8px;
            margin: 5px 0;
            border: 1px solid #000;
            padding: 3px;
        }
        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 7px;
            border-top: 1px dashed #000;
            padding-top: 5px;
        }
        .detail-item {
            font-size: 8px;
            margin-bottom: 3px;
            padding-left: 5px;
        }
        .time {
            font-size: 8px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">
            {{ $empresa->nombre ?? 'EMPRESA' }}
        </div>
        <div class="report-title">CIERRE DE CAJA</div>
        <div class="report-info">
            {{ $fecha->format('d/m/Y') }} - {{ $usuario->name }}<br>
            Generado: {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <!-- Resumen -->
    <div class="section">
        <div class="section-title">RESUMEN DEL DIA</div>
        <div class="line-item">
            <span>Inicios registrados:</span>
            <span>{{ $resumenDia['cantidad_inicios'] }}</span>
        </div>
        <div class="line-item">
            <span>Cierres registrados:</span>
            <span>{{ $resumenDia['cantidad_cierres'] }}</span>
        </div>
        <div class="line-item">
            <span>Pagos registrados:</span>
            <span>{{ $resumenDia['total_movimientos_pagos'] }}</span>
        </div>
        <div class="line-item">
            <span>Gastos registrados:</span>
            <span>{{ $resumenDia['total_movimientos_gastos'] }}</span>
        </div>
    </div>

    <!-- Cálculo -->
    <div class="section">
        <div class="section-title">CALCULO DE CIERRE</div>
        
        <div class="formula">
            (-Inicios) + (-Pagos) + (Cierres) + (Gastos)
        </div>

        <div class="line-item">
            <span>Total Inicios:</span>
            <span class="amount-negative">-${{ number_format($calculoCaja['inicio_caja'], 2, ',', '.') }}</span>
        </div>
        <div class="line-item">
            <span>Total Pagos:</span>
            <span class="amount-negative">-${{ number_format($calculoCaja['total_pagos'], 2, ',', '.') }}</span>
        </div>
        <div class="line-item">
            <span>Total Cierres:</span>
            <span class="amount-positive">+${{ number_format($calculoCaja['cierre_caja'], 2, ',', '.') }}</span>
        </div>
        <div class="line-item">
            <span>Total Gastos:</span>
            <span class="amount-positive">+${{ number_format($calculoCaja['total_gastos'], 2, ',', '.') }}</span>
        </div>

        <div class="result-box {{ $calculoCaja['calculo_final'] >= 0 ? 'result-positive' : 'result-negative' }}">
            <div style="font-weight: bold;">
                {{ $calculoCaja['calculo_final'] >= 0 ? 'RESULTADO POSITIVO' : 'RESULTADO NEGATIVO' }}
            </div>
            <div style="font-size: 14px; font-weight: bold; margin-top: 3px;">
                ${{ number_format($calculoCaja['calculo_final'], 2, ',', '.') }}
            </div>
        </div>
    </div>

    <!-- Detalle de movimientos de inicio -->
    @if($movimientosInicio->count() > 0)
    <div class="section">
        <div class="section-title">INICIOS DE CAJA</div>
        @foreach($movimientosInicio as $movimiento)
        <div class="detail-item">
            <span class="time">{{ $movimiento->created_at->format('H:i') }}</span> - 
            ${{ number_format($movimiento->importe, 2, ',', '.') }}
            @if($movimiento->comentario)
            <br><span style="font-size: 7px;">{{ $movimiento->comentario }}</span>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    <!-- Detalle de movimientos de cierre -->
    @if($movimientosCierre->count() > 0)
    <div class="section">
        <div class="section-title">CIERRES DE CAJA</div>
        @foreach($movimientosCierre as $movimiento)
        <div class="detail-item">
            <span class="time">{{ $movimiento->created_at->format('H:i') }}</span> - 
            ${{ number_format($movimiento->importe, 2, ',', '.') }}
            @if($movimiento->comentario)
            <br><span style="font-size: 7px;">{{ $movimiento->comentario }}</span>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    <!-- Detalle de pagos -->
    @if($pagosDia->count() > 0)
    <div class="section">
        <div class="section-title">PAGOS DEL DIA</div>
        @foreach($pagosDia as $pago)
        <div class="detail-item">
            <span class="time">{{ $pago->created_at->format('H:i') }}</span> - 
            ${{ number_format($pago->importe, 2, ',', '.') }}
            @if($pago->comentario)
            <br><span style="font-size: 7px;">{{ $pago->comentario }}</span>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    <!-- Detalle de gastos -->
    @if($gastosDia->count() > 0)
    <div class="section">
        <div class="section-title">GASTOS DEL DIA</div>
        @foreach($gastosDia as $gasto)
        <div class="detail-item">
            <span class="time">{{ $gasto->created_at->format('H:i') }}</span> - 
            {{ $gasto->detalle }}<br>
            ${{ number_format($gasto->importe, 2, ',', '.') }}
            @if($gasto->comentario)
            <br><span style="font-size: 7px;">{{ $gasto->comentario }}</span>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    <div class="footer">
        Sistema de gestion de caja<br>
        {{ $empresa->nombre ?? 'Sistema' }}<br>
        {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
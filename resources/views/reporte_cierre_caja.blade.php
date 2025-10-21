
<div style="width: 700px; margin: 0 auto;">
    <h2 style="text-align: center;">Reporte de Cierre de Caja</h2>
    <p><strong>Usuario:</strong> {{ $usuario }}</p>
    <p><strong>Fecha:</strong> {{ $fecha }}</p>
    <hr>
    <h4>Movimientos</h4>
    <table width="100%" border="1" cellspacing="0" cellpadding="5">
        <thead>
            <tr>
                <th>Concepto</th>
                <th>Monto</th>
                <th>Tipo</th>
                <th>Hora</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movimientos as $mov)
            <tr>
                <td>{{ $mov->concepto }}</td>
                <td style="text-align: right;">${{ number_format($mov->monto, 2) }}</td>
                <td>{{ $mov->tipo }}</td>
                <td>{{ $mov->hora }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    @if(!empty($formas_pago) && count($formas_pago) > 0)
    <hr>
    <h4>Formas de pago</h4>
    <table width="100%" border="1" cellspacing="0" cellpadding="5">
        <thead>
            <tr>
                <th>Forma de pago</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($formas_pago as $forma => $total)
                <tr>
                    <td>{{ $forma }}</td>
                    <td style="text-align: right;">${{ number_format($total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(!empty($cierres_usuario) && count($cierres_usuario) > 0)
    <hr>
    <h4>Registro de inicio / cierre</h4>
    <table width="100%" border="1" cellspacing="0" cellpadding="5">
        <thead>
            <tr>
                <th>Descripción</th>
                <th>Monto</th>
                <th>Hora</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cierres_usuario as $c)
                <tr>
                    <td>{{ $c->descripcion }}</td>
                    <td style="text-align: right;">${{ number_format($c->importe, 2) }}</td>
                    <td>{{ optional($c->created_at)->format('H:i:s') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif
    <hr>
    <p><strong>Total ingresos:</strong> ${{ number_format($total_ingresos, 2) }}</p>
    <p><strong>Total egresos:</strong> ${{ number_format($total_egresos, 2) }}</p>
    <p><strong>Saldo final:</strong> ${{ number_format($saldo_final, 2) }}</p>
</div>

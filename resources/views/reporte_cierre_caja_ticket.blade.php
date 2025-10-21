<div style="width: 200px; max-width:200px; font-family: 'Courier New', Courier, monospace; font-size:11px; color:#000;">
    <div style="text-align:center;">
        <h3 style="margin:4px 0; font-size:12px;">REPORTE CIERRE DE CAJA</h3>
        <div style="margin-bottom:6px;">Usuario: {{ $usuario }}</div>
        <div style="margin-bottom:6px;">Fecha: {{ $fecha }}</div>
    </div>

    {{-- <hr style="border:none; border-top:1px dashed #000; margin:6px 0;">

    <div style="font-size:12px;">
        <div style="display:flex; justify-content:space-between; font-weight:bold;">
            <div style="width:100px;">Concepto</div>
            <div style="width:60px; text-align:right;">Monto</div>
            <div style="width:40px; text-align:right;">Tipo</div>
        </div>
        <div style="margin-top:4px;">
            @foreach($movimientos as $mov)
                <div style="display:flex; justify-content:space-between; margin-bottom:2px;">
                    <div style="width:100px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ Str::limit($mov->concepto, 14) }}</div>
                    <div style="width:60px; text-align:right;">${{ number_format($mov->monto, 2) }}</div>
                    <div style="width:40px; text-align:right;">{{ strtoupper(substr($mov->tipo,0,3)) }}</div>
                </div>
            @endforeach
        </div>
    </div> --}}

    <hr style="border:none; border-top:1px dashed #000; margin:6px 0;">

    @if(!empty($formas_pago) && count($formas_pago) > 0)
        <div style="margin-top:6px; font-weight:bold;">Formas de pago</div>
        @foreach($formas_pago as $forma => $total)
            <div style="display:flex; justify-content:space-between;">
                <div style="width:120px;">{{ Str::limit($forma, 12) }}</div>
                <div style="width:60px; text-align:right;">${{ number_format($total, 2) }}</div>
            </div>
        @endforeach
    @endif

    @if(!empty($cierres_usuario) && count($cierres_usuario) > 0)
        <div style="margin-top:6px; font-weight:bold;">Inicio / Cierre</div>
        @foreach($cierres_usuario as $c)
            <div style="display:flex; justify-content:space-between;">
                <div style="width:120px;">{{ Str::limit($c->descripcion, 12) }}</div>
                <div style="width:60px; text-align:right;">${{ number_format($c->importe, 2) }}</div>
            </div>
        @endforeach
    @endif

    @php
        $maxGastosTicket = $maxGastosTicket ?? 6; // máximo por defecto
        $gastosList = collect($gastos_usuario ?? []);
        $gastosCount = $gastosList->count();
        $gastosToShow = $gastosList->slice(0, $maxGastosTicket);
    @endphp

    @if($gastosCount > 0)
        <div style="margin-top:6px; font-weight:bold;">Gastos</div>
        @foreach($gastosToShow as $g)
            <div style="display:flex; justify-content:space-between;">
                <div style="width:120px;">{{ Str::limit($g->detalle ?? ($g['detalle'] ?? ''), 12) }}</div>
                <div style="width:60px; text-align:right;">${{ number_format($g->importe ?? ($g['importe'] ?? 0), 2) }}</div>
            </div>
        @endforeach

        @if($gastosCount > $maxGastosTicket)
            <div style="margin-top:4px; color:#555;">... y {{ $gastosCount - $maxGastosTicket }} más</div>
        @endif

        <div style="display:flex; justify-content:space-between; font-weight:bold; margin-top:4px;">
            <div>Total Gastos</div>
            <div style="text-align:right;">${{ number_format($total_gastos ?? 0, 2) }}</div>
        </div>
    @endif

    <div style="display:flex; justify-content:space-between; font-weight:bold; margin-top:6px;">
        <div>Total ingresos</div>
        <div style="text-align:right;">${{ number_format($total_ingresos, 2) }}</div>
    </div>
    {{-- <div style="display:flex; justify-content:space-between; font-weight:bold;">
        <div>Total egresos</div>
        <div style="text-align:right;">${{ number_format($total_egresos, 2) }}</div>
    </div> --}}
    <div style="display:flex; justify-content:space-between; font-weight:bold; font-size:13px; margin-top:6px;">
        <div>Saldo final</div>
        <div style="text-align:right;">${{ number_format($saldo_final, 2) }}</div>
    </div>

    <hr style="border:none; border-top:1px dashed #000; margin:8px 0;">
    <div style="text-align:center; font-size:11px;">Gracias por su preferencia</div>
</div>

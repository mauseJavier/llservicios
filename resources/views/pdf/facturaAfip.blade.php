<!DOCTYPE html>
<html>
<head>
    <title>Factura</title>
    <style type="text/css">
        *{
            box-sizing: border-box;
        }
        .bill-container{
            width: 100%;
            max-width: 750px;
            margin: 0 auto;
            border-collapse: collapse;
            font-family: sans-serif;
            font-size: 13px;
        }

        .bill-emitter-row td{
            width: 50%;
            border-bottom: 1px solid; 
            padding-top: 10px;
            padding-left: 10px;
            vertical-align: top;
        }
        .bill-emitter-row{
            position: relative;
        }
        .bill-emitter-row td:nth-child(2){
            padding-left: 60px;
        }
        .bill-emitter-row td:nth-child(1){
            padding-right: 60px;
        }

        .bill-type{
            border: 1px solid;
            background: white;
            width: 60px;
            height: 50px;
            display: inline-block;
            text-align: center;
            font-size: 40px;
            font-weight: 600;
            line-height: 50px;
            margin: 0 auto 6px auto;
        }
        .text-lg{
            font-size: 30px;
        }
        .text-center{
            text-align: center;
        }

        .row-table{
            width: 100%;
            border-collapse: collapse;
        }
        .row-table td{
            vertical-align: top;
        }
        .w-16{ width: 16.66666667%; }
        .w-25{ width: 25%; }
        .w-33{ width: 33.3333333%; }
        .w-41{ width: 41.66666667%; }
        .w-50{ width: 50%; }
        .w-66{ width: 66.66666667%; }
        .w-83{ width: 83.33333333%; }

        .margin-b-0{
            margin-bottom: 0px;
        }

        .bill-row td{
            padding-top: 5px
        }

        .bill-row td > div{
            border-top: 1px solid; 
            border-bottom: 1px solid; 
            margin: 0 -1px 0 -2px;
            padding: 0 10px 13px 10px;
        }
        .row-details table {
            border-collapse: collapse;
            width: 100%;
        }
        .row-details td > div, .row-qrcode td > div{
            border: 0;
            margin: 0 -1px 0 -2px;
            padding: 0;
        }
        .row-details table td{
            padding: 5px;
        }
        .row-details table tr:nth-child(1){
            border-top: 1px solid; 
            border-bottom: 1px solid; 
            background: #c0c0c0;
            font-weight: bold;
            text-align: center;
        }
        .row-details table tr +  tr{
            border-top: 1px solid #c0c0c0; 
			
        }
        .text-right{
            text-align: right;
        }

        .margin-b-10 {
            margin-bottom: 10px;
        }

        .total-row td > div{
            border-width: 2px;
        }

        .row-qrcode td{
            padding: 10px;
        }		

        #qrcode {
            width: 50%
        }
    </style>
</head>
<body>
    <table class="bill-container">
        <tr class="bill-emitter-row">
            <td>
                <div class="text-lg text-center">{{ $empresa->nombre }}</div>
                <p><strong>Razón social:</strong> {{ $empresa->nombre }}</p>
                @if($empresa->direccion)
                    <p><strong>Domicilio Comercial:</strong> {{ $empresa->direccion }}</p>
                @endif
                <p><strong>Condición Frente al IVA:</strong> {{ $empresa->condicion_iva ?? '—' }}</p>
            </td>
            <td class="text-center">
                <div class="bill-type">
                    {{ substr($pago->tipo_comprobante_nombre ?? 'Factura', -1) }}
                </div>

                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <p>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                COD: {{ str_pad($pago->afip_tipo_comprobante ?? 0, 2, '0', STR_PAD_LEFT) }}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            </p>
            </td>
            <td>
                <div class="text-lg">{{ $pago->tipo_comprobante_nombre ?? 'Factura' }}</div>
                <p>
                    COD: {{ str_pad($pago->afip_tipo_comprobante ?? 0, 2, '0', STR_PAD_LEFT) }}
                </p>


                <table class="row-table">
                    <tr>
                        <td class="w-50">
                            <strong>Punto de Venta: {{ str_pad($pago->afip_punto_venta ?? 0, 4, '0', STR_PAD_LEFT) }}</strong>
                        </td>
                        <td class="w-50">
                            <strong>Comp. Nro: {{ str_pad($pago->afip_numero_comprobante ?? 0, 8, '0', STR_PAD_LEFT) }}</strong>
                        </td>
                    </tr>
                </table>
                <p><strong>Fecha de Emisión:</strong> {{ \Carbon\Carbon::parse($pago->created_at)->format('d/m/Y') }}</p>
                <p><strong>CUIT:</strong> {{ $empresa->cuit }}</p>
                @if($empresa->ingresos_brutos)
                    <p><strong>Ingresos Brutos:</strong> {{ $empresa->ingresos_brutos }}</p>
                @endif
                @if($empresa->inicio_actividades)
                    <p><strong>Fecha de Inicio de Actividades:</strong> {{ \Carbon\Carbon::parse($empresa->inicio_actividades)->format('d/m/Y') }}</p>
                @endif
            </td>
        </tr>
        <tr class="bill-row">
            <td colspan="3">
                <table class="row-table">
                    <tr>
                        <td class="w-33"><strong>Período Facturado Desde:</strong> {{ \Carbon\Carbon::parse($pago->created_at)->format('d/m/Y') }}</td>
                        <td class="w-25"><strong>Hasta:</strong> {{ \Carbon\Carbon::parse($pago->created_at)->format('d/m/Y') }}</td>
                        <td class="w-41"><strong>Fecha de Vto. para el pago:</strong> {{ \Carbon\Carbon::parse($pago->afip_cae_vencimiento)->format('d/m/Y') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr class="bill-row">
            <td colspan="3">
                <table class="row-table">
                    <tr>
                        <td class="w-33"><strong>CUIL/CUIT:</strong> {{ $cliente->dni ?? '—' }}</td>
                        <td class="w-66"><strong>Apellido y Nombre / Razón social:</strong> {{ $datos->Cliente ?? ($cliente->nombre ?? '—') }}</td>
                    </tr>
                    <tr>
                        <td class="w-50"><strong>Condición Frente al IVA:</strong> {{ $cliente->condicion_iva ?? 'Consumidor final' }}</td>
                        <td class="w-50"><strong>Domicilio:</strong> {{ $cliente->direccion ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="w-100" colspan="2"><strong>Condición de venta:</strong> {{ $datos->formaPago ?? '—' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr class="bill-row row-details">
            <td colspan="3">
                <div>
                    <table>
                        <tr>
                            <td>Código</td>
                            <td>Producto / Servicio</td>
                            <td>Cantidad</td>
                            <td>U. Medida</td>
                            <td>Precio Unit.</td>
                            <td>% Bonif.</td>
                            <td>Imp. Bonif.</td>
                            <td>Subtotal</td>
                        </tr>
                        <tr>
                            <td>{{ $datos->codigo ?? '—' }}</td>
                            <td>{{ $datos->Servicio ?? '—' }}</td>
                            <td>1,00</td>
                            <td>Unidad</td>
                            <td>{{ number_format($datos->importe ?? 0, 2, ',', '.') }}</td>
                            <td>0,00</td>
                            <td>0,00</td>
                            <td>{{ number_format(($datos->importe ?? 0) + ($datos->importe2 ?? 0), 2, ',', '.') }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
        <tr class="bill-row total-row">
            <td colspan="3">
                <table class="row-table">
                    <tr class="text-right">
                        <td class="w-83"><strong>Subtotal: $</strong></td>
                        <td class="w-16"><strong>{{ number_format(($datos->importe ?? 0) + ($datos->importe2 ?? 0), 2, ',', '.') }}</strong></td>
                    </tr>
                    <tr class="text-right">
                        <td class="w-83"><strong>Importe Otros Tributos: $</strong></td>
                        <td class="w-16"><strong>0,00</strong></td>
                    </tr>
                    <tr class="text-right">
                        <td class="w-83"><strong>Importe total: $</strong></td>
                        <td class="w-16"><strong>{{ number_format(($datos->importe ?? 0) + ($datos->importe2 ?? 0), 2, ',', '.') }}</strong></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr class="bill-row row-details">
            <td colspan="2">
                <div>
                    <div class="row">
                        @if(!empty($qrBase64))
                            <img id="qrcode" src="{{ $qrBase64 }}">
                        @endif
                    </div>
                </div>
            </td>
            <td>
                <div>
                    <div class="row text-right margin-b-10">
                        <strong>CAE Nº:&nbsp;</strong> {{ $pago->afip_cae }}
                    </div>
                    <div class="row text-right">
                        <strong>Fecha de Vto. de CAE:&nbsp;</strong> {{ \Carbon\Carbon::parse($pago->afip_cae_vencimiento)->format('d/m/Y') }}
                    </div>
                </div>
            </td>
        </tr>
        <tr class="bill-row row-details">
            <td colspan="3">
                <div>
                    <div class="row text-center margin-b-10">
                        <span class="vertical-align:bottom">Generado con LLServicios</span>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>

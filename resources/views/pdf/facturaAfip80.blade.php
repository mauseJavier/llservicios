<!DOCTYPE html>
<html>
<head>
	<title>Ticket</title>
	<style type="text/css">
		*{
			box-sizing: border-box;
			-webkit-user-select: none; /* Chrome, Opera, Safari */
			-moz-user-select: none; /* Firefox 2+ */
			-ms-user-select: none; /* IE 10+ */
			user-select: none; /* Standard syntax */
		}

		.bill-container{
			border-collapse: collapse;
			max-width: 8cm;
			position: absolute;
			left:0;
			right: 0;
			margin: auto;
			border-collapse: collapse;
			font-family: monospace;
			font-size: 12px;
		}

		.text-lg{
			font-size: 20px;
		}

		.text-center{
			text-align: center;
		}
	

		#qrcode {
			width: 75%
		}

		p {
			margin: 2px 0;
		}

		table table {
			width: 100%;
		}

		
		table table tr td:last-child{
			text-align: right;
		}

		.border-top {
			border-top: 1px dashed;
		}

		.padding-b-3 {
			padding-bottom: 3px;
		}

		.padding-t-3 {
			padding-top: 3px;
		}

	</style>
</head>
<body>
	<table class="bill-container">
		<tr>
			<td class="padding-b-3">
				<p>Razón social: {{ $empresa->nombre }}</p>
				@if($empresa->direccion)
					<p>Direccion: {{ $empresa->direccion }}</p>
				@endif
				<p>C.U.I.T.: {{ $empresa->cuit }}</p>
				<p>{{ $empresa->condicion_iva ?? '—' }}</p>
				@if($empresa->ingresos_brutos)
					<p>IIBB: {{ $empresa->ingresos_brutos }}</p>
				@endif
				@if($empresa->inicio_actividades)
					<p>Inicio de actividad: {{ \Carbon\Carbon::parse($empresa->inicio_actividades)->format('d/m/Y') }}</p>
				@endif
			</td>
		</tr>
		<tr>
			<td class="border-top padding-t-3 padding-b-3">
				<p class="text-center text-lg">{{ strtoupper($pago->tipo_comprobante_nombre ?? 'Factura') }}</p>
				<p class="text-center">COD {{ str_pad($pago->afip_tipo_comprobante ?? 0, 2, '0', STR_PAD_LEFT) }}</p>
				<p>P.V: {{ str_pad($pago->afip_punto_venta ?? 0, 4, '0', STR_PAD_LEFT) }}</p>
				<p>Nro: {{ str_pad($pago->afip_numero_comprobante ?? 0, 8, '0', STR_PAD_LEFT) }}</p>
				<p>Fecha: {{ \Carbon\Carbon::parse($pago->created_at)->format('d/m/Y') }}</p>
				<p>Concepto: {{ $datos->concepto ?? 'Servicios' }}</p>
			</td>
		</tr>
		<tr>
			<td class="border-top padding-t-3 padding-b-3">
				<p>{{ $cliente->condicion_iva ?? 'Consumidor final' }}</p>
				<p>CUIL/CUIT: {{ $cliente->dni ?? '—' }}</p>
				<p>Cliente: {{ $datos->Cliente ?? ($cliente->nombre ?? '—') }}</p>
				<p>Domicilio: {{ $cliente->direccion ?? '—' }}</p>
				<p>Cond. venta: {{ $datos->formaPago ?? '—' }}</p>
			</td>
		</tr>
		<tr>
			<td class="border-top padding-t-3 padding-b-3">
				<div>
					<table>
						<tr>
							<td>1</td>
							<td>{{ $datos->Servicio ?? '—' }}</td>
							<td>{{ $datos->codigo ?? '—' }}</td>
							<td>{{ number_format(($datos->importe ?? 0) + ($datos->importe2 ?? 0), 2, ',', '.') }}</td>
						</tr>
					</table>
				</div>
			</td>
		</tr>
		<tr>
			<td class="border-top padding-t-3 padding-b-3">
				<div>
					<table>
						<tr>
							<td>TOTAL</td>
							<td>{{ number_format(($datos->importe ?? 0) + ($datos->importe2 ?? 0), 2, ',', '.') }}</td>
						</tr>
					</table>
				</div>
			</td>
		</tr>
		<tr>
			<td class="border-top padding-t-3">
				<p>CAE: {{ $pago->afip_cae ?? '—' }}</p>
				<p>Vto: {{ \Carbon\Carbon::parse($pago->afip_cae_vencimiento)->format('d/m/Y') }}</p>
			</td>
		</tr>
		<tr class="text-center">
			<td>
				@if(!empty($qrBase64))
					<img id="qrcode" src="{{ $qrBase64 }}">
				@endif
			</td>
		</tr>
		<tr class="bill-row row-details">
			<td style="margin-bottom: 10px; text-align: center;">
				<span class="vertical-align:bottom">Generado con LLServicios</span>
			</td>
		</tr>
	</table>
</body>
</html>
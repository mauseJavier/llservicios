<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Comprobante de Pago</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 10px; }
        .titulo { font-size: 16px; font-weight: bold; margin-bottom: 10px; }
        .dato { margin-bottom: 4px; }
        .resaltado { font-weight: bold; }
        .total { font-size: 14px; font-weight: bold; margin-top: 10px; }
        .footer { margin-top: 15px; font-size: 10px; color: #888; }
    </style>
</head>
<body>
    <div style="text-align: center; margin-bottom: 10px;">
        <img src="{{ $logoEmpresa ?? '' }}" alt="Logo" style="max-width: 150px; max-height: 80px; height: auto;">
    </div>

    <div class="titulo">Comprobante de Pago</div>
    <hr>
    <div class="dato" style="text-align: center;"><span class="resaltado">Empresa:</span> <strong>{{ $nombreEmpresa ?? '' }}</strong></div>
    <hr>

    <div class="dato" style="text-align: center;"><span class="resaltado">Cliente:</span> <strong>{{ $nombreCliente ?? '' }}</strong></div>
    <div class="dato" style="text-align: center;"><span class="resaltado">DNI:</span> <strong>{{ $dniCliente ?? '' }}</strong></div>
    <div class="dato" style="text-align: center;"><span class="resaltado">Servicio:</span> <strong>{{ $nombreServicio ?? '' }}</strong></div>
    <div class="dato"><span class="resaltado">Cantidad:</span> {{ $cantidad ?? '' }}</div>
    <div class="dato"><span class="resaltado">Precio unitario:</span> ${{ number_format($precioUnitario ?? 0, 2) }}</div>
    <div class="dato"><span class="resaltado">Forma de pago:</span> {{ $forma_pago ?? '' }}</div>
    @if(!empty($forma_pago2) && !empty($importe2) && $importe2 > 0)
        <div class="dato"><span class="resaltado">Forma de pago 2:</span> {{ $forma_pago2 }} - ${{ number_format($importe2, 2) }}</div>
    @endif
    <div class="total">Total pagado: ${{ number_format(($importe ?? ($importe1 ?? 0)) + ($importe2 ?? 0), 2) }}</div>
    <div class="dato"><span class="resaltado">Fecha:</span> {{ $fechaPago ?? (date('d/m/Y H:i')) }}</div>
    @if(!empty($comentario))
        <div class="dato"><span class="resaltado">Comentario:</span> {{ $comentario }}</div>
    @endif
    <div class="footer">Gracias por su pago.</div>
</body>
</html>

<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Pagos;
use App\Models\Empresa;
use App\Services\WhatsAppService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Exception;

class EnviarComprobantesWhatsapp extends Component
{
    public $pagoId;
    public $idServicioPagar;

    public $telefono = '';
    public $pago;
    public $cliente;
    public $servicio;
    public $empresa;

    public $loading = false;
    public $successMessage = '';
    public $errorMessage = '';

    public $instanciaWS;
    public $tokenWS;


    protected $rules = [
        'telefono' => 'required|string|min:6|max:25',
    ];

    protected $messages = [
        'telefono.required' => 'El número de WhatsApp es obligatorio',
        'telefono.min' => 'El número de WhatsApp es demasiado corto',
        'telefono.max' => 'El número de WhatsApp es demasiado largo',
    ];

    public function mount($pagoId, $idServicioPagar)
    {
        $this->pagoId = $pagoId;
        $this->idServicioPagar = $idServicioPagar;
        $this->cargarPago();

        $this->telefono = $this->cliente?->telefono ?? '';
        $this->instanciaWS = $this->empresa?->instanciaWS ?? env('WHATSAPP_INSTANCE_ID', null);
        $this->tokenWS = $this->empresa?->tokenWS ?? env('WHATSAPP_API_KEY', null);
    }

    public function cargarPago()
    {
        $this->pago = Pagos::with(['servicioPagar.cliente', 'servicioPagar.servicio', 'usuario', 'formaPago', 'formaPago2'])
            ->find($this->pagoId);

        if (!$this->pago) {
            $this->errorMessage = 'Pago no encontrado';
            return;
        }

        $this->cliente = $this->pago->servicioPagar?->cliente;
        $this->servicio = $this->pago->servicioPagar?->servicio;
        $this->empresa = Empresa::find(Auth::user()->empresa_id);
    }

    public function enviarComprobantes()
    {
        $this->validate();

        $this->loading = true;
        $this->successMessage = '';
        $this->errorMessage = '';

        try {
            if (!$this->pago) {
                throw new Exception('Pago no encontrado');
            }

            if (!$this->empresa) {
                throw new Exception('Empresa no encontrada');
            }

            $datosRecibo = $this->obtenerDatosRecibo();
            if (!$datosRecibo) {
                throw new Exception('No se pudieron obtener los datos del recibo');
            }

            $whatsappService = app()->make(WhatsAppService::class, [

            'instanciaWS' => $this->instanciaWS ?? null,
            'tokenWS' => $this->tokenWS ?? null,

            ]);

            $reciboBase64 = $this->generarReciboA4Base64($datosRecibo, $this->empresa);
            $mensajeRecibo = $this->construirMensaje($datosRecibo);

            $respuestaRecibo = $whatsappService->sendDocument(
                $this->telefono,
                '',
                $this->nombreArchivoRecibo($datosRecibo),
                $mensajeRecibo,
                [],
                $reciboBase64
            );

            if (!($respuestaRecibo['success'] ?? false)) {
                throw new Exception($respuestaRecibo['message'] ?? 'Error al enviar el recibo');
            }

            $facturaEnviada = false;
            if ($this->pago->tieneFacturaAfip()) {
                $datosFactura = $this->obtenerDatosFactura();
                if (!$datosFactura) {
                    throw new Exception('No se pudieron obtener los datos de la factura');
                }

                $facturaBase64 = $this->generarFacturaA4Base64($datosFactura, $this->empresa);
                $mensajeFactura = $this->construirMensajeFactura($datosFactura);

                $respuestaFactura = $whatsappService->sendDocument(
                    $this->telefono,
                    '',
                    $this->nombreArchivoFactura(),
                    $mensajeFactura,
                    [],
                    $facturaBase64
                );

                if (!($respuestaFactura['success'] ?? false)) {
                    throw new Exception($respuestaFactura['message'] ?? 'Error al enviar la factura');
                }

                $facturaEnviada = true;
            }

            $this->successMessage = $facturaEnviada
                ? 'Recibo y factura enviados correctamente.'
                : 'Recibo enviado correctamente.';
        } catch (Exception $e) {
            Log::error('Error enviando comprobantes por WhatsApp', [
                'pago_id' => $this->pagoId,
                'id_servicio_pagar' => $this->idServicioPagar,
                'telefono' => $this->telefono,
                'error' => $e->getMessage(),
            ]);

            $this->errorMessage = 'Error al enviar comprobantes: ' . $e->getMessage();
        } finally {
            $this->loading = false;
        }
    }

    private function obtenerDatosRecibo()
    {
        $empresaId = Auth::user()->empresa_id;

        $datos = DB::select('SELECT
                    a.*,
                    b.id as idServicioPagar,
                    c.name AS nombreUsuario,
                    d.nombre AS Servicio,
                    e.nombre AS Cliente,
                    e.id as idCliente,
                    f.nombre AS formaPago,
                    f2.nombre AS formaPago2
                FROM
                    pagos a
                    INNER JOIN servicio_pagar b ON a.id_servicio_pagar = b.id
                    INNER JOIN users c ON a.id_usuario = c.id
                    INNER JOIN servicios d ON b.servicio_id = d.id
                    INNER JOIN clientes e ON b.cliente_id = e.id
                    INNER JOIN forma_pagos f ON a.forma_pago = f.id
                    LEFT JOIN forma_pagos f2 ON a.forma_pago2 = f2.id
                    INNER JOIN cliente_empresa g ON e.id = g.cliente_id
                WHERE
                    g.empresa_id = ?
                    AND b.id = ?', [$empresaId, $this->idServicioPagar]);

        return $datos[0] ?? null;
    }

    private function obtenerDatosFactura()
    {
        $empresaId = Auth::user()->empresa_id;

        $datos = DB::select('SELECT
                    a.*,
                    b.id as idServicioPagar,
                    c.name AS nombreUsuario,
                    d.nombre AS Servicio,
                    e.nombre AS Cliente,
                    e.id as idCliente,
                    f.nombre AS formaPago,
                    f2.nombre AS formaPago2
                FROM
                    pagos a
                    INNER JOIN servicio_pagar b ON a.id_servicio_pagar = b.id
                    INNER JOIN users c ON a.id_usuario = c.id
                    INNER JOIN servicios d ON b.servicio_id = d.id
                    INNER JOIN clientes e ON b.cliente_id = e.id
                    INNER JOIN forma_pagos f ON a.forma_pago = f.id
                    LEFT JOIN forma_pagos f2 ON a.forma_pago2 = f2.id
                    INNER JOIN cliente_empresa g ON e.id = g.cliente_id
                WHERE
                    g.empresa_id = ?
                    AND a.id = ?', [$empresaId, $this->pagoId]);

        return $datos[0] ?? null;
    }

    private function generarReciboA4Base64($datos, $empresa): string
    {
        $pdf = Pdf::loadView('pdf.pagoPDF', [
            'datos' => $datos,
            'empresa' => $empresa,
        ])->setPaper('A4', 'portrait');

        return base64_encode($pdf->output());
    }

    private function generarFacturaA4Base64($datos, $empresa): string
    {
        $cliente = $this->cliente;
        $qrBase64 = $this->generarQrAfipBase64ParaPdf($this->pago, $empresa, $cliente, $datos);

        $pdf = Pdf::loadView('pdf.facturaAfip', [
            'datos' => $datos,
            'empresa' => $empresa,
            'pago' => $this->pago,
            'cliente' => $cliente,
            'qrBase64' => $qrBase64,
        ])->setPaper('A4', 'portrait');

        return base64_encode($pdf->output());
    }

    private function generarQrAfipBase64ParaPdf($pago, $empresa, $cliente, $datos): ?string
    {
        try {
            $fechaEmision = \Carbon\Carbon::parse($pago->created_at)->format('Y-m-d');
            $importeTotal = (float) (($datos->importe ?? 0) + ($datos->importe2 ?? 0));
            $dniNormalizado = $cliente?->dni ? ($cliente->dni) : null;
            $tipoDocRec = $dniNormalizado ? (strlen($dniNormalizado) === 11 ? 80 : 96) : null;

            $qrPayload = [
                'ver' => 1,
                'fecha' => $fechaEmision,
                'cuit' => (int) ($empresa->cuit ?? 0),
                'ptoVta' => (int) ($pago->afip_punto_venta ?? 0),
                'tipoCmp' => (int) ($pago->afip_tipo_comprobante ?? 0),
                'nroCmp' => (int) ($pago->afip_numero_comprobante ?? 0),
                'importe' => round($importeTotal, 2),
                'moneda' => 'PES',
                'ctz' => 1,
                'tipoCodAut' => 'E',
                'codAut' => (int) $pago->afip_cae,
            ];

            if ($tipoDocRec && $dniNormalizado) {
                $qrPayload['tipoDocRec'] = (int) $tipoDocRec;
                $qrPayload['nroDocRec'] = (int) $dniNormalizado;
            }

            $qrJson = json_encode($qrPayload, JSON_UNESCAPED_SLASHES);
            $qrBase64Data = base64_encode($qrJson);
            $qrUrl = 'https://www.arca.gob.ar/fe/qr/?p=' . $qrBase64Data;

            $writer = new PngWriter();
            $qrCode = new QrCode($qrUrl);
            $result = $writer->write($qrCode);

            return $result->getDataUri();
        } catch (Exception $e) {
            Log::error('No se pudo generar QR AFIP para PDF', [
                'pago_id' => $pago->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function construirMensaje($datos): string
    {
        $total = number_format(($datos->importe ?? 0) + ($datos->importe2 ?? 0), 2);
        $fecha = \Carbon\Carbon::parse($datos->created_at)->format('d/m/Y H:i');

        return "Recibo A4\nServicio: {$datos->Servicio}\nCliente: {$datos->Cliente}\nPago ID: {$this->pagoId}\nServicio ID: {$this->idServicioPagar}\nTotal: $$total\nFecha: {$fecha}";
    }

    private function construirMensajeFactura($datos): string
    {
        $total = number_format(($datos->importe ?? 0) + ($datos->importe2 ?? 0), 2);
        $fecha = \Carbon\Carbon::parse($datos->created_at)->format('d/m/Y H:i');

        return "Factura AFIP A4\nServicio: {$datos->Servicio}\nCliente: {$datos->Cliente}\nPago ID: {$this->pagoId}\nServicio ID: {$this->idServicioPagar}\nTotal: $$total\nFecha: {$fecha}";
    }

    private function nombreArchivoRecibo($datos): string
    {
        $cliente = preg_replace('/[^A-Za-z0-9\-_ ]/', '', $datos->Cliente ?? 'Cliente');
        $servicio = preg_replace('/[^A-Za-z0-9\-_ ]/', '', $datos->Servicio ?? 'Servicio');

        return trim($cliente . ' ' . $servicio) . '.pdf';
    }

    private function nombreArchivoFactura(): string
    {
        return 'Factura_AFIP_' . ($this->pago->afip_cae ?? $this->pagoId) . '.pdf';
    }

    public function render()
    {
        return view('livewire.enviar-comprobantes-whatsapp');
    }
}

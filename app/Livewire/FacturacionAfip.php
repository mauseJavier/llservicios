<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Pagos;
use App\Models\Empresa;
use App\Services\AfipService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class FacturacionAfip extends Component
{
    // Propiedad del pago
    public $pagoId;
    public $pago;
    
    // Propiedades del modal
    public $showFacturarModal = false;
    public $puntoVenta = 1;
    public $tipoComprobante = null;
    public $condicionIvaReceptorId = 5;
    public $tipoDocumentoReceptor = null;
    
    
    // Estado
    public $loading = false;
    public $empresa = null;
    public $certificadosValidos = false;
    public $certificadosError = '';
    
    // Mensajes
    public $successMessage = '';
    public $errorMessage = '';
    
    // Datos de factura generada
    public $facturaGenerada = false;
    public $urlPdfFactura = '';

    protected $rules = [
        'puntoVenta' => 'required|integer|min:1',
        'tipoComprobante' => 'required|integer|in:1,6,11,3,8,13',
        'condicionIvaReceptorId' => 'required|integer|in:1,6,13,16,4,5,7,8,9,10,15',
        'tipoDocumentoReceptor' => 'required|integer|in:80,96,99',
    ];

    protected $messages = [
        'puntoVenta.required' => 'El punto de venta es obligatorio',
        'puntoVenta.min' => 'El punto de venta debe ser mayor a 0',
        'tipoComprobante.required' => 'El tipo de comprobante es obligatorio',
        'tipoComprobante.in' => 'Tipo de comprobante no válido',
        'condicionIvaReceptorId.required' => 'La condición frente al IVA es obligatoria',
        'condicionIvaReceptorId.in' => 'Condición frente al IVA no válida',
        'tipoDocumentoReceptor.required' => 'El tipo de documento del receptor es obligatorio',
        'tipoDocumentoReceptor.in' => 'Tipo de documento del receptor no válido',
    ];

    public function mount($pagoId)
    {
        $this->pagoId = $pagoId;
        $this->cargarPago();
        $this->cargarEmpresaConfig();
        $this->verificarCertificados();
        
        // Cargar configuración por defecto
        $user = Auth::user();
        $this->puntoVenta = $user?->afip_punto_venta ?? config('afip.default_punto_venta', 1);
        // $this->tipoComprobante = config('afip.default_tipo_comprobante', 6);
        $this->condicionIvaReceptorId = $this->pago?->servicioPagar?->cliente?->condicion_iva_id
            ?? config('afip.default_condicion_iva_receptor', 5);
        $this->tipoDocumentoReceptor = $this->pago?->servicioPagar?->cliente?->tipo_documento_id
            ?? config('afip.default_tipo_documento_receptor', 80);
    }

    /**
     * Cargar datos del pago
     */
    public function cargarPago()
    {
        $this->pago = Pagos::with(['servicioPagar.cliente', 'servicioPagar.servicio', 'usuario', 'formaPago', 'formaPago2'])
            ->find($this->pagoId);
            
        if (!$this->pago) {
            $this->errorMessage = 'Pago no encontrado';
        }
    }

    /**
     * Cargar configuración de la empresa
     */
    public function cargarEmpresaConfig()
    {
        $user = Auth::user();
        if ($user && $user->empresa_id) {
            $this->empresa = Empresa::find($user->empresa_id);
        }
    }

    /**
     * Verificar certificados AFIP
     */
    public function verificarCertificados()
    {
        try {
            if (!$this->empresa) {
                $this->certificadosValidos = false;
                $this->certificadosError = 'No se encontró configuración de empresa';
                return;
            }

            if (!$this->empresa->cuit) {
                $this->certificadosValidos = false;
                $this->certificadosError = 'La empresa no tiene CUIT configurado';
                return;
            }

            $afipService = new AfipService($this->empresa->id);
            $resultado = $afipService->verificarCertificados();
            
            $this->certificadosValidos = $resultado['success'];
            $this->certificadosError = $resultado['success'] ? '' : ($resultado['message'] ?? 'Error desconocido');
            
        } catch (Exception $e) {
            $this->certificadosValidos = false;
            $this->certificadosError = 'Error al verificar certificados: ' . $e->getMessage();
            Log::error('Error verificando certificados AFIP', [
                'empresa_id' => $this->empresa->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Abrir modal de facturación
     */
    public function openFacturarModal()
    {
        if (!$this->certificadosValidos) {
            $this->errorMessage = 'No se puede facturar. ' . $this->certificadosError;
            return;
        }

        if ($this->pago->tieneFacturaAfip()) {
            $this->errorMessage = 'Este pago ya tiene una factura AFIP generada';
            return;
        }

        $this->showFacturarModal = true;
        $this->errorMessage = '';
        $this->successMessage = '';
    }

    /**
     * Cerrar modal
     */
    public function cerrarModal()
    {
        $this->showFacturarModal = false;
        $this->errorMessage = '';
    }

    /**
     * Validar compatibilidad entre tipo de comprobante y condición IVA
     * 
     * @throws Exception
     */
    protected function validarCompatibilidadComprobanteIVA()
    {
        // Tipos de comprobante A (requieren que el receptor sea Responsable Inscripto)
        $tiposA = [1, 2, 3, 4]; // Factura A, Nota Débito A, Nota Crédito A, Recibo A
        
        // Tipos de comprobante B (requieren consumidor final o monotributista)
        $tiposB = [6, 7, 8, 9]; // Factura B, Nota Débito B, Nota Crédito B, Recibo B
        
        // Tipos de comprobante C (exportación o casos especiales)
        $tiposC = [11, 12, 13]; // Factura C, Nota Débito C, Nota Crédito C
        
        // Condiciones IVA que requieren Factura A
        $requierenFacturaA = [1, 6]; // Responsable Inscripto, Monotributo
        
        // Validación: Si el receptor es Responsable Inscripto, debe ser factura A
        if (in_array($this->condicionIvaReceptorId, $requierenFacturaA) && !in_array($this->tipoComprobante, $tiposA)) {
            throw new Exception('Para un Responsable Inscripto o Monotributista debe emitirse Factura A');
        }
        
        // Validación: Si es Consumidor Final (5), no puede ser factura A
        if ($this->condicionIvaReceptorId == 5 && in_array($this->tipoComprobante, $tiposA)) {
            throw new Exception('Para un Consumidor Final no se puede emitir Factura A. Use Factura B o C');
        }
    }

    /**
     * Generar factura AFIP
     */
    public function generarFactura()
    {
        $this->validate();
        
        $this->loading = true;
        $this->errorMessage = '';
        $this->successMessage = '';
        
        try {
            if (!$this->certificadosValidos) {
                throw new Exception('Certificados AFIP no válidos');
            }

            if ($this->pago->tieneFacturaAfip()) {
                throw new Exception('Este pago ya tiene una factura AFIP');
            }

            // Validar compatibilidad entre tipo de comprobante y condición IVA
            $this->validarCompatibilidadComprobanteIVA();

            // Crear servicio AFIP
            $afipService = new AfipService($this->empresa->id);
            
            // Generar factura
            $resultado = $afipService->crearFacturaDesdePago(
                $this->pago,
                $this->puntoVenta,
                $this->tipoComprobante,
                $this->condicionIvaReceptorId,
                $this->tipoDocumentoReceptor
            );

            if (!$resultado['success']) {
                throw new Exception($resultado['error'] ?? 'Error al generar factura');
            }

            // Actualizar pago con datos de AFIP
            $this->pago->update([
                'afip_cae' => $resultado['cae'],
                'afip_cae_vencimiento' => $resultado['cae_vencimiento'],
                'afip_numero_comprobante' => $resultado['numero_comprobante'],
                'afip_tipo_comprobante' => $this->tipoComprobante,
                'afip_punto_venta' => $this->puntoVenta,
            ]);

            // Recargar pago
            $this->cargarPago();

            // Cerrar modal
            $this->showFacturarModal = false;
            
            // Marcar como factura generada
            $this->facturaGenerada = true;
            $this->urlPdfFactura = route('FacturaAfipPDF', ['pagoId' => $this->pagoId]);
            
            $this->successMessage = '¡Factura AFIP generada exitosamente! CAE: ' . $resultado['cae'];
            
            if(env('APP_ENV') === 'local' ) {
                Log::info('Factura AFIP generada', [
                    'pago_id' => $this->pagoId,
                    'cae' => $resultado['cae'],
                    'numero_comprobante' => $resultado['numero_comprobante']
                ]);
            }


            // Disparar evento de JavaScript para abrir PDF
            $this->dispatch('factura-generada', url: $this->urlPdfFactura);
            
        } catch (Exception $e) {

            $this->errorMessage = 'Error al generar factura: ' .  $e->getMessage();
            Log::error('Error generando factura AFIP', [
                'pago_id' => $this->pagoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            $this->loading = false;
        }
    }

    /**
     * Descargar factura PDF
     */
    public function descargarFacturaPDF()
    {
        if (!$this->pago->tieneFacturaAfip()) {
            $this->errorMessage = 'Este pago no tiene factura AFIP generada';
            return;
        }

        return redirect()->route('FacturaAfipPDF', ['pagoId' => $this->pagoId]);
    }

    public function descargarFacturaPDF80()
    {
        if (!$this->pago->tieneFacturaAfip()) {
            $this->errorMessage = 'Este pago no tiene factura AFIP generada';
            return;
        }

        // pasar a la ruta el parametro tamañoPapel == '80MM'
        return redirect()->route('FacturaAfipPDF', ['pagoId' => $this->pagoId, 'tamañoPapel' => '80MM', 'tipoDocumentoReceptor' => $this->tipoDocumentoReceptor]);

        // return redirect()->route('FacturaAfipPDF80', ['pagoId' => $this->pagoId]);
    }

    public function render()
    {
        $tiposComprobantes = AfipService::tiposComprobantesComunes($this->empresa?->condicion_iva_id);
        $tiposContribuyentes = AfipService::tiposContribuyentes();

        $tiposDocumentosComunes = AfipService::tiposDocumentosComunes();
        
        return view('livewire.facturacion-afip', [
            'tiposComprobantes' => $tiposComprobantes,
            'tiposContribuyentes' => $tiposContribuyentes,
            'tiposDocumentosComunes' => $tiposDocumentosComunes,
        ]);
    }
}

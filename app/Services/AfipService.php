<?php

namespace App\Services;

use Afip;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AfipService
{
    protected $afip;
    protected $empresa;

    /**
     * Constructor del servicio AFIP
     * 
     * @param int $empresaId ID de la empresa
     * @param bool $inicializar Si debe inicializar AFIP al construir
     * @throws Exception Si la empresa no tiene configuración de AFIP
     */
    public function __construct($empresaId = null, $inicializar = true)
    {
        if ($empresaId) {
            $this->empresa = \App\Models\Empresa::findOrFail($empresaId);

            if ($inicializar) {
                $this->inicializarAfip();
            }
        }
    }

    /**
     * Inicializar la instancia de AFIP con las credenciales de la empresa
     */
    protected function inicializarAfip()
    {
        // Validar que la empresa tenga CUIT
        if (empty($this->empresa->cuit)) {
            throw new Exception('La empresa no tiene CUIT configurado');
        }

        // Rutas de certificados (ajustar según tu estructura)
        $certPath = storage_path('app/afip/empresas/' . $this->empresa->cuit . '/certificate.crt');
        $keyPath = storage_path('app/afip/empresas/' . $this->empresa->cuit . '/private.key');

        // Validar que existan los certificados
        if (!file_exists($certPath) || !file_exists($keyPath)) {
            throw new Exception('Certificados de AFIP no encontrados para la empresa ' . $this->empresa->cuit);
        }

        $this->afip = new Afip([
            'CUIT' => $this->empresa->cuit,
            'production' => config('afip.production', false),
            'access_token' => config('afip.access_token'),
            'cert' => file_get_contents($certPath),
            'key' => file_get_contents($keyPath),
            'ta_folder' => storage_path('app/afip/empresas/' . $this->empresa->cuit . '/ta'),
            'res_folder' => storage_path('app/afip/empresas/' . $this->empresa->cuit . '/res')
        ]);
    }

    /**
     * Crear una factura electrónica
     * 
     * @param array $data Datos de la factura
     * @return array Respuesta de AFIP con el comprobante generado
     */
    public function crearFactura(array $data)
    {
        try {
            $normalizeImporte = static fn ($value) => round((float) $value, 2);

            // Obtener el último número de comprobante
            $ultimoComprobante = $this->afip->ElectronicBilling->GetLastVoucher(
                $data['PtoVta'], 
                $data['CbteTipo']
            );

            $siguienteNumero = $ultimoComprobante + 1;

            // Datos del comprobante
            $facturaData = [
                'CantReg' => 1,
                'PtoVta' => $data['PtoVta'],
                'CbteTipo' => $data['CbteTipo'],
                'Concepto' => $data['Concepto'] ?? 1, // 1=Productos, 2=Servicios, 3=Productos y Servicios
                'DocTipo' => $data['DocTipo'] ?? 99, // 99=Sin identificar
                'DocNro' => $data['DocNro'] ?? 0,
                'CbteDesde' => $siguienteNumero,
                'CbteHasta' => $siguienteNumero,
                'CbteFch' => date('Ymd'),
                'ImpTotal' => $normalizeImporte($data['ImpTotal']),
                'ImpTotConc' => $normalizeImporte($data['ImpTotConc'] ?? 0),
                'ImpNeto' => $normalizeImporte($data['ImpNeto']),
                'ImpOpEx' => $normalizeImporte($data['ImpOpEx'] ?? 0),
                'ImpIVA' => $normalizeImporte($data['ImpIVA']),
                'ImpTrib' => $normalizeImporte($data['ImpTrib'] ?? 0),
                'MonId' => $data['MonId'] ?? 'PES',
                'MonCotiz' => $normalizeImporte($data['MonCotiz'] ?? 1),
                'CondicionIVAReceptorId' => $data['CondicionIVAReceptorId'] ?? 5, // 5=Consumidor Final


                
            ];

            if (in_array($facturaData['Concepto'], [2, 3], true)) {
                $hoy = date('Ymd');
                $facturaData['FchServDesde'] = $data['FchServDesde'] ?? $hoy;
                $facturaData['FchServHasta'] = $data['FchServHasta'] ?? $hoy;
                $facturaData['FchVtoPago'] = $data['FchVtoPago'] ?? date('Ymd', strtotime('+10 days'));
            }

            // Si tiene IVA, agregar el detalle (solo para comprobantes que discriminan IVA)
            if (isset($data['Iva']) && $data['ImpIVA'] > 0) {
                $facturaData['Iva'] = $data['Iva'];
            }

            // Comprobantes asociados (requerido para Notas de Crédito/Débito)
            if (isset($data['CbtesAsoc'])) {
                $facturaData['CbtesAsoc'] = $data['CbtesAsoc'];
            }

            if(env('APP_ENV') === 'local' ) {
                Log::info('Creando factura AFIP desde pago', [
                    'factura_data' => $facturaData
                ]);
            }  


            // Crear la factura
            $voucher = $this->afip->ElectronicBilling->CreateVoucher($facturaData);

            if(env('APP_ENV') === 'local' ) {
                Log::info('Factura AFIP creada exitosamente', [
                    'empresa_id' => $this->empresa->id,
                    'comprobante' => $siguienteNumero,
                    'cae' => $voucher['CAE'] ?? null
                ]);
            }

            return [
                'success' => true,
                'data' => $voucher,
                'numero_comprobante' => $siguienteNumero,
                'cae' => $voucher['CAE'] ?? null,
                'cae_vencimiento' => $voucher['CAEFchVto'] ?? null
            ];

        } catch (Exception $e) {

            
            Log::error('Error al crear factura AFIP', [
                'empresa_id' => $this->empresa->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener el último número de comprobante
     * 
     * @param int $puntoVenta Punto de venta
     * @param int $tipoComprobante Tipo de comprobante
     * @return int Último número de comprobante
     */
    public function obtenerUltimoComprobante($puntoVenta, $tipoComprobante)
    {
        try {
            return $this->afip->ElectronicBilling->GetLastVoucher($puntoVenta, $tipoComprobante);
        } catch (Exception $e) {
            Log::error('Error al obtener último comprobante AFIP', [
                'empresa_id' => $this->empresa->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Obtener tipos de comprobantes disponibles
     * 
     * @return array Lista de tipos de comprobantes
     */
    public function obtenerTiposComprobantes()
    {
        try {
            return $this->afip->ElectronicBilling->GetVoucherTypes();
        } catch (Exception $e) {
            Log::error('Error al obtener tipos de comprobantes AFIP', [
                'empresa_id' => $this->empresa->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Obtener puntos de venta disponibles
     * 
     * @return array Lista de puntos de venta
     */
    public function obtenerPuntosVenta()
    {
        try {
            return $this->afip->ElectronicBilling->GetSalesPoints();
        } catch (Exception $e) {
            Log::error('Error al obtener puntos de venta AFIP', [
                'empresa_id' => $this->empresa->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Obtener tipos de documentos disponibles
     * 
     * @return array Lista de tipos de documentos
     */
    public function obtenerTiposDocumentos()
    {
        try {
            return $this->afip->ElectronicBilling->GetDocumentTypes();
        } catch (Exception $e) {
            Log::error('Error al obtener tipos de documentos AFIP', [
                'empresa_id' => $this->empresa->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Obtener tipos de IVA disponibles
     * 
     * @return array Lista de tipos de IVA
     */
    public function obtenerTiposIva()
    {
        try {
            return $this->afip->ElectronicBilling->GetAliquotTypes();
        } catch (Exception $e) {
            Log::error('Error al obtener tipos de IVA AFIP', [
                'empresa_id' => $this->empresa->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Consultar información de un contribuyente por CUIT
     * 
     * @param string $cuit CUIT del contribuyente
     * @return array Información del contribuyente
     */
    public function consultarContribuyente($cuit)
    {
        try {
            $info = $this->afip->RegisterScopeFour->GetTaxpayerDetails($cuit);
            
            return [
                'success' => true,
                'data' => $info
            ];
        } catch (Exception $e) {
            Log::error('Error al consultar contribuyente AFIP', [
                'cuit' => $cuit,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Crear factura desde un pago del sistema
     * 
     * @param \App\Models\Pagos $pago Pago del sistema
     * @param int $puntoVenta Punto de venta
     * @param int $tipoComprobante Tipo de comprobante (6=Factura B, 1=Factura A, etc)
     * @return array Respuesta de AFIP
     */
    public function crearFacturaDesdePago($pago, $puntoVenta = 1, $tipoComprobante = 6, $condicionIvaReceptorId = null, $tipoDocumentoReceptor = null)
    {

        $normalizeImporte = static fn ($value) => round((float) $value, 2);

        $pago->load('servicioPagar.cliente');
        
        // Determinar tipo de documento del cliente
        // $dniNormalizado = \App\Helpers\DniHelper::extractDni($pago->servicioPagar->cliente->dni);
                
        $dniNormalizado = $pago->servicioPagar->cliente->dni;

        $docTipo = $tipoDocumentoReceptor ?? (strlen($dniNormalizado) === 11 ? 80 : 96); // 80=CUIT, 96=DNI
        $docNro = $dniNormalizado ?? 0;

        // Calcular montos según el tipo de comprobante
        $impTotal = $pago->importe;
        
        // Determinar si el tipo de comprobante discrimina IVA
        // Tipos A y B (1,2,3,4,6,7,8,9) discriminan IVA
        // Tipos C (11,12,13,15) NO discriminan IVA
        $tiposConIVA = [1, 2, 3, 4, 6, 7, 8, 9];
        $tiposSinIVA = [11, 12, 13, 15];
        
        $discriminaIVA = in_array($tipoComprobante, $tiposConIVA);
        
        if ($discriminaIVA) {
            // Factura A o B: Calcular IVA (asumiendo 21%)
            $impNeto = round($impTotal / 1.21, 2);
            $impIva = $impTotal - $impNeto;
            $ivaArray = [
                [
                    'Id' => 5, // 21%
                    'BaseImp' => $normalizeImporte($impNeto),
                    'Importe' => $normalizeImporte($impIva)
                ]
            ];
        } else {
            // Factura C: No discriminar IVA
            $impNeto = $impTotal;
            $impIva = 0;
            $ivaArray = null; // No enviar array de IVA para tipo C
        }

        $facturaData = [
            'PtoVta' => $puntoVenta,
            'CbteTipo' => $tipoComprobante,
            'Concepto' => 2, // Servicios
            'DocTipo' => $docTipo,
            'DocNro' => $docNro,
            'ImpTotal' => $normalizeImporte($impTotal),
            'ImpNeto' => $normalizeImporte($impNeto),
            'ImpIVA' => $normalizeImporte($impIva),
            'ImpTotConc' => 0,
            'ImpOpEx' => 0,
            'ImpTrib' => 0,
            'MonId' => 'PES',
            'MonCotiz' => 1,
            'CondicionIVAReceptorId' => $condicionIvaReceptorId
                ?? $pago->servicioPagar->cliente->condicion_iva_id
                ?? 5, // 5=Consumidor Final
        ];
        
        // Solo agregar array de IVA si el comprobante lo requiere
        if ($ivaArray !== null) {
            $facturaData['Iva'] = $ivaArray;
        }
 

        return $this->crearFactura($facturaData);
    }

    /**
     * Crear una Nota de Crédito AFIP a partir de un pago ya facturado
     *
     * Mapeo automático: Factura A (1) → NC A (3), Factura B (6) → NC B (8), Factura C (11) → NC C (13)
     *
     * @param \App\Models\Pagos $pagoOriginal Pago con CAE ya generado
     * @return array Respuesta de AFIP con success, cae, numero_comprobante, tipo_comprobante
     */
    public function crearNotaCredito($pagoOriginal)
    {
        $normalizeImporte = static fn ($value) => round((float) $value, 2);

        $mapaNc = [1 => 3, 6 => 8, 11 => 13];
        $tipoOriginal = (int) $pagoOriginal->afip_tipo_comprobante;

        if (!array_key_exists($tipoOriginal, $mapaNc)) {
            return [
                'success' => false,
                'error'   => 'No se puede emitir NC para el tipo de comprobante ' . $tipoOriginal,
            ];
        }

        $tipoNc     = $mapaNc[$tipoOriginal];
        $puntoVenta = (int) $pagoOriginal->afip_punto_venta;
        $impTotal   = $pagoOriginal->total; // importe + importe2

        // Misma lógica IVA que crearFacturaDesdePago
        $tiposConIVA  = [1, 2, 3, 4, 6, 7, 8, 9];
        $discriminaIVA = in_array($tipoNc, $tiposConIVA);

        if ($discriminaIVA) {
            $impNeto  = round($impTotal / 1.21, 2);
            $impIva   = $normalizeImporte($impTotal - $impNeto);
            $ivaArray = [[
                'Id'      => 5,
                'BaseImp' => $normalizeImporte($impNeto),
                'Importe' => $impIva,
            ]];
        } else {
            $impNeto  = $impTotal;
            $impIva   = 0;
            $ivaArray = null;
        }

        $pagoOriginal->loadMissing('servicioPagar.cliente');
        $dniNorm = $pagoOriginal->servicioPagar->cliente->dni ?? '0';
        $docTipo = (strlen((string) $dniNorm) === 11) ? 80 : 96;

        $facturaData = [
            'PtoVta'                 => $puntoVenta,
            'CbteTipo'               => $tipoNc,
            'Concepto'               => 2, // Servicios
            'DocTipo'                => $docTipo,
            'DocNro'                 => $dniNorm,
            'ImpTotal'               => $normalizeImporte($impTotal),
            'ImpNeto'                => $normalizeImporte($impNeto),
            'ImpIVA'                 => $normalizeImporte($impIva),
            'ImpTotConc'             => 0,
            'ImpOpEx'                => 0,
            'ImpTrib'                => 0,
            'MonId'                  => 'PES',
            'MonCotiz'               => 1,
            'CondicionIVAReceptorId' => $pagoOriginal->servicioPagar->cliente->condicion_iva_id ?? 5,
            'CbtesAsoc'              => [[
                'Tipo'   => $tipoOriginal,
                'PtoVta' => $puntoVenta,
                'Nro'    => (int) $pagoOriginal->afip_numero_comprobante,
                'Cuit'   => (int) $this->empresa->cuit,
            ]],
        ];

        if ($ivaArray !== null) {
            $facturaData['Iva'] = $ivaArray;
        }

        $resultado = $this->crearFactura($facturaData);

        if ($resultado['success']) {
            $resultado['tipo_comprobante'] = $tipoNc;
        }

        return $resultado;
    }

    /**
     * Tipos de contribuyentes (Condición frente al IVA)
     *
     * @return array<int, array{Desc:string,Cmp_Clase:string}>
     */
    public static function tiposContribuyentes()
    {
        return [
            1 => ['Desc' => 'IVA Responsable Inscripto', 'Cmp_Clase' => 'A/M/C'],
            6 => ['Desc' => 'Responsable Monotributo', 'Cmp_Clase' => 'A/M/C'],
            13 => ['Desc' => 'Monotributista Social', 'Cmp_Clase' => 'A/M/C'],
            16 => ['Desc' => 'Monotributo Trabajador Independiente Promovido', 'Cmp_Clase' => 'A/M/C'],
            4 => ['Desc' => 'IVA Sujeto Exento', 'Cmp_Clase' => 'B/C'],
            5 => ['Desc' => 'Consumidor Final', 'Cmp_Clase' => 'B/C'],
            7 => ['Desc' => 'Sujeto No Categorizado', 'Cmp_Clase' => 'B/C'],
            8 => ['Desc' => 'Proveedor del Exterior', 'Cmp_Clase' => 'B/C'],
            9 => ['Desc' => 'Cliente del Exterior', 'Cmp_Clase' => 'B/C'],
            10 => ['Desc' => 'IVA Liberado – Ley N° 19.640', 'Cmp_Clase' => 'B/C'],
            15 => ['Desc' => 'IVA No Alcanzado', 'Cmp_Clase' => 'B/C'],
        ];
    }

    /**
     * Verificar si los certificados de la empresa son válidos
     * 
     * @return array{success:bool,message?:string}
     */
    public function verificarCertificados()
    {
        try {
            $certPath = storage_path('app/afip/empresas/' . $this->empresa->cuit . '/certificate.crt');
            $keyPath = storage_path('app/afip/empresas/' . $this->empresa->cuit . '/private.key');

            if (!file_exists($certPath) || !file_exists($keyPath)) {
                return [
                    'success' => false,
                    'message' => 'No se encontraron certificados de AFIP'
                ];
            }

            if (!isset($this->afip)) {
                $this->inicializarAfip();
            }

            // Validar certificados consultando estado del servicio
            $this->afip->ElectronicBilling->GetServerStatus();

            return [
                'success' => true
            ];

        } catch (Exception $e) {
            Log::error('Error al verificar certificados AFIP', [
                'empresa_id' => $this->empresa->id,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Generar certificados de AFIP en entorno de desarrollo (homologación)
     *
     * @param array $data Debe incluir: cuit, username, password, alias
     * @return array Resultado con success, cert_path, key_path o error
     */
    public function generarCertificadosDesarrollo(array $data)
    {
        return $this->generarCertificadosAutomation(config('afip.automation_dev', 'create-cert-dev'), $data);
    }

    /**
     * Generar certificados de AFIP en entorno de producción
     *
     * @param array $data Debe incluir: cuit, username, password, alias
     * @return array Resultado con success, cert_path, key_path o error
     */
    public function generarCertificadosProduccion(array $data)
    {
        return $this->generarCertificadosAutomation(config('afip.automation_prod', 'create-cert-prod'), $data);
    }

    /**
     * Ejecuta la automatización de AFIP y persiste cert/key en storage
     *
     * @param string $automationName Nombre de la automatización
     * @param array $data Datos requeridos por la automatización
     * @return array
     */
    protected function generarCertificadosAutomation($automationName, array $data)
    {
        try {
            $accessToken = config('afip.access_token');

            if (empty($accessToken)) {
                throw new Exception('AFIP access token no configurado');
            }

            foreach (['cuit', 'username', 'password', 'alias'] as $campo) {
                if (empty($data[$campo])) {
                    throw new Exception('Falta el campo requerido: ' . $campo);
                }
            }

            if(env('APP_ENV') === 'local' ) {

                Log::info('Generando certificados AFIP (automatización)', [
                    'automation' => $automationName,
                    'data' => $data
                ]);


            }

            $afip = new Afip([
                'access_token' => $accessToken
            ]);

            $response = $afip->CreateAutomation($automationName, $data, true);

            if(env('APP_ENV') === 'local' ) {
                
                Log::info('Respuesta de automatización AFIP', [
                    'response' => $response
                ]); 

            }

            $cert = $response->data->cert ?? null;
            $key = $response->data->key ?? null;

            if (empty($cert) || empty($key)) {
                throw new Exception('No se recibieron cert/key en la respuesta de AFIP');
            }

            $empresaPath = storage_path('app/afip/empresas/' . $data['cuit']);

            if (!file_exists($empresaPath)) {
                mkdir($empresaPath, 0755, true);
                mkdir($empresaPath . '/ta', 0755, true);
                mkdir($empresaPath . '/res', 0755, true);
            }

            $certPath = $empresaPath . '/certificate.crt';
            $keyPath = $empresaPath . '/private.key';

            file_put_contents($certPath, $cert);
            file_put_contents($keyPath, $key);

            Log::info('Certificados AFIP generados', [
                'cuit' => $data['cuit'],
                'automation' => $automationName
            ]);

            return [
                'success' => true,
                'cert_path' => $certPath,
                'key_path' => $keyPath
            ];
        } catch (Exception $e) {
            Log::error('Error al generar certificados AFIP', [
                'cuit' => $data['cuit'] ?? null,
                'automation' => $automationName,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Tipos de comprobantes más comunes
     * 
     * @param int|null $condicionIvaId Condición IVA de la empresa (1=Responsable Inscripto, 6=Monotributo, etc.)
     * @return array Lista filtrada de tipos de comprobantes disponibles según la condición IVA
     */
    public static function tiposComprobantesComunes($condicionIvaId = null)
    {
        $todosLosComprobantes = [
            1 => 'Factura A',
            6 => 'Factura B',
            11 => 'Factura C',
            3 => 'Nota de Crédito A',
            8 => 'Nota de Crédito B',
            13 => 'Nota de Crédito C',
            2 => 'Nota de Débito A',
            7 => 'Nota de Débito B',
            12 => 'Nota de Débito C',
            4 => 'Recibo A',
            9 => 'Recibo B',
            15 => 'Recibo C'
        ];

        // Si no se especifica condición IVA, devolver todos
        if ($condicionIvaId === null) {
            return $todosLosComprobantes;
        }

        // Filtrar según condición IVA de la empresa
        switch ($condicionIvaId) {
            case 1: // Responsable Inscripto
                // Puede emitir Facturas A, Notas de Crédito A, Notas de Débito A, Recibos A
                return array_intersect_key($todosLosComprobantes, array_flip([1, 3, 2, 4,6,7,8,9]));
            
            case 6: // Monotributo
            case 13: // Monotributista Social
            case 16: // Monotributo Trabajador Independiente Promovido
                // Puede emitir Facturas B/C, Notas de Crédito B/C, Notas de Débito B/C, Recibos B/C
                return array_intersect_key($todosLosComprobantes, array_flip([ 11, 13,  12, 15]));
            
            case 4: // IVA Sujeto Exento
            case 5: // Consumidor Final (generalmente no emite, pero si lo hace son B o C)
            case 7: // Sujeto No Categorizado
            case 10: // IVA Liberado
            case 15: // IVA No Alcanzado
                // Puede emitir Facturas B/C, Notas de Crédito B/C, Notas de Débito B/C, Recibos B/C
                return array_intersect_key($todosLosComprobantes, array_flip([6, 11, 8, 13, 7, 12, 9, 15]));
            
            default:
                // Por defecto, devolver todos los comprobantes
                return $todosLosComprobantes;
        }
    }

    /**
     * Tipos de documento más comunes
     */
    public static function tiposDocumentosComunes()
    {
        return [
            80 => 'CUIT',
            86 => 'CUIL',
            87 => 'CDI',
            96 => 'DNI',
            99 => 'Sin identificar'
        ];
    }
}

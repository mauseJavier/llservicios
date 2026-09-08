<?php

namespace App\Services;

use Mause\LaravelArca\Facades\ArcaWsaa;
use Mause\LaravelArca\Facades\ArcaWsfev1;
use Mause\LaravelArca\Facades\ArcaWsPadron;
use Exception;
use Illuminate\Support\Facades\Log;

class AfipService
{
    protected $empresa;

    /**
     * Constructor del servicio AFIP → ARCA
     * 
     * @param int $empresaId ID de la empresa
     * @param bool $inicializar Si debe inicializar/validar certificados
     * @throws Exception Si la empresa no tiene configuración
     */
    public function __construct($empresaId = null, $inicializar = true)
    {
        if ($empresaId) {
            $this->empresa = \App\Models\Empresa::findOrFail($empresaId);

            if ($inicializar) {
                $this->validarCertificados();
            }
        }
    }

    /**
     * Validar que existan certificados ARCA para la empresa
     */
    protected function validarCertificados()
    {
        $this->resolveCredentialPaths();
    }

    /**
     * Resuelve paths de credenciales soportando CUIT con y sin guiones.
     *
     * @return array{0:string,1:string}
     */
    protected function resolveCredentialPaths(): array
    {
        if (empty($this->empresa->cuit)) {
            throw new Exception('La empresa no tiene CUIT configurado');
        }

        $cuitRaw = (string) $this->empresa->cuit;
        $cuitNormalized = preg_replace('/\D+/', '', $cuitRaw) ?: '';

        $candidates = array_values(array_unique([$cuitRaw, $cuitNormalized]));

        foreach ($candidates as $cuitCandidate) {
            if ($cuitCandidate === '') {
                continue;
            }

            $certPath = $this->resolveConfiguredCredentialPath(
                'arca.cert_path_pattern',
                storage_path('app/public/%s/cert.crt'),
                $cuitCandidate
            );

            $keyPath = $this->resolveConfiguredCredentialPath(
                'arca.key_path_pattern',
                storage_path('app/public/%s/key.key'),
                $cuitCandidate
            );

            // Log para verificar las rutas que se están intentando usar
            Log::info('Verificando certificados en:', ['certPath' => $certPath, 'keyPath' => $keyPath]);

            if (file_exists($certPath) && file_exists($keyPath)) {
                // Validar que los archivos no estén vacíos
                if (trim((string) @file_get_contents($certPath)) === '') {
                    throw new Exception('El archivo cert.crt está vacío: ' . $certPath);
                }

                if (trim((string) @file_get_contents($keyPath)) === '') {
                    throw new Exception('El archivo key.key está vacío: ' . $keyPath);
                }

                return [$certPath, $keyPath];
            }

            // Log si los archivos no existen
            if (!file_exists($certPath)) {
                Log::warning('El archivo cert.crt no existe en la ruta esperada', ['path' => $certPath]);
            }

            if (!file_exists($keyPath)) {
                Log::warning('El archivo key.key no existe en la ruta esperada', ['path' => $keyPath]);
            }
        }

        throw new Exception('Certificados ARCA no encontrados para CUIT ' . $this->empresa->cuit);
    }

    /**
     * Resuelve una ruta de credencial desde config/env y la devuelve absoluta.
     */
    protected function resolveConfiguredCredentialPath(string $configKey, string $defaultPattern, string $cuit): string
    {
        $pattern = (string) config($configKey, $defaultPattern);
        $resolved = str_contains($pattern, '%s') ? sprintf($pattern, $cuit) : $pattern;

        return $this->toAbsolutePath($resolved);
    }

    /**
     * Convierte una ruta relativa del proyecto a ruta absoluta.
     */
    protected function toAbsolutePath(string $path): string
    {
        if ($path === '') {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return $path;
        }

        // Soporte para rutas tipo C:\... en entornos Windows.
        if (preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }

    /**
     * Resuelve el path del CSR usando la misma base configurada para key_path_pattern.
     */
    protected function resolveConfiguredCsrPath(string $cuit): string
    {
        $keyPath = $this->resolveConfiguredCredentialPath(
            'arca.key_path_pattern',
            storage_path('app/public/%s/key.key'),
            $cuit
        );

        return dirname($keyPath) . DIRECTORY_SEPARATOR . 'request.csr';
    }

    /**
     * Asegura que cert/key también existan en el path normalizado que usa la librería ARCA.
     */
    protected function syncCredentialsToNormalizedPath(string $sourceCertPath, string $sourceKeyPath): void
    {
        $cuitNormalized = preg_replace('/\D+/', '', (string) $this->empresa->cuit) ?: '';

        if ($cuitNormalized === '') {
            return;
        }

        $targetCertPath = $this->resolveConfiguredCredentialPath(
            'arca.cert_path_pattern',
            storage_path('app/public/%s/cert.crt'),
            $cuitNormalized
        );

        $targetKeyPath = $this->resolveConfiguredCredentialPath(
            'arca.key_path_pattern',
            storage_path('app/public/%s/key.key'),
            $cuitNormalized
        );

        $targetCertDir = dirname($targetCertPath);
        $targetKeyDir = dirname($targetKeyPath);

        if (!is_dir($targetCertDir)) {
            @mkdir($targetCertDir, 0775, true);
        }

        if (!is_dir($targetKeyDir)) {
            @mkdir($targetKeyDir, 0775, true);
        }

        $sourceCertRealPath = realpath($sourceCertPath) ?: $sourceCertPath;
        $targetCertRealPath = realpath($targetCertPath) ?: $targetCertPath;

        if ($sourceCertRealPath !== $targetCertRealPath && is_readable($sourceCertPath)) {
            @copy($sourceCertPath, $targetCertPath);
        }

        $sourceKeyRealPath = realpath($sourceKeyPath) ?: $sourceKeyPath;
        $targetKeyRealPath = realpath($targetKeyPath) ?: $targetKeyPath;

        if ($sourceKeyRealPath !== $targetKeyRealPath && is_readable($sourceKeyPath)) {
            @copy($sourceKeyPath, $targetKeyPath);
        }
    }

    /**
     * Crear una factura electrónica
     * 
     * @param array $data Datos de la factura
     * @return array Respuesta con CAE y detalles
     */
    public function crearFactura(array $data)
    {
        try {
            $normalizeImporte = static fn ($value) => round((float) $value, 2);

            // Obtener último número autorizado
            $lastResult = ArcaWsfev1::getLastAuthorizedNumber(
                $this->empresa->cuit,
                $data['PtoVta'],
                $data['CbteTipo']
            );

            if (!$lastResult || !isset($lastResult['cbte_nro'])) {
                throw new Exception('No se pudo obtener el último número de comprobante');
            }

            $siguienteNumero = ($lastResult['cbte_nro'] ?? 0) + 1;

            // Construir estructura de factura para ARCA (FeCabReq + FeDetReq)
            $invoice = [
                'FeCabReq' => [
                    'CantReg' => 1,
                    'CbteTipo' => $data['CbteTipo'],
                    'PtoVta' => $data['PtoVta'],
                ],
                'FeDetReq' => [
                    'FECAEDetRequest' => [
                        'Concepto' => $data['Concepto'] ?? 1,
                        'DocTipo' => $data['DocTipo'] ?? 99,
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
                        'CondicionIVAReceptorId' => $data['CondicionIVAReceptorId'] ?? 5,
                    ]
                ]
            ];

            // Agregar fechas de servicio si aplica (Concepto 2 o 3)
            if (in_array($invoice['FeDetReq']['FECAEDetRequest']['Concepto'], [2, 3], true)) {
                $hoy = date('Ymd');
                $invoice['FeDetReq']['FECAEDetRequest']['FchServDesde'] = $data['FchServDesde'] ?? $hoy;
                $invoice['FeDetReq']['FECAEDetRequest']['FchServHasta'] = $data['FchServHasta'] ?? $hoy;
                $invoice['FeDetReq']['FECAEDetRequest']['FchVtoPago'] = $data['FchVtoPago'] ?? date('Ymd', strtotime('+10 days'));
            }

            // Agregar detalle de IVA si existe
            if (isset($data['Iva']) && $data['ImpIVA'] > 0) {
                $invoice['FeDetReq']['FECAEDetRequest']['Iva'] = $data['Iva'];
            }

            // Comprobantes asociados (NC/ND)
            if (isset($data['CbtesAsoc'])) {
                $invoice['FeDetReq']['FECAEDetRequest']['CbtesAsoc'] = $data['CbtesAsoc'];
            }

            if (env('APP_ENV') === 'local') {
                Log::info('Solicitando CAE a ARCA', ['empresa_id' => $this->empresa->id, 'data' => $invoice]);
            }

            // Solicitar CAE a ARCA
            $response = ArcaWsfev1::requestCae($this->empresa->cuit, $invoice);

            if (!$response || isset($response['error'])) {
                $mensaje = $response['error'] ?? 'Error desconocido al solicitar CAE';
                $mensaje = $this->mensajeErrorArca($mensaje);
                throw new Exception($mensaje);
            }

            $cae = trim((string) ($response['cae'] ?? ''));
            $caeVencimiento = trim((string) ($response['expiration'] ?? ''));

            // ARCA puede responder sin error técnico pero sin CAE (comprobante rechazado).
            if ($cae === '') {
                Log::warning('Respuesta de ARCA sin CAE', [
                    'empresa_id' => $this->empresa->id,
                    'response' => $response,
                    'invoice' => $invoice,
                ]);

                throw new Exception($this->mensajeErrorArca('ARCA no devolvió CAE para el comprobante. Revisar datos fiscales del receptor/comprobante.'));
            }

            Log::info('CAE obtenido exitosamente desde ARCA', [
                'empresa_id' => $this->empresa->id,
                'cae' => $cae,
                'numero_comprobante' => $siguienteNumero
            ]);

            return [
                'success' => true,
                'data' => $response,
                'numero_comprobante' => $siguienteNumero,
                'cae' => $cae,
                'cae_vencimiento' => $caeVencimiento
            ];

        } catch (Exception $e) {
            Log::error('Error al crear factura con ARCA', [
                'empresa_id' => $this->empresa->id,
                'error' => $e->getMessage()
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
     * @return int Último número
     */
    public function obtenerUltimoComprobante($puntoVenta, $tipoComprobante)
    {
        try {
            $result = ArcaWsfev1::getLastAuthorizedNumber(
                $this->empresa->cuit,
                $puntoVenta,
                $tipoComprobante
            );

            return $result['cbte_nro'] ?? 0;
        } catch (Exception $e) {
            Log::error('Error obtener último comprobante', [
                'empresa_id' => $this->empresa->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Obtener tipos de comprobantes disponibles
     * 
     * @return array|null
     */
    public function obtenerTiposComprobantes()
    {
        try {
            return ArcaWsfev1::getInvoiceTypes($this->empresa->cuit) ?? [];
        } catch (Exception $e) {
            Log::error('Error obtener tipos de comprobantes', [
                'empresa_id' => $this->empresa->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Obtener puntos de venta disponibles
     * 
     * @return array
     */
    public function obtenerPuntosVenta()
    {
        try {
            $ta = ArcaWsaa::requestTa($this->empresa->cuit, 'wsfe');

            if (!$ta || empty($ta['token']) || empty($ta['sign'])) {
                throw new Exception('No se pudo obtener TA de WSAA para consultar puntos de venta');
            }

            $mode = (string) config('arca.mode', 'homologation');
            $isProduction = in_array(strtolower($mode), ['production', 'produccion'], true);
            $wsfeUrl = $isProduction
                ? 'https://servicios1.afip.gov.ar/wsfev1/service.asmx'
                : 'https://wswhomo.afip.gov.ar/wsfev1/service.asmx';

            $client = new \SoapClient($wsfeUrl . '?wsdl', [
                'exceptions' => true,
                'trace' => true,
                'soap_version' => SOAP_1_1,
            ]);

            $response = $client->FEParamGetPtosVenta([
                'Auth' => [
                    'Token' => $ta['token'],
                    'Sign' => $ta['sign'],
                    'Cuit' => (int) preg_replace('/\D+/', '', (string) $this->empresa->cuit),
                ],
            ]);

            $puntosRaw = $response->FEParamGetPtosVentaResult->ResultGet->PtoVenta ?? null;

            if ($puntosRaw === null) {
                throw new Exception('ARCA no devolvió puntos de venta');
            }

            $puntos = is_array($puntosRaw) ? $puntosRaw : [$puntosRaw];

            $resultado = [];
            foreach ($puntos as $punto) {
                if (!is_object($punto) && !is_array($punto)) {
                    continue;
                }

                $numero = is_object($punto)
                    ? ($punto->Nro ?? $punto->nro ?? $punto->numero ?? $punto->id ?? null)
                    : ($punto['Nro'] ?? $punto['nro'] ?? $punto['numero'] ?? $punto['id'] ?? null);

                if ($numero === null || $numero === '') {
                    continue;
                }

                $resultado[] = [
                    'id' => (int) $numero,
                    'numero' => (int) $numero,
                    'Nro' => (int) $numero,
                    'nombre' => 'Punto de Venta ' . $numero,
                    'Nombre' => 'Punto de Venta ' . $numero,
                ];
            }

            if (empty($resultado)) {
                throw new Exception('ARCA devolvió una estructura de puntos de venta vacía o inválida');
            }

            usort($resultado, static fn (array $a, array $b) => ($a['numero'] <=> $b['numero']));

            return $resultado;
        } catch (Exception $e) {
            Log::error('Error obtener puntos de venta', [
                'empresa_id' => $this->empresa->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Obtener tipos de documentos disponibles
     * Nota: ARCA no expone listado, valores comunes:
     * 80=CUIT, 86=CUIL, 96=Documento Extranjero, 99=Sin especificar
     * 
     * @return array
     */
    public function obtenerTiposDocumentos()
    {
        try {
            return [
                ['id' => 80, 'nombre' => 'CUIT'],
                ['id' => 86, 'nombre' => 'CUIL'],
                ['id' => 96, 'nombre' => 'Documento Extranjero'],
                ['id' => 99, 'nombre' => 'Sin Especificar'],
            ];
        } catch (Exception $e) {
            Log::error('Error obtener tipos de documentos', [
                'empresa_id' => $this->empresa->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Obtener tipos de IVA disponibles
     * Nota: ARCA no expone listado, valores comunes:
     * 3=0%, 4=10.5%, 5=21%, etc.
     * 
     * @return array
     */
    public function obtenerTiposIva()
    {
        try {
            return [
                ['id' => 3, 'nombre' => '0%'],
                ['id' => 4, 'nombre' => '10.5%'],
                ['id' => 5, 'nombre' => '21%'],
                ['id' => 6, 'nombre' => '27%'],
            ];
        } catch (Exception $e) {
            Log::error('Error obtener tipos de IVA', [
                'empresa_id' => $this->empresa->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Consultar contribuyente por DNI/CUIT/CUIL (auto-detecta tipo)
     * 
     * @param string|int $identificador DNI, CUIT o CUIL
     * @return array
     */
    public function consultarContribuyente($identificador)
    {
        try {
            $result = ArcaWsPadron::consultarPadron($this->empresa->cuit, $identificador);

            if (!$result || isset($result['error'])) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Error en consulta de padrón'
                ];
            }

            return [
                'success' => true,
                'data' => $result['data'] ?? $result,
                'type' => $result['type'] ?? null
            ];
        } catch (Exception $e) {
            Log::error('Error consultarContribuyente', [
                'empresa_id' => $this->empresa->id,
                'identificador' => $identificador,
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
     * Obtener las condiciones frente al IVA del receptor habilitadas por ARCA
     * (método FEParamGetCondicionIvaReceptor, RG 5616).
     *
     * Devuelve la lista en el formato usado por la UI:
     * [id => ['Desc' => string, 'Cmp_Clase' => string]]
     * Si ARCA no responde, devuelve el listado estático de tiposContribuyentes().
     *
     * @return array<int, array{Desc:string, Cmp_Clase:string}>
     */
    public function obtenerCondicionesIvaReceptor()
    {
        try {
            $condiciones = ArcaWsfev1::getCondicionIvaReceptor($this->empresa->cuit);

            if (is_array($condiciones) && count($condiciones) > 0) {
                $normalizadas = [];
                foreach ($condiciones as $condicion) {
                    $id = (int) ($condicion['id'] ?? 0);
                    if ($id <= 0) {
                        continue;
                    }
                    $normalizadas[$id] = [
                        'Desc' => (string) ($condicion['desc'] ?? $condicion['Desc'] ?? ''),
                        'Cmp_Clase' => (string) ($condicion['cmp_clase'] ?? $condicion['Cmp_Clase'] ?? ''),
                    ];
                }

                if (count($normalizadas) > 0) {
                    return $normalizadas;
                }
            }
        } catch (Exception $e) {
            Log::warning('No se pudieron obtener condiciones IVA receptor desde ARCA', [
                'empresa_id' => $this->empresa->id,
                'error' => $e->getMessage(),
            ]);
        }

        return self::tiposContribuyentes();
    }

    /**
     * Verificar certificados y conectividad con ARCA
     * 
     * @return array
     */
    public function verificarCertificados()
    {
        try {
            [$certPath, $keyPath] = $this->resolveCredentialPaths();
            $this->syncCredentialsToNormalizedPath($certPath, $keyPath);

            log::info('Certificados encontrados para ARCA', [
                'empresa_id' => $this->empresa->id,
                'certPath' => $certPath,
                'keyPath' => $keyPath
            ]);



            // Evita falsos positivos por archivos existentes pero vacíos/corruptos.
            if (!is_readable($certPath) || trim((string) @file_get_contents($certPath)) === '') {
                return [
                    'success' => false,
                    'message' => 'El certificado cert.crt está vacío o no se puede leer'
                ];
            }

            if (!is_readable($keyPath) || trim((string) @file_get_contents($keyPath)) === '') {
                return [
                    'success' => false,
                    'message' => 'La clave key.key está vacía o no se puede leer'
                ];
            }

            // Validar WSAA. Si ARCA devuelve "TA ya valido", se considera un estado correcto.
            try {
                $ta = ArcaWsaa::requestTa($this->empresa->cuit, 'wsfe');
                if (!$ta || empty($ta['token']) || empty($ta['sign'])) {
                    return [
                        'success' => false,
                        'message' => 'No se pudo obtener TA de WSAA. Revisa certificado, clave y autorización del servicio wsfe'
                    ];
                }

                log ::info('TA obtenido de WSAA', [
                    'empresa_id' => $this->empresa->id,
                    'ta_expiration' => $ta['expiration'] ?? null
                ]);

            } catch (Exception $wsaaException) {
                $wsaaMessage = (string) $wsaaException->getMessage();
                $taVigente = stripos($wsaaMessage, 'TA valido') !== false
                    || stripos($wsaaMessage, 'TA válido') !== false
                    || stripos($wsaaMessage, 'posee un TA') !== false;

                if (!$taVigente) {
                    throw $wsaaException;
                }
            }

            // Intentar obtener tipos de comprobantes (verifica conectividad)
            $types = ArcaWsfev1::getInvoiceTypes($this->empresa->cuit);

            log::info('Tipos de comprobantes obtenidos de ARCA', [
                'empresa_id' => $this->empresa->id,
                'tipos_comprobantes' => $types
            ]);     


            if (!$types) {
                return [
                    'success' => false,
                    'message' => 'TA válido, pero WSFE no devolvió tipos de comprobantes'
                ];
            }

            return [
                'success' => true,
                'message' => 'Certificados válidos y conectividad con ARCA OK'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Generar CSR (Certificate Signing Request) para la empresa
     * Genera key.key y request.csr en storage/app/public/{cuit}/
     * 
     * @param array $data con organizationName, commonName, email, etc.
     * @return array
     */
    public function generarCertificadosDesarrollo(array $data)
    {
        try {
            $normalizedCuit = preg_replace('/\D+/', '', (string) $this->empresa->cuit) ?: (string) $this->empresa->cuit;

            $dn = [
                'organizationName' => $data['organizationName'] ?? $this->empresa->nombre,
                'commonName' => $data['commonName'] ?? 'SistemaFacturacion',
                'countryName' => 'AR',
                'stateOrProvinceName' => 'Buenos Aires',
                'emailAddress' => $data['email'] ?? 'admin@empresa.com',
            ];

            $result = ArcaWsaa::createCertificateRequest(
                $this->empresa->cuit,
                $dn,
                $data['passphrase'] ?? null
            );

            Log::info('CSR generado con ARCA', [
                'empresa_id' => $this->empresa->id,
                'cuit' => $this->empresa->cuit
            ]);

            return [
                'success' => true,
                'message' => 'CSR generado. Descárgalo y sube a ARCA para obtener cert.crt',
                'csr_path' => $this->resolveConfiguredCsrPath($normalizedCuit)
            ];
        } catch (Exception $e) {
            Log::error('Error generando CSR', [
                'empresa_id' => $this->empresa->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Alias para compatibilidad: generarCertificadosProduccion
     * En ARCA es el mismo proceso que desarrollo
     * 
     * @param array $data
     * @return array
     */
    public function generarCertificadosProduccion(array $data)
    {
        return $this->generarCertificadosDesarrollo($data);
    }

    /**
     * Helper para obtener tipos de comprobantes según condición IVA
     * 
     * Mapeo de comprobantes por condición:
     * - RI (1): Tipos A, B, M
     * - Monotributo (6): Tipo C
     * - Consumidor Final (5): Tipos B, C
     * - Exento (4): Tipos B, C
     * - Sujeto No Categorizado (7): Tipos B, C
     * 
     * @param int|null $condicionIvaId Condición frente al IVA
     * @return array
     */
    public static function tiposComprobantesComunes($condicionIvaId = null)
    {
        // Si no se especifica, devolver todos
        if ($condicionIvaId === null) {
            return [
                1 => 'Factura A',
                2 => 'Nota de Crédito A',
                4 => 'Nota de Débito A',
                6 => 'Factura B',
                3 => 'Nota de Crédito B',
                8 => 'Nota de Débito B',
                11 => 'Factura C',
                13 => 'Nota de Crédito C',
                15 => 'Nota de Débito C',
            ];
        }

        // RI (Responsable Inscripto) - Tipos A, B
        if ($condicionIvaId === 1) {
            return [
                1 => 'Factura A',
                2 => 'Nota de Crédito A',
                4 => 'Nota de Débito A',
                6 => 'Factura B',
                3 => 'Nota de Crédito B',
                8 => 'Nota de Débito B',
            ];
        }

        // Monotributo (6) - Solo Tipo C
        if ($condicionIvaId === 6) {
            return [
                11 => 'Factura C',
                13 => 'Nota de Crédito C',
                15 => 'Nota de Débito C',
            ];
        }

        // Consumidor Final (5), Exento (4), Sujeto No Categorizado (7) - Tipos B, C
        if (in_array($condicionIvaId, [4, 5, 7, 8, 9, 10, 15])) {
            return [
                6 => 'Factura B',
                3 => 'Nota de Crédito B',
                8 => 'Nota de Débito B',
                11 => 'Factura C',
                13 => 'Nota de Crédito C',
                15 => 'Nota de Débito C',
            ];
        }

        // Default: todos los tipos
        return [
            1 => 'Factura A',
            2 => 'Nota de Crédito A',
            4 => 'Nota de Débito A',
            6 => 'Factura B',
            3 => 'Nota de Crédito B',
            8 => 'Nota de Débito B',
            11 => 'Factura C',
            13 => 'Nota de Crédito C',
            15 => 'Nota de Débito C',
        ];
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

    /**
     * Traduce mensajes de error de ARCA (RG 5616) a mensajes claros en español.
     */
    protected function mensajeErrorArca(string $mensaje): string
    {
        $mensajeLimpio = trim(strip_tags($mensaje));

        // Error 10242: Condición IVA receptor obligatoria / valor inválido (RG 5616)
        if (str_contains($mensajeLimpio, '10242')
            || stripos($mensajeLimpio, 'Condicion IVA receptor') !== false
            || stripos($mensajeLimpio, 'Condición IVA receptor') !== false) {
            return 'ARCA rechazó el comprobante: la Condición frente al IVA del receptor es obligatoria o no es válida (Error 10242, RG 5616). Verificá la condición frente al IVA del cliente en su ficha.';
        }

        // Error 10245: Condición IVA receptor será obligatoria (observación)
        if (str_contains($mensajeLimpio, '10245')) {
            return 'ARCA observó el comprobante: la Condición frente al IVA del receptor será obligatoria (Error 10245, RG 5616). Verificá la condición frente al IVA del cliente.';
        }

        return $mensajeLimpio;
    }

}

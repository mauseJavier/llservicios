<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\AfipService;
use App\Models\Empresa;
use App\Models\Pagos;
use Exception;

class AfipController extends Controller
{
    /**
     * Mostrar el panel de configuración de AFIP
     */
    public function index()
    {
        $empresa = Auth::user()->empresa;
        
        // Verificar si tiene certificados configurados
        $tieneCertificados = false;
        $certificadosValidos = false;
        
        try {
            $afipService = new AfipService($empresa->id, false);
            $tieneCertificados = true;
            $resultado = $afipService->verificarCertificados();
            $certificadosValidos = $resultado['success'] ?? false;
        } catch (Exception $e) {
            $tieneCertificados = false;
        }

        $tiposComprobantes = AfipService::tiposComprobantesComunes();

        return view('afip.index', compact(
            'empresa',
            'tieneCertificados',
            'certificadosValidos',
            'tiposComprobantes'
        ));
    }

    /**
     * Subir certificados de AFIP
     */
    public function subirCertificados(Request $request)
    {
        $request->validate([
            'certificado' => 'required|file|mimes:crt,pem',
            'clave_privada' => 'required|file|mimes:key,pem',
        ]);

        $empresa = Auth::user()->empresa;
        $empresaPath = storage_path('app/afip/empresas/' . $empresa->cuit);

        // Crear directorios si no existen
        if (!file_exists($empresaPath)) {
            mkdir($empresaPath, 0755, true);
            mkdir($empresaPath . '/ta', 0755, true);
            mkdir($empresaPath . '/res', 0755, true);
        }

        // Guardar certificado
        $certPath = $empresaPath . '/certificate.crt';
        $request->file('certificado')->move($empresaPath, 'certificate.crt');

        // Guardar clave privada
        $keyPath = $empresaPath . '/private.key';
        $request->file('clave_privada')->move($empresaPath, 'private.key');

        Log::info('Certificados AFIP cargados', [
            'empresa_id' => $empresa->id,
            'usuario_id' => Auth::id()
        ]);

        return redirect()->back()->with('success', 'Certificados de AFIP cargados exitosamente');
    }

    /**
     * Generar certificados de AFIP mediante automatización
     */
    public function generarCertificados(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'alias' => 'required|string',
            'entorno' => 'required|in:dev,prod',
        ]);

        $empresa = Auth::user()->empresa;

        try {
            $afipService = new AfipService($empresa->id);

            $data = [
                'cuit' => $empresa->cuit,
                'username' => $request->input('username'),
                'password' => $request->input('password'),
                'alias' => $request->input('alias'),
            ];

            $resultado = $request->input('entorno') === 'prod'
                ? $afipService->generarCertificadosProduccion($data)
                : $afipService->generarCertificadosDesarrollo($data);

            if ($resultado['success']) {
                return redirect()->back()->with('success', 'Certificados de AFIP generados y guardados correctamente');
            }

            return redirect()->back()->with('error', 'Error al generar certificados: ' . $resultado['error']);
        } catch (Exception $e) {
            Log::error('Error al generar certificados AFIP', [
                'empresa_id' => $empresa->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Error al generar certificados: ' . $e->getMessage());
        }
    }

    /**
     * Generar factura para un pago
     */
    public function generarFactura(Request $request, Pagos $pago)
    {
        $request->validate([
            'punto_venta' => 'nullable|integer|min:1',
            'tipo_comprobante' => 'required|integer|in:1,6,11,3,8,13,2,7,12,4,9,15',
        ]);

        try {
            $empresa = Auth::user()->empresa;
            $afipService = new AfipService($empresa->id);

            $puntoVenta = $request->input('punto_venta', config('afip.default_punto_venta'));
            $tipoComprobante = $request->input('tipo_comprobante');

            $resultado = $afipService->crearFacturaDesdePago($pago, $puntoVenta, $tipoComprobante);

            if ($resultado['success']) {
                // Actualizar el pago con los datos de la factura
                $pago->update([
                    'afip_cae' => $resultado['cae'],
                    'afip_cae_vencimiento' => $resultado['cae_vencimiento'],
                    'afip_numero_comprobante' => $resultado['numero_comprobante'],
                    'afip_tipo_comprobante' => $tipoComprobante,
                    'afip_punto_venta' => $puntoVenta,
                ]);

                return redirect()->back()->with('success', 'Factura generada exitosamente. CAE: ' . $resultado['cae']);
            } else {
                return redirect()->back()->with('error', 'Error al generar factura: ' . $resultado['error']);
            }

        } catch (Exception $e) {
            Log::error('Error al generar factura AFIP', [
                'pago_id' => $pago->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Error al generar factura: ' . $e->getMessage());
        }
    }

    /**
     * Consultar contribuyente por CUIT
     */
    public function consultarContribuyente(Request $request)
    {
        $request->validate([
            'cuit' => 'required|numeric|digits:11',
        ]);

        try {
            $empresa = Auth::user()->empresa;
            $afipService = new AfipService($empresa->id);

            $resultado = $afipService->consultarContribuyente($request->cuit);

            if ($resultado['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $resultado['data']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $resultado['error']
                ], 400);
            }

        } catch (Exception $e) {
            Log::error('Error al consultar contribuyente', [
                'cuit' => $request->cuit,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al consultar contribuyente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener último número de comprobante
     */
    public function obtenerUltimoComprobante(Request $request)
    {
        $request->validate([
            'punto_venta' => 'required|integer|min:1',
            'tipo_comprobante' => 'required|integer',
        ]);

        try {
            $empresa = Auth::user()->empresa;
            $afipService = new AfipService($empresa->id);

            $ultimoNumero = $afipService->obtenerUltimoComprobante(
                $request->punto_venta,
                $request->tipo_comprobante
            );

            return response()->json([
                'success' => true,
                'ultimo_numero' => $ultimoNumero,
                'siguiente_numero' => $ultimoNumero + 1
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener puntos de venta disponibles
     */
    public function obtenerPuntosVenta()
    {
        try {
            $empresa = Auth::user()->empresa;
            $afipService = new AfipService($empresa->id);

            $puntosVenta = $afipService->obtenerPuntosVenta();

            return response()->json([
                'success' => true,
                'data' => $puntosVenta
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener puntos de venta disponibles para una empresa específica
     */
    public function obtenerPuntosVentaEmpresa($empresaId)
    {
        try {
            $empresa = Empresa::findOrFail($empresaId);

            $afipService = new AfipService($empresa->id, false);
            $resultado = $afipService->verificarCertificados();

            if (!($resultado['success'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'error' => $resultado['message'] ?? 'Certificados de AFIP no válidos'
                ], 422);
            }

            $afipService = new AfipService($empresa->id);
            $puntosVenta = $afipService->obtenerPuntosVenta();

            return response()->json([
                'success' => true,
                'data' => $puntosVenta
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test de conexión con AFIP
     */
    public function testConexion()
    {
        try {
            $empresa = Auth::user()->empresa;
            $afipService = new AfipService($empresa->id);

            // Intentar obtener el último comprobante como test
            $ultimoNumero = $afipService->obtenerUltimoComprobante(1, 6);

            return redirect()->back()->with('success', 'Conexión con AFIP exitosa. Último comprobante: ' . $ultimoNumero);

        } catch (Exception $e) {
            Log::error('Error en test de conexión AFIP', [
                'empresa_id' => Auth::user()->empresa->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Error de conexión: ' . $e->getMessage());
        }
    }
}

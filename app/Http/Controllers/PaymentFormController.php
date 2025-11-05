<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Services\MercadoPago\MercadoPagoService;

class PaymentFormController extends Controller
{
    protected $mercadoPagoService;

    public function __construct(MercadoPagoService $mercadoPagoService)
    {
        $this->mercadoPagoService = $mercadoPagoService;
    }

    /**
     * Mostrar el formulario de pago de demostración
     */
    public function show()
    {
        return view('mercadopago.payment-form');
    }

    /**
     * Procesar el pago desde el formulario de demostración
     */
    public function processPayment(Request $request)
    {
        try {
            Log::info('Iniciando proceso de pago', $request->all());

            // Validar los datos del formulario
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'quantity' => 'required|integer|min:1',
                'unit_price' => 'required|numeric|min:0.01',
                'currency_id' => 'required|string|max:3',
                'payer_name' => 'required|string|max:255',
                'payer_surname' => 'required|string|max:255',
                'payer_email' => 'required|email',
                'payer_phone' => 'nullable|string|max:20',
                'description' => 'nullable|string|max:600'
            ]);

            if ($validator->fails()) {
                Log::error('Error de validación', $validator->errors()->toArray());
                return back()->withErrors($validator)->withInput();
            }

            // Preparar los datos para MercadoPago - usar estructura mínima que funciona
            $items = [
                [
                    'title' => $request->title,
                    'quantity' => floatval($request->quantity),
                    'unit_price' => floatval($request->unit_price),
                    'currency_id' => $request->currency_id,
                ]
            ];

            // Obtener URLs válidas para el entorno actual
            $urls = MercadoPagoService::getValidUrls();

/*             dd($items);
 */
            $preferenceData = [
                'items' => $items,
                'external_reference' => 'ORDER-' . time(),
                'back_urls' => [
                    'success' => $urls['success'],
                    'failure' => $urls['failure'],
                    'pending' => $urls['pending'],
                ],
                'auto_return' => 'approved',
                'notification_url' => $urls['webhook'],
            ];

            // Solo agregar payer si el email es válido (evitar datos incompletos)
            if ($request->payer_email && filter_var($request->payer_email, FILTER_VALIDATE_EMAIL)) {
                $preferenceData['payer'] = [
                    'name' => $request->payer_name,
                    'surname' => $request->payer_surname,
                    'email' => $request->payer_email,
                ];
                
                // Solo agregar teléfono si está presente
                if ($request->payer_phone) {
                    $preferenceData['payer']['phone'] = ['number' => $request->payer_phone];
                }
            }

            // Crear la preferencia usando el servicio
            Log::info('Datos de preferencia preparados', $preferenceData);
            $result = $this->mercadoPagoService->createPreference($preferenceData);
            Log::info('Resultado de createPreference', $result);

            if ($result['success']) {
                // Redirigir al checkout de MercadoPago
                $checkoutUrl = config('services.mercadopago.sandbox') 
                    ? $result['sandbox_init_point'] 
                    : $result['init_point'];
                
                Log::info('Redirigiendo a checkout URL', ['url' => $checkoutUrl]);
                return redirect($checkoutUrl);
            } else {
                Log::error('Error creando preferencia', $result);
                return back()->with('error', 'No se pudo crear la preferencia de pago: ' . ($result['error'] ?? 'Error desconocido'));
            }

        } catch (\Exception $e) {
            Log::error('Error procesando pago desde formulario: ' . $e->getMessage());
            return back()->with('error', 'Error al procesar el pago: ' . $e->getMessage());
        }
    }
}
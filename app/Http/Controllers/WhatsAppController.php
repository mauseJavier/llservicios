<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WhatsAppController extends Controller
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Enviar mensaje de texto
     * 
     * POST /api/whatsapp/send-text
     * Body: {
     *   "phone": "5492942506803",
     *   "message": "Hola, este es un mensaje de prueba"
     * }
     */
    public function sendText(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);

        $result = $this->whatsappService->sendButtons(
            $request->phone,
            config('app.name'),
            $request->message,
            '',
            [
                ['type' => 'reply', 'displayText' => 'Información recibida', 'id' => 'info_recibida'],
            ]
        );

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Validar configuración del servicio
     * 
     * GET /api/whatsapp/validate-config
     */
    public function validateConfig(): JsonResponse
    {
        $validation = $this->whatsappService->validateConfiguration();

        return response()->json($validation, $validation['valid'] ? 200 : 500);
    }

    /**
     * Ejemplo de uso del servicio para enviar notificación a un cliente
     */
    public function notificarCliente(Request $request): JsonResponse
    {
        $request->validate([
            'cliente_id' => 'required|integer',
            'tipo_notificacion' => 'required|string|in:pago,servicio,recordatorio',
        ]);

        // Aquí puedes obtener los datos del cliente desde la base de datos
        // $cliente = Cliente::findOrFail($request->cliente_id);

        // Ejemplo de envío de diferentes tipos de notificaciones
        $phone = $request->phone ?? '5492942506803'; // Número del cliente
        
        $mensaje = match($request->tipo_notificacion) {
            'pago' => 'Hola! Tu pago ha sido registrado correctamente. Gracias!',
            'servicio' => 'Recordamos que tienes un servicio pendiente.',
            'recordatorio' => 'Este es un recordatorio de tu próximo servicio.',
            default => 'Notificación desde ' . config('app.name')
        };

        $result = $this->whatsappService->sendButtons(
            $phone,
            '🔔 Notificación',
            $mensaje,
            config('app.name'),
            [
                ['type' => 'reply', 'displayText' => 'Información recibida', 'id' => 'info_recibida'],
            ]
        );

        return response()->json($result, $result['success'] ? 200 : 500);
    }
}

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

        $result = $this->whatsappService->sendTextMessage(
            $request->phone,
            $request->message,
            $request->only(['pushName', 'instanceId', 'source'])
        );

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Enviar documento
     * 
     * POST /api/whatsapp/send-document
     * Body: {
     *   "phone": "5492942506803",
     *   "document_url": "https://ejemplo.com/documento.pdf",
     *   "filename": "documento.pdf",
     *   "caption": "Aquí está tu documento"
     * }
     */
    public function sendDocument(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'document_url' => 'required|url',
            'filename' => 'required|string',
            'caption' => 'nullable|string',
        ]);

        $result = $this->whatsappService->sendDocument(
            $request->phone,
            $request->document_url,
            $request->filename,
            $request->caption,
            $request->only(['pushName', 'instanceId', 'source', 'mimetype'])
        );

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Enviar imagen
     * 
     * POST /api/whatsapp/send-image
     * Body: {
     *   "phone": "5492942506803",
     *   "image_url": "https://ejemplo.com/imagen.jpg",
     *   "caption": "Mira esta imagen"
     * }
     */
    public function sendImage(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'image_url' => 'required|url',
            'caption' => 'nullable|string',
        ]);

        $result = $this->whatsappService->sendImage(
            $request->phone,
            $request->image_url,
            $request->caption,
            $request->only(['pushName', 'instanceId', 'source'])
        );

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Enviar mensaje personalizado con estructura completa
     * 
     * POST /api/whatsapp/send-custom
     * Body: {
     *   "key": {
     *     "remoteJid": "5492942506803@s.whatsapp.net",
     *     "fromMe": true,
     *     "id": "..."
     *   },
     *   "pushName": "Mi App",
     *   "status": "PENDING",
     *   "message": {
     *     "conversation": "mensaje de prueba"
     *   },
     *   "messageType": "conversation",
     *   "messageTimestamp": 1761756020,
     *   "instanceId": "b8ace17d-ae1d-4e03-a750-6bd4edd8cb8a",
     *   "source": "api"
     * }
     */
    public function sendCustom(Request $request): JsonResponse
    {
        $request->validate([
            'key.remoteJid' => 'required|string',
            'message' => 'required|array',
        ]);

        $result = $this->whatsappService->sendCustomMessage($request->all());

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

        $result = $this->whatsappService->sendTextMessage($phone, $mensaje);

        return response()->json($result, $result['success'] ? 200 : 500);
    }
}

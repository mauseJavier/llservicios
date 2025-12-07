# Servicio de WhatsApp

Este servicio permite enviar mensajes de texto, documentos e imágenes por WhatsApp utilizando una API.

## Configuración

### Variables de Entorno

Agrega las siguientes variables a tu archivo `.env`:

```env
WHATSAPP_API_URL=https://tu-api-whatsapp.com/api
WHATSAPP_API_KEY=tu_api_key_aqui
WHATSAPP_INSTANCE_ID=b8ace17d-ae1d-4e03-a750-6bd4edd8cb8a
```

### Estructura del Proyecto

- **Servicio**: `app/Services/WhatsAppService.php`
- **Controlador**: `app/Http/Controllers/WhatsAppController.php`
- **Configuración**: `config/services.php`

## Uso del Servicio

### 1. Enviar Mensaje de Texto Simple

```php
use App\Services\WhatsAppService;

$whatsapp = new WhatsAppService();

$resultado = $whatsapp->sendTextMessage(
    '5492942506803',  // Número de teléfono con código de país
    'Hola! Este es un mensaje de prueba'  // Mensaje
);

if ($resultado['success']) {
    echo "Mensaje enviado correctamente";
} else {
    echo "Error: " . $resultado['error'];
}
```

### 2. Enviar Documento (PDF, Word, Excel, etc)

```php
$resultado = $whatsapp->sendDocument(
    '5492942506803',                           // Teléfono
    'https://ejemplo.com/documentos/factura.pdf',  // URL del documento
    'Factura-2024.pdf',                        // Nombre del archivo
    'Aquí está tu factura del mes',            // Caption (opcional)
    [
        'mimetype' => 'application/pdf'        // Tipo MIME (opcional)
    ]
);
```

### 3. Enviar Imagen

```php
$resultado = $whatsapp->sendImage(
    '5492942506803',
    'https://ejemplo.com/imagenes/producto.jpg',
    'Mira nuestro nuevo producto!'  // Caption opcional
);
```

### 4. Enviar Mensaje Personalizado (Estructura Completa)

```php
$payload = [
    'key' => [
        'remoteJid' => '5492942506803@s.whatsapp.net',
        'fromMe' => true,
        'id' => 'ID_UNICO_MENSAJE'
    ],
    'pushName' => 'Mi Empresa',
    'status' => 'PENDING',
    'message' => [
        'conversation' => 'mensaje de prueba'
    ],
    'messageType' => 'conversation',
    'messageTimestamp' => time(),
    'instanceId' => 'b8ace17d-ae1d-4e03-a750-6bd4edd8cb8a',
    'source' => 'api'
];

$resultado = $whatsapp->sendCustomMessage($payload);
```

## Uso desde Controladores

### Endpoints API Disponibles

#### 1. Enviar Texto
```http
POST /api/whatsapp/send-text
Content-Type: application/json

{
  "phone": "5492942506803",
  "message": "Hola, este es un mensaje de prueba"
}
```

#### 2. Enviar Documento
```http
POST /api/whatsapp/send-document
Content-Type: application/json

{
  "phone": "5492942506803",
  "document_url": "https://ejemplo.com/documento.pdf",
  "filename": "documento.pdf",
  "caption": "Aquí está tu documento"
}
```

#### 3. Enviar Imagen
```http
POST /api/whatsapp/send-image
Content-Type: application/json

{
  "phone": "5492942506803",
  "image_url": "https://ejemplo.com/imagen.jpg",
  "caption": "Mira esta imagen"
}
```

#### 4. Enviar Mensaje Personalizado
```http
POST /api/whatsapp/send-custom
Content-Type: application/json

{
  "key": {
    "remoteJid": "5492942506803@s.whatsapp.net",
    "fromMe": true,
    "id": "3EB0437BF8B1698787B7B5A15521D8EC7B09BCBF"
  },
  "pushName": "Mi App",
  "status": "PENDING",
  "message": {
    "conversation": "mensaje de prueba"
  },
  "messageType": "conversation",
  "messageTimestamp": 1761756020,
  "instanceId": "b8ace17d-ae1d-4e03-a750-6bd4edd8cb8a",
  "source": "api"
}
```

#### 5. Validar Configuración
```http
GET /api/whatsapp/validate-config
```

## Ejemplos de Integración

### Enviar Notificación de Pago

```php
use App\Services\WhatsAppService;

class PagoController extends Controller
{
    public function notificarPago($pagoId)
    {
        $pago = Pago::findOrFail($pagoId);
        $cliente = $pago->cliente;
        
        $whatsapp = new WhatsAppService();
        
        $mensaje = "Hola {$cliente->nombre}! \n\n" .
                   "Tu pago de \${$pago->monto} ha sido registrado correctamente.\n" .
                   "Fecha: {$pago->fecha}\n" .
                   "Concepto: {$pago->concepto}\n\n" .
                   "¡Gracias por tu pago!";
        
        $resultado = $whatsapp->sendTextMessage(
            $cliente->telefono,
            $mensaje
        );
        
        if ($resultado['success']) {
            Log::info("Notificación enviada al cliente {$cliente->id}");
        }
    }
}
```

### Enviar Recibo en PDF

```php
use App\Services\WhatsAppService;
use App\Services\PdfService;

class ReciboController extends Controller
{
    public function enviarRecibo($reciboId)
    {
        $recibo = Recibo::findOrFail($reciboId);
        $cliente = $recibo->cliente;
        
        // Generar PDF (usando tu PdfService existente)
        $pdfService = new PdfService();
        $pdfUrl = $pdfService->generarReciboPDF($recibo);
        
        // Enviar por WhatsApp
        $whatsapp = new WhatsAppService();
        
        $resultado = $whatsapp->sendDocument(
            $cliente->telefono,
            $pdfUrl,
            "Recibo-{$recibo->numero}.pdf",
            "Hola {$cliente->nombre}, adjunto tu recibo de pago."
        );
        
        return $resultado;
    }
}
```

### Job para Envío Masivo

```php
namespace App\Jobs;

use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnviarWhatsAppMasivoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $clientes;
    protected $mensaje;

    public function __construct($clientes, $mensaje)
    {
        $this->clientes = $clientes;
        $this->mensaje = $mensaje;
    }

    public function handle()
    {
        $whatsapp = new WhatsAppService();
        
        foreach ($this->clientes as $cliente) {
            if (!empty($cliente->telefono)) {
                $whatsapp->sendTextMessage(
                    $cliente->telefono,
                    $this->mensaje
                );
                
                // Esperar un poco entre mensajes para no saturar
                sleep(2);
            }
        }
    }
}
```

## Formato de Números de Teléfono

El servicio acepta números en diferentes formatos:
- Con código de país: `5492942506803`
- Ya formateado: `5492942506803@s.whatsapp.net`

El servicio automáticamente formateará el número al formato requerido por WhatsApp.

## Validación de Configuración

Para verificar que el servicio está configurado correctamente:

```php
$whatsapp = new WhatsAppService();
$validacion = $whatsapp->validateConfiguration();

if ($validacion['valid']) {
    echo "Configuración válida";
} else {
    echo "Errores: " . implode(', ', $validacion['errors']);
}
```

## Logs

Todos los envíos y errores se registran automáticamente en los logs de Laravel:
- Ubicación: `storage/logs/laravel.log`
- Canal: `WhatsApp`

## Respuestas del Servicio

Todas las funciones devuelven un array con la siguiente estructura:

### Respuesta Exitosa
```php
[
    'success' => true,
    'data' => [...],  // Datos de respuesta de la API
    'message' => 'Mensaje enviado correctamente'
]
```

### Respuesta con Error
```php
[
    'success' => false,
    'error' => 'Mensaje de error',
    'data' => null
]
```

## Tipos de Mensajes Soportados

1. **conversation**: Mensaje de texto simple
2. **documentMessage**: Envío de documentos (PDF, Word, Excel, etc)
3. **imageMessage**: Envío de imágenes
4. Extensible para otros tipos según la API que uses

## Notas Importantes

- Los mensajes se envían de forma asíncrona
- Se recomienda usar Jobs para envíos masivos
- El ID de instancia debe ser válido y activo
- Las URLs de documentos/imágenes deben ser públicamente accesibles
- Se generan IDs únicos automáticamente para cada mensaje

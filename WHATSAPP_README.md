# 📱 Servicio de WhatsApp - Resumen de Implementación

## ✅ Archivos Creados

### 📁 Archivos Principales

1. **`app/Services/WhatsAppService.php`** - Servicio principal para enviar mensajes
2. **`app/Http/Controllers/WhatsAppController.php`** - Controlador con endpoints API
3. **`app/Jobs/EnviarWhatsAppJob.php`** - Job para envío asíncrono
4. **`tests/Unit/WhatsAppServiceTest.php`** - Tests unitarios

### 📁 Archivos de Configuración

5. **`config/services.php`** - ✏️ Actualizado con configuración de WhatsApp
6. **`.env.whatsapp.example`** - Variables de entorno de ejemplo

### 📁 Archivos de Documentación

7. **`WHATSAPP_SERVICE.md`** - Documentación completa del servicio
8. **`WHATSAPP_EXAMPLES.php`** - 12 ejemplos prácticos de uso
9. **`routes/whatsapp_routes_example.php`** - Rutas de ejemplo para API

---

## 🚀 Inicio Rápido (5 pasos)

### Paso 1: Configurar Variables de Entorno

Agrega estas líneas a tu archivo `.env`:

```env
WHATSAPP_API_URL=https://tu-api-whatsapp.com/api
WHATSAPP_API_KEY=tu_api_key_aqui
WHATSAPP_INSTANCE_ID=b8ace17d-ae1d-4e03-a750-6bd4edd8cb8a
```

### Paso 2: Agregar Rutas

Abre `routes/api.php` y agrega:

```php
use App\Http\Controllers\WhatsAppController;

Route::prefix('whatsapp')->group(function () {
    Route::post('/send-text', [WhatsAppController::class, 'sendText']);
    Route::post('/send-document', [WhatsAppController::class, 'sendDocument']);
    Route::post('/send-image', [WhatsAppController::class, 'sendImage']);
    Route::post('/send-custom', [WhatsAppController::class, 'sendCustom']);
    Route::get('/validate-config', [WhatsAppController::class, 'validateConfig']);
});
```

### Paso 3: Probar Configuración

```bash
php artisan tinker
```

```php
$whatsapp = new App\Services\WhatsAppService();
$whatsapp->validateConfiguration();
```

### Paso 4: Enviar Primer Mensaje

```php
$whatsapp = new App\Services\WhatsAppService();
$whatsapp->sendTextMessage('5492942506803', '¡Hola desde Laravel!');
```

### Paso 5: Usar via API

```bash
curl -X POST http://localhost/api/whatsapp/send-text \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "5492942506803",
    "message": "Hola desde API"
  }'
```

---

## 📚 Uso Básico

### Enviar Mensaje de Texto

```php
use App\Services\WhatsAppService;

$whatsapp = new WhatsAppService();
$resultado = $whatsapp->sendTextMessage(
    '5492942506803',
    'Hola! Este es un mensaje de prueba'
);
```

### Enviar Documento (PDF, Word, etc)

```php
$resultado = $whatsapp->sendDocument(
    '5492942506803',
    'https://ejemplo.com/documento.pdf',
    'documento.pdf',
    'Aquí está tu documento'
);
```

### Enviar Imagen

```php
$resultado = $whatsapp->sendImage(
    '5492942506803',
    'https://ejemplo.com/imagen.jpg',
    'Mira esta imagen'
);
```

### Envío Asíncrono (Recomendado)

```php
use App\Jobs\EnviarWhatsAppJob;

// Enviar en segundo plano
EnviarWhatsAppJob::dispatch('5492942506803', 'Mensaje', 'text');
```

---

## 🎯 Casos de Uso Comunes

### 1. Notificar Pago Registrado

```php
$cliente = Cliente::find($clienteId);
$pago = Pago::find($pagoId);

$whatsapp = new WhatsAppService();
$mensaje = "✅ Pago Registrado\n\n" .
           "Monto: \${$pago->monto}\n" .
           "Concepto: {$pago->concepto}\n\n" .
           "¡Gracias!";

$whatsapp->sendTextMessage($cliente->telefono, $mensaje);
```

### 2. Enviar Recibo en PDF

```php
// Genera el PDF con tu PdfService
$pdfUrl = $pdfService->generarReciboPDF($recibo);

// Envía por WhatsApp
$whatsapp->sendDocument(
    $cliente->telefono,
    $pdfUrl,
    "Recibo-{$recibo->numero}.pdf",
    "🧾 Tu recibo de pago"
);
```

### 3. Recordatorio de Servicios Impagos

```php
$servicios = Servicio::where('estado', 'impago')
    ->where('cliente_id', $clienteId)
    ->get();

$mensaje = "⚠️ Servicios Pendientes:\n\n";
foreach ($servicios as $servicio) {
    $mensaje .= "• {$servicio->descripcion} - \${$servicio->monto}\n";
}

$whatsapp->sendTextMessage($cliente->telefono, $mensaje);
```

---

## 🔧 Métodos Disponibles

| Método | Descripción | Parámetros |
|--------|-------------|------------|
| `sendTextMessage()` | Enviar texto simple | phone, message, options |
| `sendDocument()` | Enviar documento | phone, url, filename, caption, options |
| `sendImage()` | Enviar imagen | phone, url, caption, options |
| `sendCustomMessage()` | Mensaje personalizado | payload completo |
| `validateConfiguration()` | Validar config | ninguno |

---

## 📋 Estructura de Respuesta

### Respuesta Exitosa
```php
[
    'success' => true,
    'data' => [...],
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

---

## 🧪 Testing

### Ejecutar Tests

```bash
php artisan test --filter WhatsAppServiceTest
```

### Tests Incluidos

- ✅ Envío de mensaje de texto
- ✅ Envío de documento
- ✅ Envío de imagen
- ✅ Manejo de errores
- ✅ Formato de números
- ✅ Validación de configuración
- ✅ Mensajes personalizados

---

## 📖 Documentación Completa

Para más detalles, consulta:

- **`WHATSAPP_SERVICE.md`** - Documentación completa con todos los detalles
- **`WHATSAPP_EXAMPLES.php`** - 12 ejemplos prácticos listos para usar

---

## 🛠️ Comandos Útiles

### Crear comando personalizado para enviar mensajes

```bash
php artisan make:command TestWhatsAppCommand
```

```php
// En el handle():
$whatsapp = new WhatsAppService();
$whatsapp->sendTextMessage($this->argument('phone'), $this->argument('message'));
```

Uso:
```bash
php artisan whatsapp:test 5492942506803 "Hola mundo"
```

---

## ⚠️ Notas Importantes

1. **URLs públicas**: Los documentos e imágenes deben estar en URLs públicamente accesibles
2. **Rate limiting**: Considera espaciar envíos masivos
3. **Jobs**: Usa `EnviarWhatsAppJob` para envíos masivos
4. **Logs**: Todos los envíos se registran en `storage/logs/laravel.log`
5. **Formato de teléfono**: Acepta con o sin `@s.whatsapp.net`
6. **Reintentos**: El Job reintenta 3 veces automáticamente

---

## 🔒 Seguridad

- Las API Keys se guardan en `.env` (nunca en el código)
- Usa middleware de autenticación para endpoints públicos
- Valida todos los inputs antes de enviar

---

## 📞 Soporte

Para problemas o preguntas:
1. Revisa los logs en `storage/logs/laravel.log`
2. Verifica la configuración con `validateConfiguration()`
3. Consulta los ejemplos en `WHATSAPP_EXAMPLES.php`

---

## 🎉 ¡Listo!

Tu servicio de WhatsApp está configurado y listo para usar. Empieza con un mensaje simple y luego explora las funcionalidades avanzadas.

**Primer paso sugerido:**
```php
$whatsapp = new App\Services\WhatsAppService();
$whatsapp->sendTextMessage('TU_NUMERO', '¡Funciona! 🎉');
```

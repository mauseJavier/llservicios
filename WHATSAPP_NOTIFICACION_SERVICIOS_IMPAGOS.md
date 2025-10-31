# Notificación de Servicios Impagos por WhatsApp

## 📋 Descripción

Esta funcionalidad permite enviar notificaciones automáticas por WhatsApp a todos los clientes que tengan servicios pendientes de pago (impagos) en la empresa del usuario logueado.

## 🚀 Componentes Creados

### 1. Job: `EnviarWhatsAppTodosServiciosImpagosJob.php`

Ubicación: `app/Jobs/EnviarWhatsAppTodosServiciosImpagosJob.php`

**Responsabilidades:**
- Enviar mensajes de WhatsApp a los clientes con servicios impagos
- Construir un mensaje detallado con la información de cada servicio adeudado
- Registrar logs de éxito o error en el envío

**Características del mensaje:**
- Saludo personalizado con el nombre del cliente
- Nombre de la empresa
- Cantidad de servicios pendientes
- Detalle de cada servicio (nombre, cantidad, precio, subtotal, fecha)
- Total adeudado
- Mensaje de cortesía

### 2. Método en Controller: `NotificacionWhatsAppTodosServiciosImpagos()`

Ubicación: `app/Http/Controllers/EnviarCorreoController.php`

**Responsabilidades:**
- Obtener todos los clientes con servicios impagos de la empresa del usuario logueado
- Filtrar solo clientes que tengan número de teléfono registrado
- Procesar la información de servicios para cada cliente
- Despachar el Job para envío asíncrono
- Mostrar mensaje de confirmación con cantidad de clientes notificados

## 📝 Uso

### Agregar Ruta

Agrega esta ruta en tu archivo `routes/web.php`:

```php
Route::get('/notificacion-whatsapp-servicios-impagos', [EnviarCorreoController::class, 'NotificacionWhatsAppTodosServiciosImpagos'])
    ->name('NotificacionWhatsAppServiciosImpagos')
    ->middleware('auth');
```

### Llamar desde una Vista

En tu vista de servicios impagos, puedes agregar un botón:

```html
<a href="{{ route('NotificacionWhatsAppServiciosImpagos') }}" 
   class="btn btn-success"
   onclick="return confirm('¿Desea enviar notificaciones de WhatsApp a todos los clientes con servicios impagos?')">
    <i class="fa fa-whatsapp"></i> Notificar por WhatsApp
</a>
```

### Llamar desde un Controller

```php
return redirect()->route('NotificacionWhatsAppServiciosImpagos');
```

## 🔧 Configuración Requerida

### 1. Variables de Entorno

Asegúrate de tener configuradas las siguientes variables en tu archivo `.env`:

```env
WHATSAPP_API_URL=https://tu-api-whatsapp.com
WHATSAPP_API_KEY=tu_api_key
WHATSAPP_INSTANCE_ID=tu_instance_id
```

### 2. Configuración en `config/services.php`

Verifica que exista la configuración de WhatsApp:

```php
'whatsapp' => [
    'api_url' => env('WHATSAPP_API_URL'),
    'api_key' => env('WHATSAPP_API_KEY'),
    'instance_id' => env('WHATSAPP_INSTANCE_ID'),
],
```

### 3. Tabla de Clientes

La tabla `clientes` debe tener el campo `telefono`:

```php
$table->string('telefono')->nullable();
```

## 📊 Funcionamiento

### Flujo de Ejecución

1. **Usuario hace clic** en el botón de notificación por WhatsApp
2. **Controller consulta** la base de datos para obtener clientes con servicios impagos
3. **Filtra** solo clientes con número de teléfono válido
4. **Procesa** los servicios de cada cliente y calcula totales
5. **Despacha** un Job por cada cliente para envío asíncrono
6. **Redirecciona** con mensaje de confirmación

### Query SQL

El sistema ejecuta dos queries:

**Query 1:** Obtiene clientes con servicios impagos
```sql
SELECT
    COUNT(d.id) AS cantidad,
    a.cliente_id AS cliente_id,
    d.nombre AS nombreCliente,
    d.telefono AS telefonoCliente,
    c.nombre AS nombreEmpresa
FROM
    servicio_pagar a,
    servicios b,
    empresas c,
    clientes d
WHERE
    a.cliente_id = d.id 
    AND a.servicio_id = b.id 
    AND b.empresa_id = c.id 
    AND c.id = [ID_EMPRESA_USUARIO]
    AND a.estado = 'impago'
    AND d.telefono IS NOT NULL
    AND d.telefono != ""
GROUP BY
    a.cliente_id, d.nombre, d.telefono, c.nombre
```

**Query 2:** Obtiene detalle de servicios por cliente
```sql
SELECT
    b.nombre AS nombreServicio,
    a.cantidad AS cantidad,
    a.precio AS precio,
    a.precio * a.cantidad AS total,
    a.created_at as fecha
FROM
    servicio_pagar a,
    servicios b
WHERE
    a.servicio_id = b.id 
    AND b.empresa_id = [ID_EMPRESA]
    AND a.cliente_id = [ID_CLIENTE]
    AND a.estado = 'impago'
```

## 📱 Formato del Mensaje

El mensaje enviado tiene el siguiente formato:

```
🔔 *Notificación de Servicios Pendientes*

Hola *Juan Pérez*,

Le informamos desde *Mi Empresa SRL* que tiene *3* servicio(s) pendiente(s) de pago:

📌 *Servicio 1:*
   • Nombre: Internet 100MB
   • Cantidad: 1
   • Precio unitario: $5000
   • Subtotal: $5.000,00
   • Fecha: 15/09/2025

📌 *Servicio 2:*
   • Nombre: Cable HD
   • Cantidad: 1
   • Precio unitario: $3000
   • Subtotal: $3.000,00
   • Fecha: 15/09/2025

📌 *Servicio 3:*
   • Nombre: Teléfono
   • Cantidad: 1
   • Precio unitario: $1500
   • Subtotal: $1.500,00
   • Fecha: 15/09/2025

💰 *Total adeudado: $9.500,00*

Por favor, regularice su situación a la brevedad posible.

Ante cualquier consulta, no dude en contactarnos.

Gracias por su atención. 🙏
```

## 🔍 Logs

El sistema registra logs en `storage/logs/laravel.log`:

**Logs de inicio:**
```
WhatsApp Job - Iniciando envío de notificación de servicios impagos
```

**Logs de éxito:**
```
WhatsApp Job - Mensaje enviado exitosamente
```

**Logs de error:**
```
WhatsApp Job - Error al enviar mensaje
WhatsApp Job - Excepción al enviar mensaje
```

## ⚠️ Consideraciones

### Números de Teléfono

- El sistema filtra automáticamente clientes sin número de teléfono
- Los números deben estar en formato internacional (ej: 5492942506803)
- El `WhatsAppService` agrega automáticamente el prefijo 549 si no lo tiene

### Procesamiento Asíncrono

- Los mensajes se envían a través de **Jobs** (cola de trabajos)
- Debes tener configurado el sistema de colas de Laravel
- Para procesamiento inmediato, ejecuta: `php artisan queue:work`
- Para producción, configura Supervisor o similar

### Comando para Procesar Cola

```bash
php artisan queue:work --tries=3
```

## 🧪 Pruebas

### Verificar Configuración

Puedes crear una ruta de prueba para verificar la configuración:

```php
Route::get('/test-whatsapp-config', function() {
    $whatsapp = new \App\Services\WhatsAppService();
    return $whatsapp->validateConfiguration();
});
```

### Enviar Mensaje de Prueba

```php
Route::get('/test-whatsapp-send', function() {
    $whatsapp = new \App\Services\WhatsAppService();
    return $whatsapp->sendTextMessage('5492942506803', '🧪 Mensaje de prueba desde Laravel');
});
```

## 🔄 Comparación con Email

| Característica | Email | WhatsApp |
|---|---|---|
| Método Controller | `NotificacionTodosServiciosImpagos()` | `NotificacionWhatsAppTodosServiciosImpagos()` |
| Job | `EnviarEmailTodosServiciosImpagosJob` | `EnviarWhatsAppTodosServiciosImpagosJob` |
| Servicio | `Mail::to()` | `WhatsAppService` |
| Campo requerido | `correo` | `telefono` |
| Filtro | Sin filtro especial | `telefono IS NOT NULL` |

## 📚 Documentación Relacionada

- [WhatsApp Service Documentation](WHATSAPP_SERVICE.md)
- [WhatsApp Examples](WHATSAPP_EXAMPLES.php)
- [WhatsApp Architecture](WHATSAPP_ARQUITECTURA.md)

## 🆘 Troubleshooting

### Error: "WhatsApp API URL no está configurada"

**Solución:** Verifica que tengas las variables de entorno configuradas en `.env`

### Error: "No se envían mensajes"

**Solución:** 
1. Verifica que la cola esté corriendo: `php artisan queue:work`
2. Revisa los logs: `tail -f storage/logs/laravel.log`
3. Verifica la conexión con la API de WhatsApp

### Clientes no reciben mensajes

**Solución:**
1. Verifica que los clientes tengan número de teléfono en la BD
2. Verifica el formato del número (debe incluir código de país)
3. Revisa los logs de la API de WhatsApp

---

**Última actualización:** Octubre 2025  
**Autor:** Sistema de Gestión LL Servicios

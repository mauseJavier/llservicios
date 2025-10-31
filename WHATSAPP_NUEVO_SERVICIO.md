# 📱 Notificación WhatsApp - Nuevo Servicio

## 📋 Descripción

Esta funcionalidad envía una notificación por WhatsApp al cliente cuando se le registra un nuevo servicio a pagar. Trabaja en conjunto con la notificación por email, enviando ambas notificaciones de forma automática.

## 🆕 Archivo Creado

### `EnviarWhatsAppNuevoServicioJob.php`

**Ubicación:** `app/Jobs/EnviarWhatsAppNuevoServicioJob.php`

**Basado en:** `EnviarEmailNuvoServicioJob.php`

## 🔧 Características Principales

### ✅ Consulta de Datos
- Obtiene información completa del servicio mediante el ID
- Incluye datos del cliente, servicio y empresa
- Verifica que el cliente tenga número de teléfono

### ✅ Validaciones
- Valida que el `idServicioPagar` esté definido
- Verifica que se encontraron datos en la BD
- Confirma que el cliente tenga número de teléfono
- Registra logs en cada validación

### ✅ Mensaje Personalizado
- Saludo con nombre del cliente
- Nombre de la empresa
- Detalle del servicio:
  - Nombre del servicio
  - Cantidad
  - Precio unitario
  - Fecha de registro
  - Total a pagar

### ✅ Soporte Multi-Instancia
- Acepta `instanciaWS` y `tokenWS` personalizados
- Útil para empresas con múltiples instancias de WhatsApp

## 📊 Flujo de Ejecución

```
Usuario registra nuevo servicio
         ↓
Controller: NotificacionNuevoServicio($idServicioPagar)
         ↓
┌────────────────────────────────────┐
│  Obtiene datos de la empresa      │
│  (instanciaWS, tokenWS)            │
└────────────────────────────────────┘
         ↓
┌─────────────────────┬──────────────────────┐
│                     │                      │
▼                     ▼                      ▼
EnviarEmailNuvoServicioJob    EnviarWhatsAppNuevoServicioJob
    (Correo)                      (WhatsApp)
         │                             │
         ▼                             ▼
   Cliente recibe               Cliente recibe
      email                        WhatsApp
```

## 💬 Formato del Mensaje

```
📢 *Nuevo Servicio Registrado*

Hola *Juan Pérez*,

Le informamos desde *Mi Empresa SRL* que se ha registrado un nuevo servicio a su nombre:

📋 *Detalle del servicio:*
   • Servicio: *Internet 100MB*
   • Cantidad: 1
   • Precio unitario: $5.000,00
   • Fecha de registro: 30/10/2025

💰 *Total a pagar: $5.000,00*

Por favor, proceda con el pago a la brevedad posible.

Si tiene alguna consulta, no dude en contactarnos.

Gracias por su atención. 🙏
```

## 🔍 Query SQL

El Job ejecuta la siguiente consulta para obtener los datos:

```sql
SELECT
    b.nombre AS nombreCliente,
    b.telefono AS telefonoCliente,
    c.nombre AS nombreServicio,
    a.cantidad AS cantidadServicio,
    a.precio AS precioServicio,
    a.created_at AS fechaServicio,
    d.nombre AS nombreEmpresa
FROM
    servicio_pagar a,
    clientes b,
    servicios c,
    empresas d
WHERE
    a.cliente_id = b.id 
    AND a.servicio_id = c.id 
    AND c.empresa_id = d.id
    AND a.id = ?
```

## 📝 Modificaciones en el Controller

### Método: `NotificacionNuevoServicio()`

**Antes:**
```php
public function NotificacionNuevoServicio($idServicioPagar){
    EnviarEmailNuvoServicioJob::dispatch($idServicioPagar);
    return redirect()->route('ServiciosImpagos')
        ->with('status','Mensaje Correcto');
}
```

**Después:**
```php
public function NotificacionNuevoServicio($idServicioPagar){
    $usuario = Auth::user();
    $empresa = \App\Models\Empresa::find($usuario->empresa_id);

    $instanciaWS = $empresa->instanciaWS ?? null;
    $tokenWS = $empresa->tokenWS ?? null;

    // Enviar correo electrónico
    EnviarEmailNuvoServicioJob::dispatch($idServicioPagar);

    // Enviar WhatsApp
    EnviarWhatsAppNuevoServicioJob::dispatch($idServicioPagar, $instanciaWS, $tokenWS);

    return redirect()->route('ServiciosImpagos')
        ->with('status','Notificaciones enviadas correctamente (Email y WhatsApp)');
}
```

## 🎯 Casos de Uso

### 1. Cliente con Email y Teléfono
- ✅ Recibe email
- ✅ Recibe WhatsApp

### 2. Cliente solo con Email
- ✅ Recibe email
- ⚠️ No recibe WhatsApp (se registra en log)

### 3. Cliente solo con Teléfono
- ⚠️ No recibe email (manejado por el Job de email)
- ✅ Recibe WhatsApp

### 4. Cliente sin Email ni Teléfono
- ⚠️ No recibe email
- ⚠️ No recibe WhatsApp

## 📊 Logs Generados

### Log de Inicio
```
WhatsApp Job - Enviando notificación de nuevo servicio
[telefono] => 5492942506803
[cliente] => Juan Pérez
[servicio] => Internet 100MB
```

### Log de Éxito
```
WhatsApp Job - Notificación de nuevo servicio enviada exitosamente
[telefono] => 5492942506803
[cliente] => Juan Pérez
[servicio] => Internet 100MB
```

### Log de Cliente sin Teléfono
```
WhatsApp Job - Cliente sin número de teléfono
[idServicioPagar] => 123
[cliente] => María González
```

### Log de Error
```
WhatsApp Job - Error al enviar notificación de nuevo servicio
[telefono] => 5492942506803
[error] => Error en petición WhatsApp (500): Internal Server Error
```

## 🔧 Configuración Requerida

### 1. Base de Datos

La tabla `empresas` debe tener los campos:
```php
$table->string('instanciaWS')->nullable();
$table->string('tokenWS')->nullable();
```

### 2. Variables de Entorno

Si no usas instancias por empresa, configura en `.env`:
```env
WHATSAPP_API_URL=https://tu-api-evolution.com
WHATSAPP_API_KEY=tu_api_key
WHATSAPP_INSTANCE_ID=tu_instance_id
```

### 3. Cola de Trabajos

Asegúrate de tener corriendo:
```bash
php artisan queue:work --tries=3
```

## 🆚 Comparación: Email vs WhatsApp

| Aspecto | Email Job | WhatsApp Job |
|---------|-----------|--------------|
| **Nombre del Job** | `EnviarEmailNuvoServicioJob` | `EnviarWhatsAppNuevoServicioJob` |
| **Campo requerido** | `correo` | `telefono` |
| **Formato** | HTML/Blade | Texto con emojis |
| **Servicio usado** | `Mail::to()` | `WhatsAppService` |
| **Validación** | Try/catch básico | Múltiples validaciones |
| **Logs** | Mínimos | Detallados en cada paso |
| **Multi-instancia** | No aplica | Sí (instanciaWS, tokenWS) |

## 🚀 Uso

### Desde el Controller

Ya está integrado en el método `NotificacionNuevoServicio()`. Solo necesitas llamarlo:

```php
return redirect()->route('NotificacionNuevoServicio', ['idServicioPagar' => 123]);
```

### Desde una Vista

```html
<a href="{{ route('NotificacionNuevoServicio', ['idServicioPagar' => $servicio->id]) }}" 
   class="btn btn-primary">
    <i class="fa fa-bell"></i> Notificar Cliente
</a>
```

### Manualmente (Tinker)

```php
php artisan tinker

>>> use App\Jobs\EnviarWhatsAppNuevoServicioJob;
>>> EnviarWhatsAppNuevoServicioJob::dispatch(123);
```

## 🧪 Pruebas

### Verificar que el Job se crea correctamente

```bash
php artisan tinker

>>> $job = new App\Jobs\EnviarWhatsAppNuevoServicioJob(1);
>>> dd($job);
```

### Verificar consulta SQL

```bash
php artisan tinker

>>> use Illuminate\Support\Facades\DB;
>>> $datos = DB::select('SELECT b.nombre AS nombreCliente, b.telefono AS telefonoCliente, c.nombre AS nombreServicio, a.cantidad AS cantidadServicio, a.precio AS precioServicio, a.created_at AS fechaServicio, d.nombre AS nombreEmpresa FROM servicio_pagar a, clientes b, servicios c, empresas d WHERE a.cliente_id = b.id AND a.servicio_id = c.id AND c.empresa_id = d.id AND a.id = ?', [1]);
>>> dd($datos);
```

### Ejecutar Job manualmente

```bash
php artisan tinker

>>> use App\Jobs\EnviarWhatsAppNuevoServicioJob;
>>> $job = new EnviarWhatsAppNuevoServicioJob(1, 'instancia123', 'token456');
>>> $job->handle();
```

## 🐛 Troubleshooting

### El cliente no recibe WhatsApp

**Posibles causas:**
1. Cliente no tiene teléfono registrado
2. Cola de trabajos no está corriendo
3. Configuración de WhatsApp incorrecta
4. Número de teléfono en formato incorrecto

**Solución:**
```bash
# 1. Verificar logs
tail -f storage/logs/laravel.log | grep -i whatsapp

# 2. Verificar cola
php artisan queue:work

# 3. Verificar datos del cliente
php artisan tinker
>>> $cliente = App\Models\Cliente::find(1);
>>> dd($cliente->telefono);
```

### Job falla constantemente

**Solución:**
```bash
# Ver jobs fallidos
php artisan queue:failed

# Reintentar todos
php artisan queue:retry all

# Limpiar jobs fallidos
php artisan queue:flush
```

### WhatsApp Service no configurado

**Error:**
```
WhatsApp API URL no está configurada
```

**Solución:**
Verifica tu `.env` y asegúrate de tener:
```env
WHATSAPP_API_URL=https://tu-api.com
WHATSAPP_API_KEY=tu_key
WHATSAPP_INSTANCE_ID=tu_instance
```

## 📚 Archivos Relacionados

- **Job Email:** `app/Jobs/EnviarEmailNuvoServicioJob.php`
- **Job WhatsApp (nuevo):** `app/Jobs/EnviarWhatsAppNuevoServicioJob.php`
- **Job WhatsApp (impagos):** `app/Jobs/EnviarWhatsAppTodosServiciosImpagosJob.php`
- **Controller:** `app/Http/Controllers/EnviarCorreoController.php`
- **Service:** `app/Services/WhatsAppService.php`
- **Rutas:** `routes/web.php`

## ✨ Ventajas de esta Implementación

1. **Notificación Dual:** Cliente recibe email Y WhatsApp
2. **No Invasivo:** Si falla WhatsApp, el email se envía igual
3. **Logs Detallados:** Fácil debugging y seguimiento
4. **Multi-Instancia:** Soporte para diferentes instancias por empresa
5. **Asíncrono:** No bloquea la aplicación
6. **Validaciones:** Múltiples checks antes de enviar
7. **Formato Profesional:** Mensaje claro y bien estructurado

## 🎉 Resultado Final

Ahora cuando se registra un nuevo servicio a un cliente:

1. ✅ Se envía un **email** con los datos del servicio
2. ✅ Se envía un **WhatsApp** con los datos del servicio
3. ✅ Se registran **logs** de ambos envíos
4. ✅ El usuario ve un mensaje: **"Notificaciones enviadas correctamente (Email y WhatsApp)"**

---

**Desarrollado para:** LL Servicios  
**Fecha:** Octubre 2025  
**Versión:** 1.0.0  
**Basado en:** EnviarEmailNuvoServicioJob.php

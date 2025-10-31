# 📱 Notificación Mensual con WhatsApp

## 🔄 Integración Completada

Se ha mejorado el comando `NotificacionMensual` para enviar notificaciones tanto por **Email** como por **WhatsApp** a los clientes con servicios impagos.

---

## ✨ Mejoras Implementadas

### 1. **Envío Dual: Email + WhatsApp**
- ✅ Mantiene el envío de emails existente
- ✅ Agrega envío de WhatsApp automático
- ✅ Solo envía WhatsApp si el cliente tiene teléfono registrado

### 2. **Envío Asíncrono con Jobs**
- ✅ Los WhatsApp se envían en segundo plano (no bloquean el proceso)
- ✅ Reintentos automáticos (3 intentos si falla)
- ✅ Espaciado entre envíos para evitar saturación

### 3. **Mensajes Mejorados**
- ✅ Formato profesional con emojis
- ✅ Detalle completo de cada servicio impago
- ✅ Total adeudado destacado
- ✅ Fecha de cada servicio

### 4. **Mejor Logging y Reportes**
- ✅ Resumen en consola con estadísticas
- ✅ Log mejorado con información estructurada
- ✅ Contador de emails y WhatsApps enviados
- ✅ Registro de errores

### 5. **Interfaz de Consola Mejorada**
- ✅ Información visual con emojis
- ✅ Progreso en tiempo real
- ✅ Colores para éxitos, errores y advertencias

---

## 📋 Uso del Comando

### Ejecutar Manualmente

```bash
php artisan app:notificacion-mensual
```

### Salida de Ejemplo

```
🔄 Iniciando notificación mensual de servicios impagos...
📊 Se encontraron 5 clientes con servicios impagos
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📤 Procesando cliente: Juan Pérez
  ✅ Email enviado a: juan@email.com
  ✅ WhatsApp programado para: 5492942506803
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📤 Procesando cliente: María García
  ✅ Email enviado a: maria@email.com
  ⚠️  Cliente sin teléfono registrado
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📊 RESUMEN DE NOTIFICACIONES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  📧 Emails enviados: 5
  📱 WhatsApps programados: 4
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📝 Log guardado en: storage/app/logs/NotificacionMailMensual.txt
✅ Proceso completado exitosamente
```

---

## 📱 Formato del Mensaje WhatsApp

Los clientes recibirán un mensaje como este:

```
⚠️ *RECORDATORIO DE SERVICIOS IMPAGOS*

Hola *Juan Pérez*,

Te recordamos que tienes *3* servicio(s) pendiente(s) de pago:

📋 *Internet 50MB*
   • Cantidad: 1
   • Precio unitario: $5000
   • Total: $5000.00
   • Fecha: 15/09/2025

📋 *Telefonía Básica*
   • Cantidad: 2
   • Precio unitario: $2500
   • Total: $5000.00
   • Fecha: 20/09/2025

━━━━━━━━━━━━━━━━━━━━━
*TOTAL ADEUDADO: $10000.00*
━━━━━━━━━━━━━━━━━━━━━

Por favor, regulariza tu situación a la brevedad.

Cualquier consulta, no dudes en contactarnos.

_Mensaje automático - LLServicios_
```

---

## ⚙️ Configuración Automática (Cron)

### Agregar al Scheduler de Laravel

En `app/Console/Kernel.php`, agrega:

```php
protected function schedule(Schedule $schedule)
{
    // Enviar notificación mensual el día 1 de cada mes a las 9:00 AM
    $schedule->command('app:notificacion-mensual')
             ->monthlyOn(1, '09:00')
             ->timezone('America/Argentina/Buenos_Aires');
    
    // O si prefieres el primer día hábil del mes
    $schedule->command('app:notificacion-mensual')
             ->monthlyOn(1, '09:00')
             ->when(function () {
                 return now()->isWeekday();
             });
}
```

### Opciones de Programación

```php
// Cada primer día del mes a las 9:00
->monthlyOn(1, '09:00')

// Cada día 5 del mes a las 10:00
->monthlyOn(5, '10:00')

// El primer lunes de cada mes
->monthlyOn(1, '09:00')->mondays()

// Todos los días a las 9:00 (para testing)
->dailyAt('09:00')
```

### Activar el Scheduler

Agrega esta línea al crontab del servidor:

```bash
crontab -e
```

```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🔧 Requisitos Previos

### 1. Queue Worker Activo

Para que los WhatsApps se envíen, necesitas tener el queue worker corriendo:

```bash
# En producción (con supervisor)
php artisan queue:work --daemon

# En desarrollo
php artisan queue:work
```

### 2. Variables de Entorno Configuradas

Asegúrate de tener en tu `.env`:

```env
WHATSAPP_API_URL=https://tu-api-whatsapp.com/api
WHATSAPP_API_KEY=tu_api_key
WHATSAPP_INSTANCE_ID=tu_instance_id
```

### 3. Clientes con Teléfono

Verifica que los clientes tengan el campo `telefono` lleno:

```sql
-- Ver clientes sin teléfono
SELECT id, nombre, correo FROM clientes WHERE telefono IS NULL OR telefono = '';

-- Actualizar teléfono de un cliente
UPDATE clientes SET telefono = '5492942506803' WHERE id = 1;
```

---

## 📊 Monitoreo

### Ver Logs en Tiempo Real

```bash
# Log de Laravel (incluye WhatsApp y errores)
tail -f storage/logs/laravel.log

# Log específico de notificaciones mensuales
tail -f storage/app/logs/NotificacionMailMensual.txt

# Filtrar solo WhatsApp
tail -f storage/logs/laravel.log | grep WhatsApp
```

### Ver Jobs en la Cola

```bash
# Ver trabajos pendientes
php artisan queue:listen --timeout=60

# Ver estadísticas de la cola
php artisan queue:work --once

# Ver trabajos fallidos
php artisan queue:failed
```

---

## 🧪 Testing

### Test Manual Paso a Paso

```bash
# 1. Ejecutar el comando
php artisan app:notificacion-mensual

# 2. Verificar que se crearon los Jobs
php artisan queue:work --once

# 3. Revisar logs
tail -50 storage/logs/laravel.log

# 4. Verificar archivo de log
cat storage/app/logs/NotificacionMailMensual.txt
```

### Test con Cliente Específico

Puedes modificar temporalmente la consulta para probar con un cliente específico:

```php
// En NotificacionMensual.php, línea ~35
$clientes = DB::select('SELECT ... WHERE a.cliente_id = b.id AND a.estado = ? AND b.id = ?', 
    ['impago', 1]); // Probar solo con cliente ID 1
```

---

## 🔍 Estructura del Código

### Flujo de Ejecución

```
NotificacionMensual::handle()
    │
    ├─► 1. Obtener clientes con servicios impagos (SQL)
    │
    ├─► 2. Para cada cliente:
    │       ├─► Obtener detalle de servicios impagos
    │       └─► Calcular total adeudado
    │
    ├─► 3. Para cada cliente:
    │       ├─► Enviar Email (sincrónico)
    │       └─► Programar WhatsApp (Job asíncrono)
    │
    ├─► 4. Mostrar resumen en consola
    │
    └─► 5. Guardar log del proceso
```

### Métodos Implementados

| Método | Descripción |
|--------|-------------|
| `handle()` | Método principal que ejecuta todo el proceso |
| `generarMensajeWhatsApp($datos)` | Formatea el mensaje con los servicios impagos |
| `guardarLog($datos, ...)` | Guarda el log mejorado del proceso |

---

## 💡 Casos de Uso

### 1. Notificación Mensual Automática
```bash
# Configurar en cron para el día 1 de cada mes
* * 1 * * cd /path && php artisan app:notificacion-mensual
```

### 2. Notificación Manual (Emergencia)
```bash
# Ejecutar cuando sea necesario
php artisan app:notificacion-mensual
```

### 3. Testing en Desarrollo
```bash
# Ver qué pasaría sin enviar realmente
php artisan app:notificacion-mensual --dry-run  # (requiere implementar flag)
```

---

## 🐛 Resolución de Problemas

### Problema: Los WhatsApps no se envían

**Soluciones:**
1. Verificar que el queue worker esté corriendo:
   ```bash
   ps aux | grep "queue:work"
   ```

2. Ejecutar manualmente el worker:
   ```bash
   php artisan queue:work
   ```

3. Ver jobs fallidos:
   ```bash
   php artisan queue:failed
   php artisan queue:retry all
   ```

### Problema: Clientes no reciben mensajes

**Soluciones:**
1. Verificar formato de teléfono:
   ```sql
   SELECT telefono FROM clientes WHERE telefono IS NOT NULL;
   ```

2. Validar configuración de WhatsApp:
   ```bash
   php artisan tinker
   >>> (new App\Services\WhatsAppService())->validateConfiguration()
   ```

3. Revisar logs de errores:
   ```bash
   tail -100 storage/logs/laravel.log | grep ERROR
   ```

### Problema: Emails se envían pero WhatsApps no

**Causa:** Probablemente el queue worker no está corriendo o falló.

**Solución:**
```bash
# Reiniciar queue worker
php artisan queue:restart

# Ver trabajos en la cola
php artisan queue:listen
```

---

## 📈 Mejoras Futuras Sugeridas

- [ ] Agregar flag `--dry-run` para simular sin enviar
- [ ] Agregar opción `--cliente-id` para probar con un cliente específico
- [ ] Implementar límite de envíos por ejecución
- [ ] Agregar estadísticas a base de datos
- [ ] Crear dashboard de notificaciones enviadas
- [ ] Agregar plantillas personalizables de mensajes
- [ ] Implementar respuestas automáticas de WhatsApp

---

## ✅ Checklist de Implementación

```
✅ Servicio WhatsApp creado e integrado
✅ Job de envío asíncrono implementado
✅ Comando NotificacionMensual actualizado
✅ Mensajes formateados profesionalmente
✅ Logging y reportes mejorados
✅ Documentación completa creada
□ Configurar variables de entorno en producción
□ Activar queue worker en servidor
□ Configurar cron para ejecución automática
□ Probar con clientes reales
□ Monitorear logs por 1 semana
```

---

## 📞 Ejemplo de Uso Completo

```bash
# 1. Configurar entorno
echo "WHATSAPP_API_URL=..." >> .env
echo "WHATSAPP_INSTANCE_ID=..." >> .env

# 2. Iniciar queue worker (en otra terminal)
php artisan queue:work

# 3. Ejecutar notificación
php artisan app:notificacion-mensual

# 4. Monitorear
tail -f storage/logs/laravel.log
```

---

**¡La notificación mensual ahora incluye WhatsApp automático! 🎉**

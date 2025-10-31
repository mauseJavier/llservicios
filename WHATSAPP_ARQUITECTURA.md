# 📊 Arquitectura del Servicio de WhatsApp

## 🔄 Flujo de Ejecución

```
┌─────────────────────────────────────────────────────────────────┐
│                         TU APLICACIÓN                            │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ 1. Llama al servicio
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      WhatsAppService.php                         │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ • sendTextMessage()                                      │   │
│  │ • sendDocument()                                         │   │
│  │ • sendImage()                                            │   │
│  │ • sendCustomMessage()                                    │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ 2. Prepara payload
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Formato del Mensaje                           │
│  {                                                               │
│    "key": {                                                      │
│      "remoteJid": "5492942506803@s.whatsapp.net",               │
│      "fromMe": true,                                            │
│      "id": "UNIQUE_ID"                                          │
│    },                                                            │
│    "message": { "conversation": "texto" },                      │
│    "messageType": "conversation",                               │
│    "instanceId": "...",                                         │
│    "status": "PENDING"                                          │
│  }                                                               │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ 3. Hace HTTP Request
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    API de WhatsApp                               │
│                  (Tu proveedor de API)                           │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ 4. Envía a WhatsApp
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                       📱 WhatsApp                                │
│                  (Usuario final recibe el mensaje)               │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🏗️ Estructura del Proyecto

```
llservicios/
│
├── app/
│   ├── Services/
│   │   └── WhatsAppService.php          ⭐ Servicio principal
│   │
│   ├── Http/Controllers/
│   │   └── WhatsAppController.php       🎮 Controlador API
│   │
│   └── Jobs/
│       └── EnviarWhatsAppJob.php        🔄 Job asíncrono
│
├── config/
│   └── services.php                      ⚙️ Configuración
│
├── routes/
│   ├── api.php                           🛣️ Rutas API
│   └── whatsapp_routes_example.php      📝 Ejemplos de rutas
│
├── tests/
│   └── Unit/
│       └── WhatsAppServiceTest.php      🧪 Tests
│
├── .env                                  🔐 Variables de entorno
├── .env.whatsapp.example                📋 Template de config
│
└── Documentación/
    ├── WHATSAPP_README.md               📖 Guía de inicio
    ├── WHATSAPP_SERVICE.md              📚 Documentación completa
    └── WHATSAPP_EXAMPLES.php            💡 Ejemplos de uso
```

---

## 📡 Endpoints API Disponibles

```
┌────────────────────────────────────────────────────────────┐
│  POST /api/whatsapp/send-text                              │
│  ──────────────────────────────────────────────────────    │
│  Body: { "phone": "...", "message": "..." }                │
│  Respuesta: { "success": true, "data": {...} }            │
└────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────┐
│  POST /api/whatsapp/send-document                          │
│  ──────────────────────────────────────────────────────    │
│  Body: {                                                   │
│    "phone": "...",                                         │
│    "document_url": "https://...",                          │
│    "filename": "documento.pdf",                            │
│    "caption": "..."                                        │
│  }                                                         │
└────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────┐
│  POST /api/whatsapp/send-image                             │
│  ──────────────────────────────────────────────────────    │
│  Body: {                                                   │
│    "phone": "...",                                         │
│    "image_url": "https://...",                             │
│    "caption": "..."                                        │
│  }                                                         │
└────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────┐
│  POST /api/whatsapp/send-custom                            │
│  ──────────────────────────────────────────────────────    │
│  Body: { payload completo según estructura de WhatsApp }   │
└────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────┐
│  GET /api/whatsapp/validate-config                         │
│  ──────────────────────────────────────────────────────    │
│  Respuesta: {                                              │
│    "valid": true,                                          │
│    "errors": [],                                           │
│    "config": {...}                                         │
│  }                                                         │
└────────────────────────────────────────────────────────────┘
```

---

## 🎯 Casos de Uso por Módulo

```
┌─────────────────────────────────────────────────────────────┐
│  📦 MÓDULO DE PAGOS                                         │
├─────────────────────────────────────────────────────────────┤
│  ✅ Confirmar pago recibido                                 │
│  📄 Enviar recibo en PDF                                    │
│  ⚠️ Recordar pago pendiente                                 │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  👥 MÓDULO DE CLIENTES                                      │
├─────────────────────────────────────────────────────────────┤
│  👋 Mensaje de bienvenida                                   │
│  🎂 Felicitación de cumpleaños                              │
│  📊 Estado de cuenta                                        │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  🛠️ MÓDULO DE SERVICIOS                                     │
├─────────────────────────────────────────────────────────────┤
│  🔔 Recordatorio de servicio                                │
│  ✅ Confirmación de servicio realizado                      │
│  ⏰ Notificación de próximo servicio                        │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  🎉 MÓDULO DE MARKETING                                     │
├─────────────────────────────────────────────────────────────┤
│  📢 Promociones y ofertas                                   │
│  🎁 Descuentos especiales                                   │
│  📰 Novedades de la empresa                                 │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔄 Flujo de Envío Asíncrono (Recomendado)

```
┌──────────────┐
│ Controlador  │
│   o Model    │
└──────┬───────┘
       │
       │ 1. Despacha Job
       ▼
┌──────────────────┐
│ EnviarWhatsApp   │
│      Job         │◄─────── 2. Laravel Queue
└──────┬───────────┘         (procesa en background)
       │
       │ 3. Ejecuta
       ▼
┌──────────────────┐
│  WhatsAppService │
└──────┬───────────┘
       │
       │ 4. Envía mensaje
       ▼
┌──────────────────┐
│  API WhatsApp    │
└──────────────────┘

✅ Ventajas:
   • No bloquea la aplicación
   • Reintentos automáticos (3x)
   • Procesamiento en cola
   • Logging automático
```

---

## 🔐 Variables de Configuración

```env
┌────────────────────────────────────────────────────────────┐
│  WHATSAPP_API_URL                                          │
│  ─────────────────────────────────────────────────────     │
│  URL base de tu API de WhatsApp                            │
│  Ejemplo: https://api.whatsapp.com/v1                      │
└────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────┐
│  WHATSAPP_API_KEY                                          │
│  ─────────────────────────────────────────────────────     │
│  Token de autenticación (si lo requiere tu proveedor)      │
│  Opcional                                                  │
└────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────┐
│  WHATSAPP_INSTANCE_ID                                      │
│  ─────────────────────────────────────────────────────     │
│  ID de tu instancia de WhatsApp Business                   │
│  Requerido                                                 │
└────────────────────────────────────────────────────────────┘
```

---

## 📈 Monitoreo y Logging

```
Todos los eventos se registran en:
📁 storage/logs/laravel.log

Eventos registrados:
├── ℹ️  Inicio de envío
├── ✅ Envío exitoso
├── ❌ Errores
├── 🔄 Reintentos
└── 📊 Estadísticas
```

---

## ✅ Checklist de Implementación

```
□ 1. Configurar variables en .env
□ 2. Agregar rutas en routes/api.php
□ 3. Probar con validateConfiguration()
□ 4. Enviar primer mensaje de prueba
□ 5. Integrar en módulos existentes
□ 6. Configurar queue worker para Jobs
□ 7. Configurar logs y monitoreo
□ 8. Ejecutar tests
```

---

## 🚀 Comandos Rápidos

```bash
# Probar configuración
php artisan tinker
>>> $w = new App\Services\WhatsAppService();
>>> $w->validateConfiguration();

# Enviar mensaje de prueba
>>> $w->sendTextMessage('5492942506803', 'Test');

# Ejecutar tests
php artisan test --filter WhatsAppServiceTest

# Ver cola de Jobs
php artisan queue:work

# Ver logs en tiempo real
tail -f storage/logs/laravel.log | grep WhatsApp
```

---

## 📞 Formato de Números Aceptados

```
✅ Válidos:
   5492942506803
   5492942506803@s.whatsapp.net
   +54 9 294 250-6803 (se limpiará automáticamente)

❌ Inválidos:
   294250680 (sin código de país)
   +54-11-1234-5678 (formato incorrecto)
```

---

## 💡 Tips y Buenas Prácticas

```
1. 🔄 Usa Jobs para envíos masivos
2. ⏱️  Espacia envíos (2-3 seg entre mensajes)
3. 📝 Valida números antes de enviar
4. 🔐 Protege endpoints con autenticación
5. 📊 Registra todos los envíos en BD
6. ⚠️  Maneja errores con try-catch
7. 🧪 Escribe tests para casos críticos
8. 📈 Monitorea logs regularmente
```

---

## 🎓 Recursos Adicionales

```
📖 Documentación completa:   WHATSAPP_SERVICE.md
💡 Ejemplos de código:        WHATSAPP_EXAMPLES.php
🚀 Guía de inicio rápido:     WHATSAPP_README.md
🛣️  Rutas de ejemplo:          routes/whatsapp_routes_example.php
⚙️  Configuración template:   .env.whatsapp.example
```

---

**¡Tu servicio de WhatsApp está listo para producción! 🎉**

# 📚 Sistema de Documentación de API

## 🎯 Descripción

Sistema automático de documentación para todos los endpoints de la API. Permite documentar, visualizar y probar fácilmente los endpoints disponibles.

---

## 🚀 Acceso a la Documentación

### Vista HTML Interactiva (Recomendada)
```
https://tu-dominio.com/api-docs
```

### JSON API Endpoints

1. **Documentación Completa:**
```
GET https://tu-dominio.com/api/docs
```

2. **Documentación por Grupo:**
```
GET https://tu-dominio.com/api/docs/cliente
GET https://tu-dominio.com/api/docs/mercadopago
GET https://tu-dominio.com/api/docs/whatsapp
```

3. **Lista de Grupos Disponibles:**
```
GET https://tu-dominio.com/api/docs/groups
```

---

## 📁 Archivos Creados

```
app/
└── Http/
    └── Controllers/
        └── Api/
            └── ApiDocumentationController.php  ⭐ Controlador principal

resources/
└── views/
    └── api-docs.blade.php                      🎨 Vista HTML

routes/
├── api.php                                      🛣️ Rutas API (actualizadas)
└── web.php                                      🌐 Rutas Web (actualizadas)
```

---

## ✨ Características

✅ **Documentación Automática:** Todos los endpoints documentados en un solo lugar  
✅ **Vista Interactiva:** Interfaz web moderna y responsive  
✅ **Búsqueda:** Busca endpoints por nombre, ruta o descripción  
✅ **Filtrado por Grupos:** Organización por módulos (cliente, mercadopago, whatsapp)  
✅ **Ejemplos de Código:** Incluye ejemplos de curl y JSON  
✅ **Respuestas Documentadas:** Muestra ejemplos de respuestas exitosas y errores  
✅ **Sin Autenticación:** Acceso público para facilitar el desarrollo  

---

## 📖 Grupos de Endpoints Documentados

### 👥 Cliente
- Buscar Cliente por DNI, correo o nombre
- Devuelve servicios pagos e impagos

### 💳 MercadoPago
- Crear Preferencia de Pago
- Obtener Información de Pago
- Validar Credenciales

### 📱 WhatsApp
- Enviar Mensaje de Texto
- Enviar Documento
- Enviar Imagen
- Validar Configuración

### 📖 Documentación
- Obtener Documentación Completa
- Obtener Documentación por Grupo
- Listar Grupos Disponibles

---

## 🎨 Vista HTML - Características

### Interfaz
- 🎨 Diseño moderno y limpio
- 📱 Responsive (móvil, tablet, desktop)
- 🔍 Búsqueda en tiempo real
- 🏷️ Filtrado por grupos
- 🌈 Código coloreado

### Información Mostrada
- Método HTTP (GET, POST, PUT, DELETE)
- Ruta del endpoint
- Descripción detallada
- Parámetros requeridos y opcionales
- Ejemplos de request (curl y JSON)
- Ejemplos de respuestas
- Notas importantes
- Estado de autenticación

---

## 💻 Ejemplos de Uso

### Desde el Navegador

**Ver toda la documentación:**
```
http://localhost/api-docs
```

**Ver documentación en JSON:**
```
http://localhost/api/docs
```

**Ver solo endpoints de clientes:**
```
http://localhost/api/docs/cliente
```

### Desde cURL

**Obtener documentación completa:**
```bash
curl http://localhost/api/docs
```

**Obtener grupos disponibles:**
```bash
curl http://localhost/api/docs/groups
```

**Obtener documentación de MercadoPago:**
```bash
curl http://localhost/api/docs/mercadopago
```

### Desde JavaScript

```javascript
// Obtener documentación completa
fetch('/api/docs')
  .then(response => response.json())
  .then(data => {
    console.log('Endpoints disponibles:', data.endpoints);
  });

// Obtener documentación de un grupo
fetch('/api/docs/cliente')
  .then(response => response.json())
  .then(data => {
    console.log('Endpoints de clientes:', data.endpoints);
  });
```

---

## 🔧 Cómo Agregar Nuevos Endpoints a la Documentación

### 1. Abre el Controlador
```
app/Http/Controllers/Api/ApiDocumentationController.php
```

### 2. Agrega tu Endpoint al Array

En el método `getAllEndpoints()`, agrega un nuevo elemento:

```php
[
    'group' => 'nombre_grupo',                    // Cliente, servicio, etc.
    'group_description' => 'Descripción del grupo',
    'name' => 'Nombre del Endpoint',
    'method' => 'GET',                            // GET, POST, PUT, DELETE
    'endpoint' => '/api/ruta/del/endpoint',
    'description' => 'Descripción detallada',
    'authentication' => false,                     // true si requiere auth
    'parameters' => [
        'query' => [                              // O 'body' o 'path'
            [
                'name' => 'parametro',
                'type' => 'string',
                'required' => true,
                'description' => 'Descripción del parámetro',
                'example' => 'valor_ejemplo'
            ]
        ]
    ],
    'request_example' => [
        'curl' => 'curl -X GET "..."',
        'json' => [...]                           // Para requests POST/PUT
    ],
    'response_success' => [
        'code' => 200,
        'example' => [...]
    ],
    'response_error' => [
        'code' => 404,
        'example' => [...]
    ],
    'notes' => [
        'Nota importante 1',
        'Nota importante 2'
    ]
]
```

### 3. Guarda y Recarga

La documentación se actualizará automáticamente.

---

## 📋 Ejemplo Completo de Nuevo Endpoint

```php
[
    'group' => 'servicio',
    'group_description' => 'Endpoints relacionados con servicios',
    'name' => 'Listar Servicios',
    'method' => 'GET',
    'endpoint' => '/api/servicios',
    'description' => 'Obtiene la lista completa de servicios disponibles',
    'authentication' => true,
    'parameters' => [
        'query' => [
            [
                'name' => 'empresa_id',
                'type' => 'integer',
                'required' => false,
                'description' => 'Filtrar por empresa',
                'example' => '1'
            ],
            [
                'name' => 'activo',
                'type' => 'boolean',
                'required' => false,
                'description' => 'Filtrar por estado activo',
                'example' => 'true'
            ]
        ]
    ],
    'request_example' => [
        'curl' => 'curl -X GET "http://localhost/api/servicios?empresa_id=1&activo=true" -H "Authorization: Bearer TOKEN"',
        'url' => 'http://localhost/api/servicios?empresa_id=1&activo=true'
    ],
    'response_success' => [
        'code' => 200,
        'example' => [
            'success' => true,
            'data' => [
                [
                    'id' => 1,
                    'nombre' => 'Internet 50MB',
                    'precio' => 5000,
                    'empresa_id' => 1,
                    'activo' => true
                ]
            ]
        ]
    ],
    'response_error' => [
        'code' => 401,
        'example' => [
            'success' => false,
            'message' => 'No autenticado'
        ]
    ],
    'notes' => [
        'Requiere token de autenticación en el header',
        'Los precios están en pesos argentinos',
        'Máximo 100 resultados por página'
    ]
]
```

---

## 🎯 Casos de Uso

### Para Desarrolladores Frontend
- Consultar endpoints disponibles
- Ver ejemplos de requests y responses
- Copiar ejemplos de código curl
- Entender la estructura de datos

### Para Desarrolladores Backend
- Documentar nuevos endpoints rápidamente
- Mantener documentación actualizada
- Compartir API con el equipo

### Para Testing
- Verificar estructura de respuestas
- Conocer códigos de error posibles
- Probar endpoints manualmente

---

## 🔐 Seguridad

### Documentación Pública
Por defecto, la documentación es pública (sin autenticación).

### Proteger con Autenticación (Opcional)

Si deseas proteger la documentación, edita `routes/web.php` y `routes/api.php`:

```php
// En routes/web.php
Route::middleware(['auth'])->get('/api-docs', function () {
    return view('api-docs');
})->name('api.docs.view');

// En routes/api.php
Route::middleware(['auth:sanctum'])->prefix('docs')->group(function () {
    Route::get('/', [ApiDocumentationController::class, 'index']);
    Route::get('/groups', [ApiDocumentationController::class, 'groups']);
    Route::get('/{group}', [ApiDocumentationController::class, 'show']);
});
```

---

## 🎨 Personalización

### Cambiar Colores

Edita `resources/views/api-docs.blade.php` y busca la sección `<style>`:

```css
.header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    /* Cambia estos colores por los de tu marca */
}
```

### Agregar Logo

En `resources/views/api-docs.blade.php`, dentro del `.header`:

```html
<div class="header">
    <img src="{{ asset('img/logo.png') }}" alt="Logo" style="height: 50px;">
    <h1>📚 {{ config('app.name') }} - API</h1>
    ...
</div>
```

---

## 📊 Estadísticas de Documentación Actual

### Endpoints Documentados:
- ✅ Cliente: 1 endpoint
- ✅ MercadoPago: 3 endpoints
- ✅ WhatsApp: 3 endpoints
- ✅ Documentación: 3 endpoints

### Total: 10 endpoints documentados

---

## 🚀 Comandos Rápidos

### Ver en el Navegador
```bash
# Iniciar servidor
php artisan serve

# Abrir en navegador
http://localhost:8000/api-docs
```

### Obtener JSON
```bash
# Documentación completa
curl http://localhost:8000/api/docs | jq

# Por grupo
curl http://localhost:8000/api/docs/cliente | jq
```

### Verificar Rutas
```bash
# Ver todas las rutas de documentación
php artisan route:list | grep docs
```

---

## 🐛 Troubleshooting

### La vista no se muestra
```bash
# Limpiar cache de vistas
php artisan view:clear
php artisan config:clear
```

### JSON no se carga en la vista
- Verifica que `/api/docs` devuelva JSON válido
- Abre la consola del navegador (F12) para ver errores

### Error 404
- Verifica que las rutas estén en `routes/api.php` y `routes/web.php`
- Ejecuta: `php artisan route:list | grep docs`

---

## 📝 TODO / Mejoras Futuras

- [ ] Agregar autenticación opcional
- [ ] Exportar documentación a Postman/Swagger
- [ ] Agregar ejemplos de código en más lenguajes (PHP, Python, JavaScript)
- [ ] Versioning de la API
- [ ] Tests automatizados de endpoints
- [ ] Modo oscuro en la vista HTML
- [ ] Histórico de cambios en endpoints

---

## 🤝 Contribuir

Para agregar un nuevo endpoint a la documentación:

1. Edita `app/Http/Controllers/Api/ApiDocumentationController.php`
2. Agrega tu endpoint en el método `getAllEndpoints()`
3. Recarga `/api-docs` para ver los cambios

---

## 📞 Soporte

Si tienes preguntas o encuentras problemas:

1. Revisa este README
2. Consulta los logs en `storage/logs/laravel.log`
3. Verifica las rutas con `php artisan route:list`

---

**¡Tu sistema de documentación está listo! 🎉**

Accede a: `http://localhost:8000/api-docs`

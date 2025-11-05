# 📱 Documentación Completa - MercadoPago QR Code

## 📋 Tabla de Contenidos
- [Descripción General](#descripción-general)
- [Configuración Inicial](#configuración-inicial)
- [Estructura del Proyecto](#estructura-del-proyecto)
- [API de Sucursales](#api-de-sucursales)
- [API de Cajas/PDV](#api-de-cajaspdv)
- [API de Órdenes QR](#api-de-órdenes-qr)
- [Ejemplos de Uso](#ejemplos-de-uso)
- [Tipos de QR](#tipos-de-qr)
- [Troubleshooting](#troubleshooting)

---

## 📖 Descripción General

Este módulo permite integrar la funcionalidad de **Códigos QR de Mercado Pago** en tu aplicación Laravel. Con esta integración podrás:

- ✅ Crear y gestionar **sucursales** (stores)
- ✅ Crear y gestionar **cajas/puntos de venta** (POS)
- ✅ Generar **órdenes QR** para cobros
- ✅ Recibir pagos mediante códigos QR
- ✅ Gestionar 3 tipos de QR: **Estático**, **Dinámico** e **Híbrido**

---

## ⚙️ Configuración Inicial

### 1. Variables de Entorno

Agrega estas variables a tu archivo `.env`:

```env
# Credenciales de MercadoPago
MERCADOPAGO_ACCESS_TOKEN=tu_access_token_aqui
MERCADOPAGO_PUBLIC_KEY=tu_public_key_aqui
MERCADOPAGO_USER_ID=tu_user_id_aqui
MERCADOPAGO_SANDBOX=true

# URLs de la aplicación
APP_URL=http://localhost:8000
```

### 2. Obtener el User ID

Si no conoces tu User ID, puedes obtenerlo con este endpoint:

```bash
GET /api/mercadopago/qr/user-id
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "user_id": "123456789",
    "email": "tu-email@example.com"
  }
}
```

### 3. Validar Configuración

Verifica que tu configuración sea correcta:

```bash
GET /api/mercadopago/qr/validate-config
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "valid": true,
    "errors": [],
    "sandbox_mode": true
  }
}
```

---

## 📁 Estructura del Proyecto

```
app/
├── Services/
│   └── MercadoPago/
│       ├── MercadoPagoApiService.php      # Servicio para API general
│       ├── MercadoPagoService.php         # Servicio SDK oficial
│       └── MercadoPagoQRService.php       # 🆕 Servicio para QR
│
└── Http/
    └── Controllers/
        └── MercadoPago/
            ├── MercadoPagoApiController.php        # Controlador API general
            ├── MercadoPagoController.php           # Controlador SDK
            ├── MercadoPagoQRController.php         # 🆕 Controlador QR
            └── MercadoPagoWebhookController.php    # Controlador webhooks

routes/
└── api.php                                 # ✅ Actualizado con rutas QR
```

---

## 🏢 API de Sucursales

### 1. Crear Sucursal

```bash
POST /api/mercadopago/qr/stores
```

**Body:**
```json
{
  "name": "Sucursal Centro",
  "external_id": "SUC001",
  "location": {
    "street_name": "Av. Corrientes",
    "street_number": "1234",
    "city_name": "Buenos Aires",
    "state_name": "Capital Federal",
    "latitude": -34.6037,
    "longitude": -58.3816,
    "reference": "Cerca del Obelisco"
  },
  "business_hours": {
    "monday": [
      {
        "open": "09:00",
        "close": "18:00"
      }
    ],
    "tuesday": [
      {
        "open": "09:00",
        "close": "18:00"
      }
    ]
  }
}
```

**Respuesta Exitosa:**
```json
{
  "success": true,
  "message": "Sucursal creada exitosamente",
  "data": {
    "store_id": "12345678",
    "store": {
      "id": "12345678",
      "name": "Sucursal Centro",
      "external_id": "SUC001",
      "location": {...},
      "business_hours": {...}
    }
  }
}
```

### 2. Listar Sucursales

```bash
GET /api/mercadopago/qr/stores
```

**Respuesta:**
```json
{
  "success": true,
  "data": [
    {
      "id": "12345678",
      "name": "Sucursal Centro",
      "external_id": "SUC001",
      ...
    }
  ]
}
```

### 3. Obtener Sucursal

```bash
GET /api/mercadopago/qr/stores/{storeId}
```

### 4. Actualizar Sucursal

```bash
PUT /api/mercadopago/qr/stores/{storeId}
```

### 5. Eliminar Sucursal

```bash
DELETE /api/mercadopago/qr/stores/{storeId}
```

---

## 🖥️ API de Cajas/PDV

### 1. Crear Caja (POS)

```bash
POST /api/mercadopago/qr/pos
```

**Body:**
```json
{
  "name": "Caja Principal",
  "store_id": "12345678",
  "external_store_id": "SUC001",
  "external_id": "SUC001-CAJA001",
  "fixed_amount": true,
  "category": 621102
}
```

**Parámetros:**
- `fixed_amount`: `true` para QR integrado (monto fijo), `false` para QR abierto
- `category`: Código MCC (opcional)
  - `621102`: Gastronomía
  - Consulta más códigos en la [documentación oficial](https://www.mercadopago.com/developers)

**Respuesta Exitosa:**
```json
{
  "success": true,
  "message": "Caja creada exitosamente",
  "data": {
    "pos_id": "87654321",
    "qr_code_image": "https://www.mercadopago.com/instore/merchant/qr/...",
    "qr_code_template": "https://www.mercadopago.com/instore/merchant/qr/.../template.pdf",
    "uuid": "abc123-def456-ghi789",
    "pos": {
      "id": "87654321",
      "name": "Caja Principal",
      ...
    }
  }
}
```

> **💡 Importante:** Al crear una caja, se genera automáticamente un código QR estático que puedes usar para recibir pagos.

### 2. Obtener Caja

```bash
GET /api/mercadopago/qr/pos/{posId}
```

### 3. Eliminar Caja

```bash
DELETE /api/mercadopago/qr/pos/{posId}
```

---

## 🔲 API de Órdenes QR

### 1. Crear Orden QR

```bash
POST /api/mercadopago/qr/pos/{posId}/orders
```

**Body:**
```json
{
  "title": "Orden de Compra #123",
  "description": "Compra de productos varios",
  "total_amount": 1500.50,
  "external_reference": "ORDER-2024-001",
  "notification_url": "https://tu-dominio.com/webhook",
  "items": [
    {
      "title": "Producto 1",
      "unit_price": 500,
      "quantity": 2,
      "description": "Descripción del producto"
    },
    {
      "title": "Producto 2",
      "unit_price": 500.50,
      "quantity": 1
    }
  ]
}
```

**Respuesta Exitosa:**
```json
{
  "success": true,
  "message": "Orden QR creada exitosamente",
  "data": {
    "qr_data": "00020101021143...",
    "in_store_order_id": "abc123-def456",
    "order": {
      ...detalles de la orden...
    }
  }
}
```

### 2. Obtener Orden QR

```bash
GET /api/mercadopago/qr/pos/{posId}/orders
```

### 3. Eliminar Orden QR

```bash
DELETE /api/mercadopago/qr/pos/{posId}/orders
```

---

## 💻 Ejemplos de Uso

### Ejemplo Completo: Crear Sucursal, Caja y Orden

```javascript
// 1. Crear Sucursal
const store = await fetch('/api/mercadopago/qr/stores', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    name: "Mi Tienda",
    external_id: "STORE-001",
    location: {
      city_name: "Buenos Aires",
      state_name: "Capital Federal",
      latitude: -34.6037,
      longitude: -58.3816
    }
  })
});

const storeData = await store.json();
const storeId = storeData.data.store_id;

// 2. Crear Caja
const pos = await fetch('/api/mercadopago/qr/pos', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    name: "Caja 1",
    store_id: storeId,
    external_store_id: "STORE-001",
    external_id: "POS-001",
    fixed_amount: true
  })
});

const posData = await pos.json();
const posId = posData.data.pos_id;
const qrImageUrl = posData.data.qr_code_image;

// 3. Crear Orden QR
const order = await fetch(`/api/mercadopago/qr/pos/${posId}/orders`, {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    title: "Venta #001",
    total_amount: 1000,
    items: [
      {
        title: "Producto A",
        unit_price: 1000,
        quantity: 1
      }
    ]
  })
});

const orderData = await order.json();
console.log('Orden creada:', orderData);
```

### Ejemplo PHP (Laravel)

```php
<?php

use App\Services\MercadoPago\MercadoPagoQRService;

// En tu controlador
$qrService = new MercadoPagoQRService();

// Crear sucursal
$store = $qrService->createStore([
    'name' => 'Mi Tienda',
    'external_id' => 'STORE-001',
    'location' => [
        'city_name' => 'Buenos Aires',
        'state_name' => 'Capital Federal',
        'latitude' => -34.6037,
        'longitude' => -58.3816
    ]
]);

if ($store['success']) {
    $storeId = $store['store_id'];
    
    // Crear caja
    $pos = $qrService->createPOS([
        'name' => 'Caja 1',
        'store_id' => $storeId,
        'external_store_id' => 'STORE-001',
        'external_id' => 'POS-001',
        'fixed_amount' => true
    ]);
    
    if ($pos['success']) {
        $posId = $pos['pos_id'];
        $qrImage = $pos['qr_code_image'];
        
        // Crear orden
        $order = $qrService->createQROrder($posId, [
            'title' => 'Venta #001',
            'total_amount' => 1000,
            'items' => [...]
        ]);
    }
}
```

---

## 📊 Tipos de QR

### 1. **QR Estático** 
- ✅ Un código QR fijo para múltiples transacciones
- ✅ Se genera automáticamente al crear la caja
- ✅ Ideal para puntos de venta permanentes
- ❌ El monto debe ser ingresado por el cliente

**Uso:**
```php
$pos = $qrService->createPOS([
    'name' => 'Caja Principal',
    'store_id' => $storeId,
    'external_store_id' => 'STORE-001',
    'external_id' => 'POS-001',
    'fixed_amount' => false  // QR abierto (monto variable)
]);

// El QR se obtiene en: $pos['qr_code_image']
```

### 2. **QR Dinámico**
- ✅ Un código QR único por transacción
- ✅ Monto predefinido
- ✅ Mayor seguridad
- ✅ Mejor trazabilidad

**Uso:**
```php
$pos = $qrService->createPOS([
    'fixed_amount' => true  // QR con monto fijo
]);

$order = $qrService->createQROrder($posId, [
    'title' => 'Orden #123',
    'total_amount' => 1500
]);

// El QR dinámico se obtiene en: $order['qr_data']
```

### 3. **QR Híbrido**
- ✅ Combina QR estático y dinámico
- ✅ Puedes usar el QR estático de la caja
- ✅ O generar QR dinámicos cuando lo necesites
- ✅ Máxima flexibilidad

---

## 🔍 Troubleshooting

### Error: "Access Token no configurado"

**Solución:**
1. Verifica que `MERCADOPAGO_ACCESS_TOKEN` esté en tu `.env`
2. Limpia la caché: `php artisan config:clear`
3. Valida la configuración: `GET /api/mercadopago/qr/validate-config`

### Error: "User ID no configurado"

**Solución:**
1. Obtén tu User ID: `GET /api/mercadopago/qr/user-id`
2. Agrega `MERCADOPAGO_USER_ID=tu_user_id` a `.env`
3. Limpia la caché: `php artisan config:clear`

### Error 400: "Invalid store_id"

**Solución:**
- Asegúrate de usar el `store_id` correcto (el devuelto por la API al crear la sucursal)
- No uses el `external_id`, usa el `id` interno de Mercado Pago

### Error: "QR code not generated"

**Solución:**
- Verifica que la caja se haya creado correctamente
- El QR estático se genera automáticamente al crear la caja
- Para QR dinámicos, debes crear una orden

---

## 📚 Recursos Adicionales

- [Documentación Oficial de Mercado Pago - QR Code](https://www.mercadopago.com/developers/es/docs/qr-code/introduction)
- [API Reference - QR Code](https://www.mercadopago.com/developers/es/reference/qr-code/_pos/post)
- [Webhooks de Mercado Pago](https://www.mercadopago.com/developers/es/docs/your-integrations/notifications/webhooks)

---

## 🎯 Testing

### Modo Sandbox

Asegúrate de tener `MERCADOPAGO_SANDBOX=true` en tu `.env` para testing.

### Tarjetas de Prueba

Usa estas tarjetas para probar pagos:

| Tarjeta | Número | CVV | Fecha |
|---------|--------|-----|-------|
| Visa | 4509 9535 6623 3704 | 123 | 11/25 |
| Mastercard | 5031 7557 3453 0604 | 123 | 11/25 |

---

## 📞 Soporte

Si tienes problemas o dudas:
1. Revisa esta documentación
2. Consulta los logs en `storage/logs/laravel.log`
3. Verifica la [documentación oficial](https://www.mercadopago.com/developers)

---

**🎉 ¡Listo! Ya tienes toda la funcionalidad de QR de Mercado Pago integrada en tu proyecto.**

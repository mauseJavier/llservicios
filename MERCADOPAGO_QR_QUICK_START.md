# 🚀 Guía Rápida - MercadoPago QR

## ⚡ Setup en 5 Minutos

### 1️⃣ Configurar .env

```env
MERCADOPAGO_ACCESS_TOKEN=tu_token_aqui
MERCADOPAGO_USER_ID=tu_user_id_aqui
MERCADOPAGO_SANDBOX=true
```

### 2️⃣ Obtener User ID (si no lo tienes)

```bash
curl http://localhost:8000/api/mercadopago/qr/user-id
```

### 3️⃣ Crear tu primera sucursal

```bash
curl -X POST http://localhost:8000/api/mercadopago/qr/stores \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Mi Tienda",
    "external_id": "STORE-001",
    "location": {
      "city_name": "Buenos Aires",
      "state_name": "Capital Federal",
      "latitude": -34.6037,
      "longitude": -58.3816
    }
  }'
```

**Respuesta:** Guarda el `store_id`

### 4️⃣ Crear tu primera caja

```bash
curl -X POST http://localhost:8000/api/mercadopago/qr/pos \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Caja 1",
    "store_id": "TU_STORE_ID",
    "external_store_id": "STORE-001",
    "external_id": "POS-001",
    "fixed_amount": true
  }'
```

**Respuesta:** 
- `qr_code_image`: URL de tu código QR estático ✅
- `pos_id`: Guarda este ID para crear órdenes

### 5️⃣ Crear una orden de pago

```bash
curl -X POST http://localhost:8000/api/mercadopago/qr/pos/TU_POS_ID/orders \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Venta #001",
    "total_amount": 1000,
    "items": [
      {
        "title": "Producto A",
        "unit_price": 1000,
        "quantity": 1
      }
    ]
  }'
```

---

## 📱 Mostrar QR en tu Frontend

### HTML Simple

```html
<div class="qr-container">
  <h3>Escanea para pagar</h3>
  <img src="URL_DEL_QR" alt="Código QR" />
  <p>Total: $1,000.00</p>
</div>
```

### React

```jsx
function QRDisplay({ qrUrl, amount }) {
  return (
    <div className="qr-payment">
      <h3>Escanea con Mercado Pago</h3>
      <img src={qrUrl} alt="QR Code" />
      <p>Total: ${amount}</p>
    </div>
  );
}
```

### Vue

```vue
<template>
  <div class="qr-payment">
    <h3>Escanea para pagar</h3>
    <img :src="qrUrl" alt="QR Code" />
    <p>Total: ${{ amount }}</p>
  </div>
</template>

<script>
export default {
  props: ['qrUrl', 'amount']
}
</script>
```

---

## 🔄 Flujo Completo en PHP

```php
<?php

use App\Services\MercadoPago\MercadoPagoQRService;

// Instanciar servicio
$qrService = new MercadoPagoQRService();

// 1. Crear sucursal (una vez)
$store = $qrService->createStore([
    'name' => 'Mi Tienda',
    'external_id' => 'STORE-' . time(),
    'location' => [
        'city_name' => 'Buenos Aires',
        'state_name' => 'Capital Federal',
        'latitude' => -34.6037,
        'longitude' => -58.3816
    ]
]);

$storeId = $store['store_id'];

// 2. Crear caja (una vez por caja física)
$pos = $qrService->createPOS([
    'name' => 'Caja Principal',
    'store_id' => $storeId,
    'external_store_id' => 'STORE-' . time(),
    'external_id' => 'POS-' . time(),
    'fixed_amount' => true
]);

$posId = $pos['pos_id'];
$qrStaticImage = $pos['qr_code_image']; // QR estático

// 3. Para cada venta, crear una orden
$order = $qrService->createQROrder($posId, [
    'title' => 'Venta #' . rand(1000, 9999),
    'total_amount' => 1500.50,
    'description' => 'Compra de productos',
    'items' => [
        [
            'title' => 'Producto A',
            'unit_price' => 750.25,
            'quantity' => 2
        ]
    ]
]);

// Mostrar QR dinámico (opcional)
$qrDynamicData = $order['qr_data'];

// En tu vista:
echo "<img src='{$qrStaticImage}' alt='QR Estático' />";
```

---

## 🎯 Casos de Uso Comunes

### Caso 1: Tienda Física con Caja Fija

**Solución:** QR Estático

```php
// Setup una sola vez
$pos = $qrService->createPOS([...]);
$qrImage = $pos['qr_code_image'];

// Imprime el QR y pégalo en la caja
// Los clientes escanean y pagan
// Recibes notificaciones en tu webhook
```

### Caso 2: E-commerce con Órdenes Específicas

**Solución:** QR Dinámico

```php
// Para cada compra online
$order = $qrService->createQROrder($posId, [
    'title' => 'Orden #' . $orderId,
    'total_amount' => $total,
    'external_reference' => 'ORDER-' . $orderId
]);

// Muestra el QR al cliente
// QR único para esa compra específica
```

### Caso 3: Restaurant con Múltiples Mesas

**Solución:** QR Híbrido

```php
// Crea una caja por mesa
foreach ($mesas as $mesa) {
    $pos = $qrService->createPOS([
        'name' => 'Mesa ' . $mesa->numero,
        'external_id' => 'MESA-' . $mesa->id,
        ...
    ]);
    
    // Imprime el QR en la mesa
    // Cuando hay una cuenta, crea una orden
    $order = $qrService->createQROrder($pos['pos_id'], [
        'title' => 'Mesa ' . $mesa->numero,
        'total_amount' => $cuenta->total
    ]);
}
```

---

## 📊 Endpoints Disponibles

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `POST` | `/api/mercadopago/qr/stores` | Crear sucursal |
| `GET` | `/api/mercadopago/qr/stores` | Listar sucursales |
| `GET` | `/api/mercadopago/qr/stores/{id}` | Ver sucursal |
| `PUT` | `/api/mercadopago/qr/stores/{id}` | Actualizar sucursal |
| `DELETE` | `/api/mercadopago/qr/stores/{id}` | Eliminar sucursal |
| `POST` | `/api/mercadopago/qr/pos` | Crear caja |
| `GET` | `/api/mercadopago/qr/pos/{id}` | Ver caja |
| `DELETE` | `/api/mercadopago/qr/pos/{id}` | Eliminar caja |
| `POST` | `/api/mercadopago/qr/pos/{id}/orders` | Crear orden QR |
| `GET` | `/api/mercadopago/qr/pos/{id}/orders` | Ver orden QR |
| `DELETE` | `/api/mercadopago/qr/pos/{id}/orders` | Eliminar orden QR |
| `GET` | `/api/mercadopago/qr/user-id` | Obtener User ID |
| `GET` | `/api/mercadopago/qr/validate-config` | Validar config |

---

## ⚠️ Importante Recordar

1. **User ID**: Necesario en `.env` - obtenerlo con `/user-id`
2. **Store ID**: Usar el ID devuelto por la API, no el `external_id`
3. **POS ID**: Guardar para crear órdenes después
4. **QR Estático**: Se genera automáticamente al crear la caja
5. **QR Dinámico**: Se genera al crear una orden
6. **Sandbox**: Activar en testing, desactivar en producción

---

## 🐛 Solución Rápida de Errores

| Error | Solución |
|-------|----------|
| "Access Token no configurado" | Agregar `MERCADOPAGO_ACCESS_TOKEN` al `.env` |
| "User ID no configurado" | Agregar `MERCADOPAGO_USER_ID` al `.env` |
| 400 - Invalid store_id | Usar el `id` devuelto por la API, no el `external_id` |
| 404 - Not found | Verificar que el recurso exista |
| 401 - Unauthorized | Verificar credenciales |

---

## 📞 Testing Rápido

```bash
# 1. Validar configuración
curl http://localhost:8000/api/mercadopago/qr/validate-config

# 2. Obtener User ID
curl http://localhost:8000/api/mercadopago/qr/user-id

# 3. Listar sucursales
curl http://localhost:8000/api/mercadopago/qr/stores

# 4. Ver una caja específica
curl http://localhost:8000/api/mercadopago/qr/pos/TU_POS_ID
```

---

**🎉 ¡Listo para empezar a cobrar con QR!**

Para más detalles, consulta: `MERCADOPAGO_QR_DOCUMENTATION.md`

# Sistema de Cobro con Código QR de Mercado Pago

## 📋 Descripción General

Este sistema permite crear órdenes de pago con montos personalizados y detectar en tiempo real cuando un cliente completa el pago escaneando el código QR de Mercado Pago.

## 🏗️ Arquitectura del Sistema

### Componentes Principales

1. **Base de Datos**
   - `mercadopago_stores`: Sucursales/tiendas
   - `mercadopago_pos`: Cajas/Puntos de venta con código QR físico
   - `mercadopago_qr_orders`: Órdenes de pago individuales con seguimiento de estado

2. **Servicios**
   - `MercadoPagoQRService`: Maneja comunicación con API de Mercado Pago
   - `MercadoPagoWebhookController`: Recibe notificaciones de pagos completados

3. **Componente Livewire**
   - `QRPayment`: Interfaz interactiva para crear órdenes y detectar pagos

4. **Modelos**
   - `MercadoPagoStore`: Tienda/sucursal
   - `MercadoPagoPOS`: Caja con QR físico
   - `MercadoPagoQROrder`: Orden de pago individual

## 🔄 Flujo de Pago Completo

### 1. Preparación (Una sola vez)
```
Empresa → Crea Sucursal → Crea Caja/POS → Obtiene QR físico → Imprime QR en mostrador
```

### 2. Proceso de Cobro (Por cada venta)

#### Paso A: Cajero crea la orden
```php
// Vista Blade con Livewire
@livewire('qr-payment', ['posId' => $cajaId])

// El cajero ingresa:
- Monto: $1500.50
- Descripción: "Venta de productos"

// Al hacer clic en "Generar QR":
```

#### Paso B: Se crea la orden en Mercado Pago
```php
// En QRPayment.php -> createOrder()

1. Genera referencia única: "QR-1730576400-ABC123"
2. Llama a API de Mercado Pago:
   POST https://api.mercadopago.com/instore/qr/seller/collectors/{user_id}/stores/{store_id}/pos/{pos_id}/orders
   
3. Guarda orden en BD con estado "pending"
4. Activa polling cada 3 segundos
5. Muestra pantalla de espera al cajero
```

#### Paso C: Cliente paga
```
Cliente → Abre app Mercado Pago → Escanea QR físico del mostrador → 
Ve monto $1500.50 → Confirma pago → Pago procesado
```

#### Paso D: Notificación automática (Webhook)
```php
// Mercado Pago envía POST a: 
// https://tudominio.com/api/mercadopago/webhook/qr

// MercadoPagoWebhookController.php recibe:
{
  "action": "payment.created",
  "data": {
    "id": 123456789  // ID del pago
  }
}

// El controlador:
1. Obtiene detalles completos del pago
2. Busca la orden por external_reference
3. Actualiza estado a "paid"
4. Guarda payment_id y paid_at
```

#### Paso E: Detección en tiempo real (Polling)
```javascript
// En la vista Blade cada 3 segundos:
setInterval(() => {
    @this.call('checkPaymentStatus');
}, 3000);

// En QRPayment.php -> checkPaymentStatus():
1. Consulta orden en BD
2. Si status = "paid":
   - Detiene polling
   - Muestra pantalla de éxito
   - Reproduce sonido (opcional)
   - Envía notificación del navegador (opcional)
```

## 🗄️ Estructura de Base de Datos

### Tabla: mercadopago_qr_orders
```sql
CREATE TABLE mercadopago_qr_orders (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    mercadopago_pos_id BIGINT,  -- FK a la caja
    external_reference VARCHAR(255) UNIQUE,  -- "QR-1730576400-ABC123"
    in_store_order_id VARCHAR(255),  -- ID de MP
    total_amount DECIMAL(10,2),  -- 1500.50
    status VARCHAR(50),  -- pending, paid, cancelled, expired
    payment_id VARCHAR(255),  -- ID del pago cuando se completa
    payment_status VARCHAR(50),  -- approved, rejected, etc.
    items JSON,  -- Detalles de los productos
    notification_data JSON,  -- Datos del webhook
    paid_at TIMESTAMP,  -- Cuándo se pagó
    expires_at TIMESTAMP,  -- Expira en 10 minutos
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_external_reference (external_reference),
    INDEX idx_status (status),
    INDEX idx_payment_id (payment_id)
);
```

### Estados de la Orden
- `pending`: Esperando pago
- `paid`: Pagado exitosamente
- `cancelled`: Cancelado por el cajero
- `expired`: Expiró sin pago (10 minutos)

## 🔌 API y Endpoints

### Webhook de Mercado Pago
```
POST /api/mercadopago/webhook/qr
```
**Excluido de CSRF** (configurado en `VerifyCsrfToken.php`)

### Configuración en Mercado Pago
1. Ir a: https://www.mercadopago.com.ar/developers/panel/app
2. Seleccionar tu aplicación
3. Ir a "Webhooks"
4. Agregar URL: `https://tudominio.com/api/mercadopago/webhook/qr`
5. Seleccionar tópico: `payment`

## 💻 Uso del Componente

### En una Vista Blade
```blade
@extends('layouts.app')

@section('content')
    <div class="container">
        @livewire('qr-payment', ['posId' => 1])
    </div>
@endsection

@push('scripts')
<script>
    // Opcional: Solicitar permiso para notificaciones
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
</script>
@endpush
```

### En un Controlador
```php
public function mostrarCobro($posId)
{
    $pos = MercadoPagoPOS::findOrFail($posId);
    $poses = MercadoPagoPOS::with('store')->get();
    
    return view('mercadopago.qr-cobro', compact('pos', 'poses'));
}
```

## 🎯 Eventos Livewire

El componente dispara eventos que puedes escuchar:

### Evento: qr-created
```javascript
$wire.on('qr-created', (event) => {
    console.log('Orden creada:', event.orderId, event.amount);
    // Aquí podrías mostrar un modal, reproducir sonido, etc.
});
```

### Evento: payment-successful
```javascript
$wire.on('payment-successful', (event) => {
    console.log('¡Pago exitoso!', event.paymentId);
    
    // Reproducir sonido
    const audio = new Audio('/sounds/success.mp3');
    audio.play();
    
    // Notificación del navegador
    if (Notification.permission === 'granted') {
        new Notification('¡Pago Recibido!', {
            body: `Monto: $${event.amount}`,
            icon: '/images/success-icon.png'
        });
    }
    
    // Aquí podrías:
    // - Abrir caja registradora
    // - Imprimir ticket
    // - Actualizar inventario
    // - Enviar recibo por email
});
```

## 🔐 Seguridad

### Webhook Signature (Recomendado)
Para verificar que los webhooks vienen realmente de Mercado Pago:

```php
// En MercadoPagoWebhookController.php

public function handleQRWebhook(Request $request)
{
    // Validar firma
    $signature = $request->header('X-Signature');
    $requestId = $request->header('X-Request-Id');
    
    if (!$this->validateWebhookSignature($signature, $requestId, $request->all())) {
        return response()->json(['error' => 'Invalid signature'], 401);
    }
    
    // ... resto del código
}

private function validateWebhookSignature($signature, $requestId, $data)
{
    $secret = config('services.mercadopago.webhook_secret');
    $manifest = "id:{$requestId};request-id:{$requestId}";
    
    $hash = hash_hmac('sha256', $manifest . json_encode($data), $secret);
    
    return hash_equals($signature, $hash);
}
```

## 🧪 Testing

### Crear Orden de Prueba
```php
// En Tinker o test
$pos = MercadoPagoPOS::first();
$order = MercadoPagoQROrder::create([
    'mercadopago_pos_id' => $pos->id,
    'external_reference' => 'TEST-' . time(),
    'total_amount' => 100.00,
    'status' => 'pending',
    'items' => [['title' => 'Test', 'unit_price' => 100, 'quantity' => 1]],
    'expires_at' => now()->addMinutes(10)
]);
```

### Simular Webhook
```bash
curl -X POST http://localhost/api/mercadopago/webhook/qr \
  -H "Content-Type: application/json" \
  -d '{
    "action": "payment.created",
    "api_version": "v1",
    "data": {
      "id": "123456789"
    },
    "date_created": "2025-01-02T10:00:00Z",
    "id": 12345,
    "live_mode": false,
    "type": "payment",
    "user_id": "USER_ID"
  }'
```

## 📊 Monitoreo y Logs

### Ver Logs
```bash
# Logs del webhook
tail -f storage/logs/laravel.log | grep "Webhook QR"

# Logs de órdenes
tail -f storage/logs/laravel.log | grep "QR Order"
```

### Consultas Útiles
```php
// Órdenes pendientes
$pendientes = MercadoPagoQROrder::pending()->get();

// Órdenes pagadas hoy
$hoy = MercadoPagoQROrder::paid()
    ->whereDate('paid_at', today())
    ->sum('total_amount');

// Órdenes expiradas
$expiradas = MercadoPagoQROrder::where('status', 'expired')->count();
```

## 🚀 Próximas Mejoras

- [ ] Agregar impresión automática de tickets
- [ ] Integrar con sistema de inventario
- [ ] Enviar recibo por email/WhatsApp
- [ ] Dashboard de ventas en tiempo real
- [ ] Reportes de ventas por caja
- [ ] Soporte para múltiples items
- [ ] Integración con programa de puntos
- [ ] Devoluciones/reembolsos

## 📝 Notas Importantes

1. **Expiración**: Las órdenes expiran en 10 minutos automáticamente
2. **Polling**: Se verifica el estado cada 3 segundos (configurable)
3. **Webhooks**: Son la forma más confiable de detectar pagos
4. **QR Reutilizable**: El QR físico se usa para todas las ventas de esa caja
5. **Referencias Únicas**: Cada orden tiene un external_reference único

## 🆘 Troubleshooting

### El webhook no llega
- Verificar URL en panel de Mercado Pago
- Verificar que el dominio sea accesible públicamente
- Verificar exclusión de CSRF
- Revisar logs del servidor

### El polling no detecta el pago
- Verificar que el webhook esté funcionando
- Verificar conexión a base de datos
- Ver logs de Laravel
- Verificar estado de la orden en BD

### Error al crear orden
- Verificar credenciales de Mercado Pago
- Verificar que el POS existe en Mercado Pago
- Verificar formato de datos enviados
- Ver respuesta completa de la API

## 📚 Documentación de Referencia

- [Mercado Pago QR Docs](https://www.mercadopago.com.ar/developers/es/docs/qr-code/introduction)
- [Webhooks Guide](https://www.mercadopago.com.ar/developers/es/docs/your-integrations/notifications/webhooks)
- [Livewire Docs](https://livewire.laravel.com/)

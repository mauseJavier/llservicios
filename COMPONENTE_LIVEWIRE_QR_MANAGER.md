# 🎯 Componente Livewire: Gestión de QR MercadoPago

## 📋 Descripción

Componente Livewire completo para gestionar tiendas y cajas de MercadoPago para implementar códigos QR de pago. El componente utiliza las credenciales de MercadoPago almacenadas en cada empresa.

---

## 📁 Archivos Creados

### 1. **Migraciones**
- `database/migrations/2025_11_02_000001_create_mercadopago_stores_table.php`
  - Tabla `mercadopago_stores`: Almacena las tiendas de cada empresa
  - Tabla `mercadopago_pos`: Almacena las cajas (puntos de venta) de cada tienda

### 2. **Modelos**
- `app/Models/MercadoPagoStore.php`: Modelo para tiendas
- `app/Models/MercadoPagoPOS.php`: Modelo para cajas (POS)

### 3. **Componente Livewire**
- `app/Livewire/MercadoPagoQrManager.php`: Lógica del componente
- `resources/views/livewire/mercado-pago-qr-manager.blade.php`: Vista del componente

### 4. **Actualizaciones**
- `app/Models/Empresa.php`: Agregadas relaciones con tiendas y método `hasMercadoPagoConfigured()`
- `routes/web.php`: Agregada ruta `/mercadopago/qr-manager`

---

## 🚀 Instalación

### 1. **Ejecutar Migraciones**

```bash
php artisan migrate
```

Esto creará las tablas:
- `mercadopago_stores`
- `mercadopago_pos`

### 2. **Limpiar Cache**

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

---

## 🔧 Configuración Previa

### Configurar Credenciales de MercadoPago en la Empresa

Cada empresa debe tener configuradas sus credenciales de MercadoPago en la base de datos:

```sql
UPDATE empresas 
SET MP_ACCESS_TOKEN = 'APP_USR-xxxxxxxxxxxx',
    MP_PUBLIC_KEY = 'APP_USR-xxxxxxxxxxxx'
WHERE id = 1;
```

O desde la interfaz de administración de empresas.

---

## 📱 Uso del Componente

### Acceder al Componente

**URL:** `/mercadopago/qr-manager`

**Ruta nombrada:** `mercadopago.qr-manager`

```php
// Desde un controlador
return redirect()->route('mercadopago.qr-manager');

// En una vista blade
<a href="{{ route('mercadopago.qr-manager') }}">Gestionar QR MercadoPago</a>
```

---

## ✨ Funcionalidades

### 🏪 Gestión de Tiendas

#### **Crear Tienda**
1. Click en el botón "Nueva Tienda"
2. Completar el formulario:
   - Nombre de la tienda (requerido)
   - Dirección completa (calle, número, ciudad, provincia)
   - Código postal (opcional)
   - Coordenadas GPS (opcional, pero recomendado)
3. Click en "Crear"

**Lo que sucede:**
- Se crea la tienda en la base de datos local
- Se registra la tienda en MercadoPago mediante la API
- Se almacena el ID de la tienda de MercadoPago

#### **Editar Tienda**
1. Click en el botón de editar (ícono lápiz)
2. Modificar los datos necesarios
3. Click en "Actualizar"

#### **Eliminar Tienda**
1. Click en el botón de eliminar (ícono basura)
2. Confirmar la eliminación
3. Se elimina la tienda y **todas sus cajas** automáticamente

---

### 💰 Gestión de Cajas (POS)

#### **Crear Caja**
1. Click en el botón "+ Caja" en la tienda deseada
2. Completar el formulario:
   - Nombre de la caja (requerido)
   - Categoría (opcional)
   - Monto Fijo (switch)
     - **Activado**: El cliente no puede modificar el monto
     - **Desactivado**: El cliente puede ingresar el monto a pagar
3. Click en "Crear"

**Lo que sucede:**
- Se crea la caja en la base de datos local
- Se registra la caja en MercadoPago mediante la API
- **Se genera automáticamente un código QR estático**
- Se almacena la imagen del QR y su URL

#### **Ver QR de una Caja**
- El QR se muestra directamente en la tarjeta de cada caja
- Click en el ícono de QR para abrir en pantalla completa
- Click en el botón de descarga para guardar el QR

#### **Eliminar Caja**
1. Click en el botón de eliminar (ícono basura) en la caja
2. Confirmar la eliminación
3. Se elimina la caja y su QR

---

## 🎨 Características de la Interfaz

### **Alertas y Notificaciones**
- ✅ Mensajes de éxito (verde)
- ❌ Mensajes de error (rojo)
- ⚠️ Advertencias (amarillo)
- ℹ️ Información (azul)

### **Loading States**
- Spinner de carga durante operaciones asíncronas
- Botones deshabilitados mientras se procesa
- Overlay de loading en pantalla completa

### **Validaciones**
- Validación en tiempo real de formularios
- Mensajes de error debajo de cada campo
- Confirmación antes de eliminar

### **Responsive Design**
- Adaptado para móviles, tablets y desktop
- Cards que se reorganizan según el tamaño de pantalla
- Modales adaptables

---

## 📊 Estructura de Datos

### **Tabla: mercadopago_stores**

```sql
- id
- empresa_id (FK)
- external_id (único)
- mp_store_id (ID de MercadoPago)
- name
- location (JSON)
- address_street_name
- address_street_number
- address_city
- address_state
- address_zip_code
- address_country
- address_latitude
- address_longitude
- created_at
- updated_at
```

### **Tabla: mercadopago_pos**

```sql
- id
- mercadopago_store_id (FK)
- external_id (único)
- mp_pos_id (ID de MercadoPago)
- name
- fixed_amount ('true' o 'false')
- category
- qr_code (imagen base64)
- qr_url (URL pública del QR)
- active (boolean)
- created_at
- updated_at
```

---

## 🔐 Seguridad

### **Validaciones de Seguridad**
- Solo usuarios autenticados pueden acceder
- Solo puede gestionar tiendas/cajas de su propia empresa
- Validación de empresa_id en todas las operaciones
- Middleware `RolAdmin` requerido

### **Protección de Datos**
- Las credenciales de MercadoPago se obtienen desde la empresa
- No se exponen access tokens en el frontend
- Validación de permisos en cada operación CRUD

---

## 🔄 Flujo de Trabajo Completo

### **Configuración Inicial**

```
1. Admin configura credenciales MP en la Empresa
2. Usuario accede a /mercadopago/qr-manager
3. Sistema valida credenciales de la empresa
```

### **Crear Primera Tienda y Caja**

```
1. Usuario hace click en "Nueva Tienda"
2. Completa datos de dirección y ubicación
3. Se crea la tienda en BD local y MercadoPago
4. Usuario hace click en "+ Caja" en la tienda
5. Completa nombre y configuración de la caja
6. Se crea la caja con QR estático automático
7. QR se muestra en la interfaz
```

### **Usar el QR para Cobros**

```
1. Usuario descarga o imprime el QR de la caja
2. Coloca el QR en el mostrador/caja física
3. Cliente escanea el QR con la app de MercadoPago
4. Cliente ingresa el monto (si fixed_amount = false)
5. Cliente confirma el pago
6. MercadoPago procesa el pago
7. Webhook notifica al sistema (implementar en futuro)
```

---

## 🧪 Testing

### **Probar Manualmente**

```bash
# 1. Acceder al componente
http://localhost:8000/mercadopago/qr-manager

# 2. Verificar que muestra:
- Nombre de la empresa
- Mensaje de credenciales (configuradas o no)
- Botón "Nueva Tienda" (si está configurado)

# 3. Crear tienda de prueba
- Nombre: "Tienda Test"
- Dirección: Calle Falsa 123, Springfield, Buenos Aires

# 4. Crear caja de prueba
- Nombre: "Caja 1"
- Categoría: Tienda
- Monto Fijo: Activado

# 5. Verificar que se genera el QR
```

### **Verificar en la Base de Datos**

```sql
-- Ver tiendas creadas
SELECT * FROM mercadopago_stores WHERE empresa_id = 1;

-- Ver cajas creadas
SELECT * FROM mercadopago_pos WHERE mercadopago_store_id = 1;

-- Ver relación completa
SELECT 
    e.name AS empresa,
    s.name AS tienda,
    s.address_city AS ciudad,
    p.name AS caja,
    p.active AS activa,
    p.qr_url
FROM empresas e
JOIN mercadopago_stores s ON s.empresa_id = e.id
JOIN mercadopago_pos p ON p.mercadopago_store_id = s.id
WHERE e.id = 1;
```

---

## 🐛 Troubleshooting

### **Error: "Las credenciales de MercadoPago no están configuradas"**

**Solución:**
```sql
UPDATE empresas 
SET MP_ACCESS_TOKEN = 'tu_token_aqui',
    MP_PUBLIC_KEY = 'tu_public_key_aqui'
WHERE id = tu_empresa_id;
```

### **Error: "Usuario sin empresa asignada"**

**Solución:**
```sql
UPDATE users 
SET empresa_id = 1 
WHERE id = tu_user_id;
```

### **Error: "Error al crear la tienda en MercadoPago"**

**Causas posibles:**
1. Access token inválido o expirado
2. Problema de conectividad con la API de MercadoPago
3. Datos de dirección incompletos

**Verificar:**
```bash
# Ver logs de Laravel
tail -f storage/logs/laravel.log
```

### **El QR no se muestra**

**Verificar:**
```sql
SELECT qr_code, qr_url FROM mercadopago_pos WHERE id = tu_pos_id;
```

Si `qr_code` y `qr_url` están vacíos, la API no devolvió el QR. Verificar credenciales.

---

## 📚 Relaciones del Modelo

```
Empresa
  └── hasMany → MercadoPagoStore
        └── hasMany → MercadoPagoPOS

User
  └── belongsTo → Empresa
```

---

## 🎯 Próximas Mejoras

### **Implementaciones Futuras**

1. **Webhooks para notificaciones de pago**
   - Detectar cuando un cliente paga mediante el QR
   - Actualizar estado de pagos en tiempo real
   - Notificar al usuario sobre pagos recibidos

2. **Órdenes QR Dinámicas**
   - Crear QR específicos para cada venta
   - Con monto y descripción predefinidos
   - Válidos por tiempo limitado

3. **Reportes y estadísticas**
   - Dashboard con pagos por QR
   - Gráficos de ventas por caja/tienda
   - Exportar reportes en PDF/Excel

4. **Gestión de múltiples tiendas**
   - Vista de mapa con todas las tiendas
   - Filtros y búsqueda avanzada
   - Bulk operations

5. **Configuración avanzada de cajas**
   - Horarios de operación
   - Límites de monto
   - Descuentos y promociones

---

## 📖 Documentación Relacionada

- [MERCADOPAGO_QR_DOCUMENTATION.md](./MERCADOPAGO_QR_DOCUMENTATION.md) - Documentación completa de la API QR
- [MERCADOPAGO_QR_QUICK_START.md](./MERCADOPAGO_QR_QUICK_START.md) - Guía rápida de inicio
- [REORGANIZACION_PROYECTO.md](./REORGANIZACION_PROYECTO.md) - Cambios en la estructura del proyecto

---

## 🤝 Contribuciones

Para agregar nuevas funcionalidades al componente:

1. Actualizar el componente Livewire: `app/Livewire/MercadoPagoQrManager.php`
2. Actualizar la vista: `resources/views/livewire/mercado-pago-qr-manager.blade.php`
3. Agregar migraciones si se necesitan nuevas tablas/campos
4. Actualizar esta documentación

---

## 📝 Notas Importantes

- **Las credenciales de MercadoPago deben estar en modo PRODUCCIÓN para uso real**
- **En desarrollo, usar credenciales de SANDBOX**
- **Los QR estáticos son permanentes, los dinámicos tienen tiempo de vida limitado**
- **Cada empresa tiene sus propias tiendas y cajas aisladas**
- **El componente requiere Livewire 3.x**

---

**✅ Componente Livewire creado exitosamente!**

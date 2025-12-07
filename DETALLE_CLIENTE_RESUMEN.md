# Resumen - Componente Detalle Cliente

## 🎯 Descripción
Componente Livewire que muestra información completa del cliente y sus servicios vinculados desde la tabla `cliente_servicio`.

## 📁 Archivos Creados

1. **Componente PHP**
   - Ubicación: `/app/Livewire/DetalleCliente.php`
   
2. **Vista Blade**
   - Ubicación: `/resources/views/livewire/detalle-cliente.blade.php`

3. **Ruta**
   - Archivo: `/routes/web.php`
   - URL: `/DetalleCliente/{clienteId}`
   - Nombre: `DetalleCliente`

## 📁 Archivos Modificados

1. **Rutas Web** (`/routes/web.php`)
   - Se agregó la ruta para el componente DetalleCliente

2. **Vista VerCliente** (`/resources/views/livewire/ver-cliente/ver-cliente.blade.php`)
   - Se agregó botón "Ver" para acceder al detalle de cada cliente

## 🚀 Cómo Usar

### Opción 1: Desde el listado de clientes
1. Ir a `/VerCliente`
2. Hacer clic en el botón "Ver" (ícono de ojo) de cualquier cliente
3. Se abrirá la vista con el detalle completo

### Opción 2: URL directa
```
/DetalleCliente/{id_del_cliente}
```

## 📊 Información Mostrada

### Datos del Cliente
- Nombre
- DNI
- Correo electrónico
- Teléfono (con enlace a WhatsApp)
- Domicilio

### Resumen Estadístico
- Cantidad de servicios vinculados
- Cantidad de servicios impagos
- Total adeudado ($)
- Total pagado en últimos servicios ($)

### Tablas de Servicios

#### 1. Servicios Vinculados (cliente_servicio)
Muestra todos los servicios que el cliente tiene contratados:
- Nombre del servicio
- Descripción
- Precio unitario
- Cantidad
- Subtotal
- Periodicidad (hora/día/semana/mes)
- Fecha de vencimiento
- Fecha de vinculación

#### 2. Servicios Impagos
Lista de servicios pendientes de pago:
- Servicio
- Cantidad
- Precio
- Total
- Período
- Fecha de generación

#### 3. Últimos Servicios Pagados
Últimos 10 servicios que fueron pagados:
- Servicio
- Cantidad
- Precio
- Total
- Período
- Fecha de pago

## 🎨 Características Visuales

- **Colores diferenciados**:
  - 🔴 Rojo para servicios impagos
  - 🟢 Verde para servicios pagados
  
- **Iconos intuitivos**: Font Awesome para mejor UX

- **Responsive**: Se adapta a móviles y tablets

- **Enlaces directos**:
  - WhatsApp: Click en teléfono
  - Email: Click en correo

## 🔒 Seguridad

- Solo usuarios autenticados con rol Admin
- Verifica que el cliente pertenezca a la empresa del usuario
- Redirige si el cliente no existe o no tiene permisos

## 💡 Ejemplo de Uso

```php
// URL: /DetalleCliente/25
// Mostrará el detalle del cliente con ID 25
```

## � Funcionalidades de Vinculación

### Vincular Nuevo Servicio
1. Hacer clic en el botón "Vincular Servicio" en la sección de servicios vinculados
2. Se abre un modal con:
   - Buscador de servicios (en tiempo real)
   - Selector de servicio disponible
   - Campo de cantidad
   - Campo de fecha de vencimiento (por defecto: 1 año)
3. Completar los datos y hacer clic en "Vincular Servicio"
4. El sistema verifica que:
   - El servicio pertenece a la empresa del usuario
   - El cliente no está ya vinculado a ese servicio
5. Se crea la vinculación y se recarga la información

### Desvincular Servicio
1. En la tabla de servicios vinculados, cada fila tiene un botón de "Desvincular"
2. Al hacer clic, se muestra una confirmación
3. Si se confirma, se elimina la vinculación de la tabla `cliente_servicio`
4. La información se actualiza automáticamente

### Características de la Vinculación
- ✅ Solo muestra servicios de la empresa del usuario (`auth()->user()->empresa_id`)
- ✅ Filtra servicios ya vinculados (no los muestra en el modal)
- ✅ Búsqueda en tiempo real por nombre o descripción
- ✅ Validación de datos (cantidad mínima: 0.5)
- ✅ Fecha de vencimiento configurable
- ✅ Mensajes de éxito/error informativos

## �📝 Notas Importantes

1. El componente consulta directamente la tabla `cliente_servicio` para obtener los servicios vinculados
2. Los servicios impagos/pagos se obtienen de la tabla `servicio_pagar`
3. Se limitan los servicios pagados a los últimos 10 para no sobrecargar la vista
4. Todos los importes se muestran con formato de moneda (2 decimales)
5. **NUEVO**: Vinculación/desvinculación de servicios en tiempo real
6. **NUEVO**: Solo se muestran servicios de la empresa del usuario autenticado
7. **NUEVO**: Búsqueda instantánea de servicios disponibles

## 🔄 Funcionalidades Implementadas (Actualización)

- ✅ Ver detalle completo del cliente
- ✅ Listar servicios vinculados
- ✅ Ver servicios impagos y pagados
- ✅ **NUEVO**: Vincular nuevos servicios al cliente
- ✅ **NUEVO**: Desvincular servicios existentes
- ✅ **NUEVO**: Buscador de servicios en tiempo real
- ✅ **NUEVO**: Validaciones de seguridad y permisos
- ✅ **NUEVO**: Modal interactivo con Livewire

## 🎯 Próximos Pasos Sugeridos

Si necesitas agregar funcionalidad adicional:
- Exportar a PDF
- Filtros de fecha
- Paginación en servicios pagados
- Botón para generar nuevo cobro directamente
- Enviar notificación directamente desde aquí
- Editar cantidad y vencimiento de servicios vinculados

---

**Documentación completa**: Ver archivo `COMPONENTE_DETALLE_CLIENTE.md`

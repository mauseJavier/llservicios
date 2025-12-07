# Componente Livewire - Detalle Cliente

## Descripción
Este componente Livewire muestra información detallada de un cliente seleccionado, incluyendo:
- Datos personales del cliente
- Servicios vinculados desde la tabla `cliente_servicio`
- Servicios impagos
- Últimos servicios pagados
- Resumen de totales

## Ubicación de Archivos

### Componente PHP
- **Ruta**: `/app/Livewire/DetalleCliente.php`
- **Namespace**: `App\Livewire\DetalleCliente`

### Vista Blade
- **Ruta**: `/resources/views/livewire/detalle-cliente.blade.php`

### Ruta Web
- **Archivo**: `/routes/web.php`
- **Ruta**: `/DetalleCliente/{clienteId}`
- **Nombre**: `DetalleCliente`
- **Middleware**: `auth`, `RolAdmin`

## Funcionalidad

### Método `mount($clienteId)`
Inicializa el componente cuando se carga la página, recibiendo el ID del cliente como parámetro.

### Método `cargarDatosCliente()`
Este método realiza las siguientes operaciones:

1. **Verificación de pertenencia**: Confirma que el cliente pertenece a la empresa del usuario autenticado.

2. **Carga de servicios vinculados**: Consulta la tabla `cliente_servicio` para obtener todos los servicios que el cliente tiene vinculados, junto con información adicional como:
   - Nombre y descripción del servicio
   - Precio unitario
   - Cantidad contratada
   - Fecha de vencimiento
   - Periodicidad del servicio

3. **Carga de servicios impagos**: Obtiene todos los registros de `servicio_pagar` con estado `'impago'` para el cliente.

4. **Carga de servicios pagados**: Obtiene los últimos 10 servicios pagados del cliente.

5. **Cálculo de totales**: Suma el total adeudado y el total pagado.

## Estructura de Datos

### Servicios Vinculados (cliente_servicio)
```sql
SELECT 
    cs.id as vinculo_id,
    cs.cantidad,
    cs.vencimiento,
    cs.created_at as fecha_vinculacion,
    s.id as servicio_id,
    s.nombre as servicio_nombre,
    s.descripcion as servicio_descripcion,
    s.precio as servicio_precio,
    s.tiempo as servicio_tiempo,
    (cs.cantidad * s.precio) as subtotal
FROM cliente_servicio cs
INNER JOIN servicios s ON cs.servicio_id = s.id
WHERE cs.cliente_id = ? AND s.empresa_id = ?
```

### Servicios Impagos (servicio_pagar)
```sql
SELECT 
    sp.id,
    sp.cantidad,
    sp.precio,
    sp.created_at as fecha_creacion,
    sp.periodo_servicio,
    s.nombre as servicio_nombre,
    (sp.cantidad * sp.precio) as total
FROM servicio_pagar sp
INNER JOIN servicios s ON sp.servicio_id = s.id
WHERE sp.cliente_id = ? AND sp.estado = 'impago' AND s.empresa_id = ?
```

## Vista

### Secciones Principales

1. **Header con Información del Cliente**
   - Nombre del cliente
   - Botones de acción (Volver, Editar)

2. **Información Personal**
   - DNI
   - Correo electrónico
   - Teléfono (con enlace a WhatsApp)
   - Domicilio

3. **Tarjetas de Resumen**
   - Total de servicios vinculados
   - Cantidad de servicios impagos
   - Total adeudado
   - Total pagado (últimos servicios)

4. **Tabla de Servicios Vinculados**
   - Muestra todos los servicios desde `cliente_servicio`
   - Incluye precio, cantidad, subtotal, periodicidad y vencimiento

5. **Tabla de Servicios Impagos**
   - Solo se muestra si hay servicios impagos
   - Resaltada con borde rojo
   - Incluye total al final

6. **Tabla de Últimos Servicios Pagados**
   - Solo se muestra si hay servicios pagados
   - Resaltada con borde verde
   - Muestra los últimos 10 pagos

## Uso

### Acceso desde el Listado de Clientes

En la vista `/VerCliente`, cada cliente tiene un botón "Ver" que redirige a:
```
/DetalleCliente/{clienteId}
```

### Ejemplo de URL
```
https://tudominio.com/DetalleCliente/15
```

Donde `15` es el ID del cliente.

## Seguridad

- El componente verifica que el cliente pertenezca a la empresa del usuario autenticado
- Si el cliente no existe o no pertenece a la empresa, redirige al listado con un mensaje de error
- Utiliza middleware `auth` y `RolAdmin` para proteger el acceso

## Estilos

El componente utiliza:
- **Pico CSS**: Framework CSS del proyecto
- **Font Awesome**: Para iconos
- **Estilos personalizados**: Definidos en la sección `<style>` de la vista

## Características Destacadas

1. **Responsive**: La tabla se adapta a diferentes tamaños de pantalla con scroll horizontal
2. **Información clara**: Uso de iconos y colores para diferenciar estados
3. **Enlaces útiles**: 
   - WhatsApp directo desde el teléfono
   - Email desde el correo
4. **Formateo de datos**:
   - Fechas en formato legible
   - Precios con formato de moneda
   - Totales destacados visualmente

## Mejoras Futuras

Posibles mejoras para implementar:
1. Paginación para servicios pagados
2. Filtros por fecha para servicios
3. Exportación a PDF del detalle del cliente
4. Gráficos de historial de pagos
5. Botón para generar nuevo cobro desde esta vista
6. Envío de notificaciones desde esta vista

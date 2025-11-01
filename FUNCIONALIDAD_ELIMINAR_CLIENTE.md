# Funcionalidad: Eliminar Cliente

## 📋 Descripción

Esta funcionalidad permite eliminar clientes de la empresa junto con **todos sus servicios pagos e impagos** y registros asociados de forma segura y completa.

## 🎯 Características

### ✅ Eliminación Completa
La funcionalidad elimina:

1. **Todos los pagos registrados** del cliente (tabla `pagos`)
2. **Todos los servicios impagos** del cliente (tabla `servicio_pagar` con estado 'impago')
3. **Todos los servicios pagos** del cliente (tabla `servicio_pagar` con estado 'pago')
4. **Relaciones cliente-servicio** (tabla pivot `cliente_servicio`)
5. **Relaciones cliente-empresa** (tabla pivot `cliente_empresa`)
6. **El registro del cliente** (tabla `clientes`)

### 🔒 Seguridad

- **Modal de confirmación**: Se muestra un modal con advertencia clara antes de eliminar
- **Transacciones de base de datos**: Usa `DB::beginTransaction()` y `DB::commit()` para asegurar integridad
- **Rollback automático**: Si ocurre un error, se revierte toda la operación con `DB::rollBack()`
- **Mensajes informativos**: Muestra claramente qué se va a eliminar

### 🎨 Interfaz de Usuario

- Botón rojo "Eliminar" en la lista de clientes
- Modal de confirmación con:
  - Información del cliente a eliminar
  - Lista detallada de lo que se eliminará
  - Advertencia de que la acción es irreversible
  - Botones para confirmar o cancelar
- Mensajes de éxito/error después de la operación

## 🔧 Implementación Técnica

### Componente Livewire

**Ubicación**: `/app/Livewire/VerCliente/VerCliente.php`

#### Propiedades Nuevas

```php
public $clienteAEliminar = null;
public $mostrarModalConfirmacion = false;
```

#### Métodos Nuevos

1. **`confirmarEliminarCliente($clienteId)`**
   - Abre el modal de confirmación
   - Carga los datos del cliente seleccionado

2. **`cancelarEliminacion()`**
   - Cierra el modal
   - Limpia las variables temporales

3. **`eliminarCliente()`**
   - Ejecuta la eliminación completa
   - Usa transacciones para garantizar integridad
   - Actualiza la lista de clientes
   - Muestra mensajes de éxito/error

### Vista Blade

**Ubicación**: `/resources/views/livewire/ver-cliente/ver-cliente.blade.php`

#### Elementos Agregados

1. **Sección de mensajes flash** (arriba del contenedor)
2. **Botón "Eliminar"** en cada fila de la tabla
3. **Modal de confirmación** con advertencias y detalles

## 📖 Uso

### Para el Usuario Final

1. **Ir a la lista de clientes**
   - Navegar a Ver Clientes

2. **Seleccionar cliente a eliminar**
   - Localizar el cliente en la tabla
   - Click en el botón rojo "Eliminar"

3. **Confirmar la eliminación**
   - Leer las advertencias en el modal
   - Verificar que es el cliente correcto
   - Click en "Sí, Eliminar Definitivamente" para confirmar
   - O click en "Cancelar" para abortar

4. **Verificar resultado**
   - Se mostrará un mensaje de éxito en verde
   - El cliente desaparecerá de la lista
   - Si hay error, se mostrará un mensaje en rojo

### Proceso de Eliminación en la Base de Datos

```sql
-- Orden de eliminación (dentro de una transacción):

1. DELETE FROM pagos WHERE id_servicio_pagar IN (
     SELECT id FROM servicio_pagar WHERE cliente_id = ?
   )

2. DELETE FROM servicio_pagar WHERE cliente_id = ?

3. DELETE FROM cliente_servicio WHERE cliente_id = ?

4. DELETE FROM cliente_empresa WHERE cliente_id = ?

5. DELETE FROM clientes WHERE id = ?
```

## ⚠️ Advertencias Importantes

### Para Usuarios
- ❌ **NO se puede deshacer** - La eliminación es permanente
- 📊 **Se pierden todos los datos** - Servicios pagos, impagos y pagos registrados
- 💼 **Afecta reportes históricos** - Los datos no estarán disponibles para reportes futuros

### Para Desarrolladores
- 🔐 **Verificar permisos** - Considerar agregar validación de roles/permisos
- 💾 **Backup recomendado** - Tener respaldos antes de usar en producción
- 📝 **Log de auditoría** - Considerar agregar registro de quién eliminó qué

## 🚀 Mejoras Futuras Sugeridas

1. **Soft Deletes**: Implementar eliminación suave en lugar de hard delete
2. **Permisos**: Agregar validación de roles (solo admin puede eliminar)
3. **Auditoría**: Registrar quién y cuándo eliminó cada cliente
4. **Exportación previa**: Opción de exportar datos del cliente antes de eliminar
5. **Papelera**: Posibilidad de recuperar clientes eliminados en X días
6. **Confirmación adicional**: Requerir escribir el nombre del cliente para confirmar

## 🧪 Testing

### Casos de Prueba Recomendados

1. ✅ Eliminar cliente sin servicios
2. ✅ Eliminar cliente con servicios impagos
3. ✅ Eliminar cliente con servicios pagos
4. ✅ Eliminar cliente con servicios pagos e impagos
5. ✅ Cancelar eliminación (modal)
6. ✅ Verificar rollback en caso de error
7. ✅ Verificar mensajes de éxito/error

### Ejemplo de Test Manual

```bash
# 1. Crear un cliente de prueba
# 2. Asignarle servicios pagos e impagos
# 3. Intentar eliminar y cancelar
# 4. Verificar que el cliente sigue existiendo
# 5. Intentar eliminar y confirmar
# 6. Verificar que todo fue eliminado correctamente
```

## 📞 Soporte

Para preguntas o problemas con esta funcionalidad, contactar al equipo de desarrollo.

---

**Última actualización**: Noviembre 2025
**Versión**: 1.0.0
**Estado**: ✅ Producción lista

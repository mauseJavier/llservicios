# 🗑️ Funcionalidad: Eliminar Servicio Impago

## 📋 Descripción

Funcionalidad para eliminar servicios en estado **impago** con control de permisos. Solo usuarios con rol **Admin (role_id = 2)** o **Super (role_id = 3)** pueden eliminar servicios.

---

## 🔒 Validaciones Implementadas

### 1. ✅ Validación de Permisos de Usuario
```php
if (!in_array($usuario->role_id, [2, 3])) {
    return redirect()->back()
        ->withErrors(['No tienes permisos para eliminar servicios...']);
}
```
- Solo **Admin (2)** o **Super (3)** pueden eliminar
- Usuarios normales no ven el botón de eliminar

### 2. ✅ Validación de Existencia del Servicio
```php
if (!$servicioPagar) {
    return redirect()->back()
        ->withErrors(['El servicio no existe.']);
}
```

### 3. ✅ Validación de Empresa
```php
if ($servicio->empresa_id != $usuario->empresa_id) {
    return redirect()->back()
        ->withErrors(['No puedes eliminar servicios de otra empresa.']);
}
```
- Solo se pueden eliminar servicios de la propia empresa

### 4. ✅ Validación de Estado IMPAGO
```php
if ($servicioPagar->estado !== 'impago') {
    return redirect()->back()
        ->withErrors(['Solo se pueden eliminar servicios en estado IMPAGO...']);
}
```
- **Solo servicios impagos** pueden ser eliminados
- Servicios pagados **NO** se pueden eliminar

---

## 📁 Archivos Modificados

### 1. Controller: `ServicioPagarController.php`

**Método agregado:** `EliminarServicioImpago($idServicioPagar)`

**Características:**
- ✅ 4 validaciones de seguridad
- ✅ Logs detallados de la eliminación
- ✅ Manejo completo de errores
- ✅ Mensajes de error descriptivos

### 2. Ruta: `routes/web.php`

```php
Route::delete('EliminarServicioImpago/{idServicioPagar}', 
    [ServicioPagarController::class, 'EliminarServicioImpago'])
    ->name('EliminarServicioImpago');
```

**Características:**
- Método HTTP: **DELETE**
- Protegida por autenticación
- Parámetro: `idServicioPagar`

### 3. Vista: `ServiciosImpagos.blade.php`

**Botón agregado:**
- Solo visible para Admin (2) y Super (3)
- Confirmación antes de eliminar
- Muestra información del servicio en el alert
- Color rojo para indicar acción destructiva

---

## 🎯 Roles y Permisos

| Role ID | Nombre | Puede Eliminar Servicios |
|---------|--------|--------------------------|
| 1 | Usuario | ❌ No |
| 2 | Admin | ✅ Sí |
| 3 | Super | ✅ Sí |

---

## 💬 Flujo de Eliminación

```
Usuario Admin/Super hace clic en "Eliminar"
              ↓
    Aparece confirmación JavaScript
    (Muestra datos del servicio)
              ↓
         Usuario confirma
              ↓
    Se envía formulario con método DELETE
              ↓
    Controller: EliminarServicioImpago()
              ↓
    ┌─────────────────────────────────┐
    │ Validación 1: Permisos Usuario  │
    │ Validación 2: Servicio Existe   │
    │ Validación 3: Empresa Correcta  │
    │ Validación 4: Estado = impago   │
    └─────────────────────────────────┘
              ↓
         Todas OK?
              ↓
    ┌─────────┴──────────┐
    ▼                    ▼
  Sí                    No
    │                    │
    ▼                    ▼
Elimina              Retorna
Servicio             con Error
    │
    ▼
Registra Log
    │
    ▼
Redirecciona con
mensaje de éxito
```

---

## 🔍 Mensaje de Confirmación

Cuando el usuario hace clic en "Eliminar", ve:

```
¿Está seguro que desea eliminar este servicio impago?

Cliente: Juan Pérez
Servicio: Internet 100MB
Total: $5000.00

Esta acción no se puede deshacer.
```

**Botones:**
- ✅ Aceptar → Elimina el servicio
- ❌ Cancelar → No hace nada

---

## 📊 Logs Generados

### ✅ Log de Eliminación Exitosa

```log
[2025-10-30 15:30:00] local.INFO: Servicio impago eliminado
{
  "usuario_id": 1,
  "usuario_nombre": "Admin User",
  "role_id": 2,
  "servicio_pagar_id": 123,
  "cliente": "Juan Pérez",
  "servicio": "Internet 100MB",
  "total": 5000,
  "fecha_eliminacion": "2025-10-30 15:30:00"
}
```

### ❌ Log de Error

```log
[2025-10-30 15:30:00] local.ERROR: Error al eliminar servicio impago
{
  "usuario_id": 1,
  "servicio_pagar_id": 123,
  "error": "Only variables should be passed by reference",
  "trace": "..."
}
```

---

## 🎨 Interfaz de Usuario

### Vista para Usuario Normal (role_id = 1)
```
Pagar | Enviar Notif.
```
❌ **No ve** el botón "Eliminar"

### Vista para Admin/Super (role_id = 2 o 3)
```
Pagar | Enviar Notif. | Eliminar
```
✅ **Ve** el botón "Eliminar" en color rojo

---

## 🔐 Seguridad Implementada

### 1. **Protección de Permisos**
- Validación en el servidor (Controller)
- Validación en el cliente (Vista - solo muestra botón)

### 2. **Protección CSRF**
```blade
@csrf
@method('DELETE')
```

### 3. **Confirmación de Usuario**
- Alert JavaScript antes de enviar
- Muestra información del servicio

### 4. **Auditoría**
- Log completo de quién eliminó qué y cuándo
- Incluye información del servicio eliminado

---

## 🧪 Casos de Prueba

### ✅ Caso 1: Admin elimina servicio impago
**Resultado esperado:** Servicio eliminado exitosamente

### ❌ Caso 2: Usuario normal intenta eliminar
**Resultado esperado:** No ve el botón, si accede directo → Error de permisos

### ❌ Caso 3: Admin intenta eliminar servicio pago
**Resultado esperado:** Error "Solo se pueden eliminar servicios en estado IMPAGO"

### ❌ Caso 4: Admin intenta eliminar servicio de otra empresa
**Resultado esperado:** Error "No puedes eliminar servicios de otra empresa"

### ❌ Caso 5: Servicio no existe
**Resultado esperado:** Error "El servicio no existe"

---

## 💻 Código de la Vista

### Botón Eliminar (Solo visible para Admin/Super)

```blade
@if(Auth::user()->role_id == 2 || Auth::user()->role_id == 3)
  | 
  <strong>
    <a href="#" 
       onclick="event.preventDefault(); 
                if(confirm('¿Está seguro que desea eliminar este servicio impago?\n\n
                           Cliente: {{$e->nombreCliente}}\n
                           Servicio: {{$e->nombreServicio}}\n
                           Total: ${{$e->total}}\n\n
                           Esta acción no se puede deshacer.')) { 
                  document.getElementById('delete-form-{{$e->idServicioPagar}}').submit(); 
                }" 
       data-tooltip="Eliminar (Solo Admin/Super)"
       style="color: #d32f2f;">
      Eliminar
    </a>
  </strong>
  <form id="delete-form-{{$e->idServicioPagar}}" 
        action="{{route('EliminarServicioImpago', ['idServicioPagar' => $e->idServicioPagar])}}" 
        method="POST" 
        style="display: none;">
    @csrf
    @method('DELETE')
  </form>
@endif
```

---

## 📝 Código del Controller

```php
public function EliminarServicioImpago($idServicioPagar)
{
    try {
        $usuario = Auth::user();

        // Validación 1: Permisos
        if (!in_array($usuario->role_id, [2, 3])) {
            return redirect()->back()
                ->withErrors(['No tienes permisos...']);
        }

        // Validación 2: Existencia
        $servicioPagar = ServicioPagar::find($idServicioPagar);
        if (!$servicioPagar) {
            return redirect()->back()
                ->withErrors(['El servicio no existe.']);
        }

        // Validación 3: Empresa
        $servicio = Servicio::find($servicioPagar->servicio_id);
        if ($servicio->empresa_id != $usuario->empresa_id) {
            return redirect()->back()
                ->withErrors(['No puedes eliminar servicios de otra empresa.']);
        }

        // Validación 4: Estado
        if ($servicioPagar->estado !== 'impago') {
            return redirect()->back()
                ->withErrors(['Solo se pueden eliminar servicios en estado IMPAGO...']);
        }

        // Obtener info para log
        $cliente = $servicioPagar->cliente;
        $servicioNombre = $servicio->nombre;
        $total = $servicioPagar->total;

        // Eliminar
        $servicioPagar->delete();

        // Log
        \Log::info('Servicio impago eliminado', [...]);

        return redirect()->route('ServiciosImpagos')
            ->with('status', 'Servicio eliminado correctamente.');

    } catch (\Exception $e) {
        \Log::error('Error al eliminar servicio impago', [...]);
        return redirect()->back()
            ->withErrors(['Error al eliminar el servicio: ' . $e->getMessage()]);
    }
}
```

---

## ⚠️ Consideraciones Importantes

### 1. **No se puede deshacer**
- La eliminación es **permanente**
- No hay papelera de reciclaje
- Se recomienda **confirmar bien** antes de eliminar

### 2. **Solo servicios impagos**
- Servicios **pagados** NO se pueden eliminar
- Esto protege la integridad de los registros contables

### 3. **Solo tu empresa**
- No puedes eliminar servicios de otras empresas
- Protección adicional en entornos multi-empresa

### 4. **Logs permanentes**
- Aunque el servicio se elimine, queda registro en logs
- Útil para auditoría y seguimiento

---

## 🚀 Uso

### Como Usuario Admin o Super:

1. Ve a **Servicios Impagos**
2. Encuentra el servicio que deseas eliminar
3. Haz clic en **"Eliminar"** (botón rojo)
4. Confirma la eliminación en el alert
5. El servicio se elimina y ves mensaje de confirmación

### Como Usuario Normal:

- No verás el botón "Eliminar"
- Si intentas acceder directo a la URL, recibirás error de permisos

---

## 📊 Respuestas del Sistema

### ✅ Éxito
```
Servicio eliminado correctamente.
```

### ❌ Errores Posibles

```
No tienes permisos para eliminar servicios. 
Solo usuarios Admin o Super pueden hacerlo.
```

```
El servicio no existe.
```

```
No puedes eliminar servicios de otra empresa.
```

```
Solo se pueden eliminar servicios en estado IMPAGO. 
Este servicio está: PAGO
```

```
Error al eliminar el servicio: [mensaje de error técnico]
```

---

## 🎉 Resultado Final

### Antes:
- No había forma de eliminar servicios impagos
- Servicios mal registrados quedaban permanentemente

### Ahora:
- ✅ Admin/Super pueden eliminar servicios impagos
- ✅ 4 validaciones de seguridad
- ✅ Logs de auditoría
- ✅ Confirmación antes de eliminar
- ✅ Solo servicios impagos
- ✅ Solo de la propia empresa

---

**Desarrollado para:** LL Servicios  
**Fecha:** Octubre 2025  
**Versión:** 1.0.0  
**Permisos requeridos:** Admin (role_id = 2) o Super (role_id = 3)

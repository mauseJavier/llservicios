# 🔗 Agregar Enlace al Componente QR Manager en el Menú

## 📝 Instrucciones para Agregar al Menú Principal

### Ubicación del Archivo
`resources/views/principal/menuAPP.blade.php`

### Código a Agregar

Buscar la sección de menú para Admin (línea ~19):

```blade
@if (Auth::User()->role->nombre == 'Super' || 
    Auth::User()->role->nombre == 'Admin')
  <li><a href="{{route('Cliente.index')}}">Clientes</a></li>
  <li><a href="{{route('Servicios.index')}}">Servicios</a></li>
  <li><a href="{{route('Grilla')}}">Grilla Clientes</a></li>
  <li><a href="{{route('Pagos', ['fecha_inicio' => date('Y-m-d'), 'fecha_fin' => date('Y-m-d')])}}">Pagos</a></li>
  <li><a href="{{route('ServiciosImpagos')}}">Impagos</a></li>

  <li><a href="{{route('expenses.index')}}">Gastos</a></li>
  <li><a href="{{route('cierre-caja')}}">Cierre de Caja</a></li>
  
  {{-- 🆕 AGREGAR ESTA LÍNEA --}}
  <li><a href="{{route('mercadopago.qr-manager')}}">🔲 QR MercadoPago</a></li>

@endif
```

---

## 📋 Código Completo con el Enlace Agregado

```blade
<details class="dropdown">
  <summary>Menu</summary>
  <ul>
    <li><a href="{{route('panelServicios')}}">Panel</a></li>

    @if (Auth::User()->role->nombre == 'Super' || 
        Auth::User()->role->nombre == 'Admin')
      <li><a href="{{route('Cliente.index')}}">Clientes</a></li>
      <li><a href="{{route('Servicios.index')}}">Servicios</a></li>
      <li><a href="{{route('Grilla')}}">Grilla Clientes</a></li>
      <li><a href="{{route('Pagos', ['fecha_inicio' => date('Y-m-d'), 'fecha_fin' => date('Y-m-d')])}}">Pagos</a></li>
      <li><a href="{{route('ServiciosImpagos')}}">Impagos</a></li>

      <li><a href="{{route('expenses.index')}}">Gastos</a></li>
      <li><a href="{{route('cierre-caja')}}">Cierre de Caja</a></li>
      
      {{-- 🆕 NUEVO: Gestión de QR MercadoPago --}}
      <li>
        <a href="{{route('mercadopago.qr-manager')}}">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 5px;">
            <rect x="3" y="3" width="7" height="7"/>
            <rect x="14" y="3" width="7" height="7"/>
            <rect x="14" y="14" width="7" height="7"/>
            <rect x="3" y="14" width="7" height="7"/>
          </svg>
          QR MercadoPago
        </a>
      </li>
  
    @endif

    @if (Auth::User()->role->nombre == 'Super')
      <li><a href="{{route('usuarios')}}">Usuarios</a></li>
      <li><a href="{{route('empresas.index')}}">Empresas</a></li>
    @endif
    <li><a href="{{route('logout')}}" style="border-radius: 10px; background-color:red;" >Salir</a></li>
    
  </ul>
</details>
```

---

## 🎨 Variantes de Estilo

### Opción 1: Con Ícono QR Simple
```blade
<li><a href="{{route('mercadopago.qr-manager')}}">🔲 QR MercadoPago</a></li>
```

### Opción 2: Con Ícono SVG
```blade
<li>
  <a href="{{route('mercadopago.qr-manager')}}">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 5px;">
      <rect x="3" y="3" width="7" height="7"/>
      <rect x="14" y="3" width="7" height="7"/>
      <rect x="14" y="14" width="7" height="7"/>
      <rect x="3" y="14" width="7" height="7"/>
    </svg>
    QR MercadoPago
  </a>
</li>
```

### Opción 3: Con Badge de Nuevo
```blade
<li>
  <a href="{{route('mercadopago.qr-manager')}}">
    🔲 QR MercadoPago 
    <span style="background: #28a745; color: white; font-size: 10px; padding: 2px 6px; border-radius: 10px; margin-left: 5px;">NUEVO</span>
  </a>
</li>
```

### Opción 4: Con Ícono Font Awesome (si está disponible)
```blade
<li>
  <a href="{{route('mercadopago.qr-manager')}}">
    <i class="fas fa-qrcode"></i> QR MercadoPago
  </a>
</li>
```

### Opción 5: Destacado con Color
```blade
<li>
  <a href="{{route('mercadopago.qr-manager')}}" style="color: #009ee3;">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 5px;">
      <rect x="3" y="3" width="7" height="7"/>
      <rect x="14" y="3" width="7" height="7"/>
      <rect x="14" y="14" width="7" height="7"/>
      <rect x="3" y="14" width="7" height="7"/>
    </svg>
    QR MercadoPago
  </a>
</li>
```

---

## 🔐 Permisos de Acceso

El componente está disponible solo para usuarios con rol **Admin** o **Super**, ya que está dentro del middleware `RolAdmin`.

### Verificar Permisos en la Vista (Opcional)

Si quieres mostrar el enlace solo si la empresa tiene MercadoPago configurado:

```blade
@if (Auth::User()->role->nombre == 'Super' || 
    Auth::User()->role->nombre == 'Admin')
  
  {{-- Otros enlaces... --}}
  
  @if(Auth::user()->empresa && Auth::user()->empresa->hasMercadoPagoConfigured())
    <li>
      <a href="{{route('mercadopago.qr-manager')}}">
        🔲 QR MercadoPago
      </a>
    </li>
  @else
    <li>
      <a href="{{route('empresas.edit', Auth::user()->empresa_id)}}" style="opacity: 0.6;">
        🔲 QR MercadoPago (configurar)
      </a>
    </li>
  @endif
  
@endif
```

---

## 📱 Alternativa: Crear Sección de MercadoPago

Si planeas agregar más funcionalidades de MercadoPago en el futuro:

```blade
@if (Auth::User()->role->nombre == 'Super' || 
    Auth::User()->role->nombre == 'Admin')
  
  {{-- Otros enlaces existentes... --}}
  
  {{-- Nueva sección de MercadoPago --}}
  <li>
    <details class="dropdown">
      <summary>💳 MercadoPago</summary>
      <ul>
        <li><a href="{{route('mercadopago.qr-manager')}}">Gestionar QR</a></li>
        <li><a href="{{route('mercadopago.payment-form')}}">Formulario de Pago</a></li>
        <li><a href="{{route('Pagos', ['fecha_inicio' => date('Y-m-d'), 'fecha_fin' => date('Y-m-d')])}}">Ver Pagos</a></li>
      </ul>
    </details>
  </li>
  
@endif
```

---

## 🧪 Probar el Enlace

1. Agregar el código al menú
2. Limpiar cache:
   ```bash
   php artisan view:clear
   php artisan route:clear
   ```
3. Recargar la página
4. Verificar que aparece el enlace en el menú
5. Click en "QR MercadoPago"
6. Debería redirigir a `/mercadopago/qr-manager`

---

## ✅ Resumen

**Archivo a editar:**
- `resources/views/principal/menuAPP.blade.php`

**Código mínimo a agregar:**
```blade
<li><a href="{{route('mercadopago.qr-manager')}}">🔲 QR MercadoPago</a></li>
```

**Ubicación:**
Dentro del bloque `@if (Auth::User()->role->nombre == 'Super' || Auth::User()->role->nombre == 'Admin')`

**Permisos:**
Solo para usuarios Admin y Super

---

**¡Listo para usar!** 🚀

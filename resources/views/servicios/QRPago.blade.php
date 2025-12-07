@extends('principal.principal')

@section('body')

<div class="container">
  <nav>
    <ul>
      <li>
        <h1>Pago con QR - MercadoPago</h1>
      </li>
    </ul>
    <ul>
      <li>
        <a href="{{ route('PagarServicio', ['idServicioPagar' => $servicioPagar->id, 'importe' => $servicioPagar->total]) }}" role="button" class="secondary">
          ← Volver
        </a>
      </li>
    </ul>
  </nav>
  
  <!-- Estado y QR -->
  <article id="qrContainer">
    <header>
      <strong id="qrHeader">Código QR de Pago</strong>
    </header>

    <!-- Estado de la orden -->
    <div id="statusContainer" style="text-align: center; margin-bottom: 20px;">
      <div id="statusPending" style="display: none;">
        <p aria-busy="true">Esperando pago...</p>
        <small>La orden expira en <span id="countdown">10:00</span> minutos</small>
      </div>
      
      <div id="statusPaid" style="display: none; color: green;">
        <h2>✓ Pago Recibido Exitosamente</h2>
        <p>ID de Pago: <strong id="paymentIdDisplay"></strong></p>
        <p>Monto: <strong>${{number_format($servicioPagar->total, 2)}}</strong></p>
        <p><small>Redirigiendo en <span id="redirectCountdown">5</span> segundos...</small></p>
      </div>

      <div id="statusExpired" style="display: none; color: red;">
        <h3>⚠ Orden Expirada</h3>
        <p>La orden ha expirado. Por favor, genera un nuevo código QR.</p>
      </div>

      <div id="statusCancelled" style="display: none; color: orange;">
        <h3>⚠ Orden Cancelada</h3>
        <p>La orden ha sido cancelada correctamente.</p>
      </div>

      <div id="statusError" style="display: none; color: red;">
        <h3>⚠ Error</h3>
        <p id="errorMessage"></p>
      </div>
    </div>

    <!-- Contenedor del QR -->
    <div id="qrCodeContainer" style="text-align: center; margin: 30px 0; display: none;">
      <div id="qrCode" style="display: inline-block; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <!-- El QR se insertará aquí -->
      </div>
      <p style="margin-top: 20px;">
        <small>Escanea este código QR con la app de MercadoPago para realizar el pago</small>
      </p>
    </div>

    <!-- Botones de acción -->
    <div class="grid" id="actionButtons" style="display: none;">
      <button id="btnCancelar" class="secondary" onclick="cancelarOrden()">
        Cancelar Orden
      </button>
      <button id="btnRefresh" onclick="verificarEstado()">
        Verificar Estado
      </button>
    </div>

    <!-- Botón para generar nuevo QR después de expiración/cancelación -->
    <div style="text-align: center; display: none;" id="btnGenerarNuevoContainer">
      <button onclick="generarNuevoQR()" class="primary">
        Generar Nuevo Código QR
      </button>
    </div>

  </article>
  
  <!-- Información del Cliente -->
  <article>
    <header>
      <strong>Información del Cliente</strong>
    </header>
    <div class="grid">
      <div>
        <small><strong>Nombre:</strong></small>
        <p>{{$servicioPagar->cliente->nombre}}</p>
      </div>
      <div>
        <small><strong>DNI:</strong></small>
        <p>{{$servicioPagar->cliente->dni}}</p>
      </div>
      <div>
        <small><strong>Teléfono:</strong></small>
        <p>
          @if($servicioPagar->cliente->telefono)
            <a href="https://wa.me/+54{{$servicioPagar->cliente->telefono}}" target="_blank" rel="noopener noreferrer">
              {{$servicioPagar->cliente->telefono}}
            </a>
          @else
            -
          @endif
        </p>
      </div>
    </div>
  </article>

  <!-- Información del Servicio -->
  <article>
    <header>
      <strong>Detalles del Servicio a Pagar</strong>
    </header>
    <div class="grid">
      <div>
        <small><strong>Servicio:</strong></small>
        <p>{{$servicioPagar->servicio->nombre}}</p>
      </div>
      <div>
        <small><strong>Descripción:</strong></small>
        <p>{{$servicioPagar->servicio->descripcion ?? '-'}}</p>
      </div>
    </div>
    <div class="grid">
      <div>
        <small><strong>Precio Unitario:</strong></small>
        <p>${{number_format($servicioPagar->precio, 2)}}</p>
      </div>
      <div>
        <small><strong>Cantidad:</strong></small>
        <p>{{$servicioPagar->cantidad}} unidad(es)</p>
      </div>
      <div>
        <small><strong>Total a Pagar:</strong></small>
        <p><mark>${{number_format($servicioPagar->total, 2)}}</mark></p>
      </div>
    </div>
  </article>


</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" integrity="sha512-CNgIRecGo7nphbeZ04Sc13ka07paqdeTu0WR1IM4kNcpmBAUSHSQX0FslNhTDadL4O5SAGapGt4FodqL8My0mA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script>
// Variables globales
let orderId = null;
let pollingInterval = null;
let expirationTime = null;
let countdownInterval = null;
let paymentChecked = false;

// Configuración
const POLLING_INTERVAL = 3000; // 3 segundos
const REDIRECT_DELAY = 5000; // 5 segundos

// Función para generar el QR al cargar la página
document.addEventListener('DOMContentLoaded', function() {
  // Esperar a que la librería QRCode esté disponible
  if (typeof QRCode !== 'undefined') {
    generarQR();
  } else {
    console.error('QRCode library not loaded');
    mostrarError('Error al cargar la librería de códigos QR');
  }
});

// Generar QR de pago
function generarQR() {
  const btnGenerarNuevo = document.getElementById('btnGenerarNuevoContainer');
  const qrCodeContainer = document.getElementById('qrCodeContainer');
  const statusPending = document.getElementById('statusPending');
  const actionButtons = document.getElementById('actionButtons');
  
  // Ocultar mensajes de estado anteriores
  ocultarTodosEstados();
  btnGenerarNuevo.style.display = 'none';
  
  // Mostrar estado de carga
  statusPending.style.display = 'block';
  
  // Realizar petición para crear la orden
  fetch('{{ route("api.qr.create") }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
    body: JSON.stringify({
      servicioPagar: {{ $servicioPagar->id }},
      importe: {{ $servicioPagar->total }},
      posMpId: {{ $posMpId }}
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      orderId = data.order_id;
      
      // Mostrar QR
      mostrarQR(data.qr_data);
      qrCodeContainer.style.display = 'block';
      actionButtons.style.display = 'grid';
      
      // Configurar expiración
      expirationTime = new Date(new Date().getTime() + 10 * 60 * 1000); // 10 minutos
      iniciarContador();
      
      // Iniciar polling
      iniciarPolling();
      
    } else {
      mostrarError(data.error || 'Error al generar el código QR');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    mostrarError('Error de conexión al generar el QR');
  });
}

// Mostrar el código QR
function mostrarQR(qrData) {
  const qrContainer = document.getElementById('qrCode');
  qrContainer.innerHTML = ''; // Limpiar QR anterior
  
  try {
    // Crear el código QR usando QRCode.js
    new QRCode(qrContainer, {
      text: qrData,
      width: 300,
      height: 300,
      colorDark: '#000000',
      colorLight: '#ffffff',
      correctLevel: QRCode.CorrectLevel.H
    });
  } catch (error) {
    console.error('Error generando QR:', error);
    mostrarError('Error al generar el código QR visual');
  }
}

// Iniciar polling para verificar el estado del pago
function iniciarPolling() {
  // Limpiar polling anterior si existe
  if (pollingInterval) {
    clearInterval(pollingInterval);
  }
  
  paymentChecked = false;
  
  pollingInterval = setInterval(function() {
    verificarEstado();
  }, POLLING_INTERVAL);
}

// Verificar estado del pago
function verificarEstado() {
  if (!orderId || paymentChecked) {
    return;
  }
  
  fetch('{{ route("api.qr.check-status") }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
    body: JSON.stringify({
      order_id: orderId
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      const status = data.status;
      
      if (status === 'paid') {
        pagoExitoso(data.payment_id, data.amount);
      } else if (status === 'expired') {
        ordenExpirada();
      } else if (status === 'cancelled') {
        ordenCancelada();
      }
      // Si está pending, continúa el polling
    } else {
      console.error('Error verificando estado:', data.error);
    }
  })
  .catch(error => {
    console.error('Error en polling:', error);
  });
}

// Pago exitoso
function pagoExitoso(paymentId, amount) {
  paymentChecked = true;
  
  // Detener polling y contador
  detenerPolling();
  detenerContador();
  
  // Ocultar QR y botones
  document.getElementById('qrCodeContainer').style.display = 'none';
  document.getElementById('actionButtons').style.display = 'none';
  
  // Mostrar mensaje de éxito
  ocultarTodosEstados();
  const statusPaid = document.getElementById('statusPaid');
  statusPaid.style.display = 'block';
  document.getElementById('paymentIdDisplay').textContent = paymentId;
  
  // Cambiar el encabezado
  document.getElementById('qrHeader').textContent = 'Pago Completado';
  
  // Redirigir después de 5 segundos
  let countdown = 5;
  const redirectInterval = setInterval(function() {
    countdown--;
    document.getElementById('redirectCountdown').textContent = countdown;
    
    if (countdown <= 0) {
      clearInterval(redirectInterval);
      window.location.href = '{{ route("ServiciosImpagos") }}';
    }
  }, 1000);
}

// Orden expirada
function ordenExpirada() {
  detenerPolling();
  detenerContador();
  
  ocultarTodosEstados();
  document.getElementById('statusExpired').style.display = 'block';
  document.getElementById('qrCodeContainer').style.display = 'none';
  document.getElementById('actionButtons').style.display = 'none';
  document.getElementById('btnGenerarNuevoContainer').style.display = 'block';
}

// Orden cancelada
function ordenCancelada() {
  detenerPolling();
  detenerContador();
  
  ocultarTodosEstados();
  document.getElementById('statusCancelled').style.display = 'block';
  document.getElementById('qrCodeContainer').style.display = 'none';
  document.getElementById('actionButtons').style.display = 'none';
  document.getElementById('btnGenerarNuevoContainer').style.display = 'block';
}

// Cancelar orden
function cancelarOrden() {
  if (!orderId) {
    return;
  }
  
  if (!confirm('¿Estás seguro de que deseas cancelar esta orden de pago?')) {
    return;
  }
  
  fetch('{{ route("api.qr.cancel") }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
    body: JSON.stringify({
      order_id: orderId
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      ordenCancelada();
    } else {
      mostrarError(data.error || 'Error al cancelar la orden');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    mostrarError('Error de conexión al cancelar la orden');
  });
}

// Generar nuevo QR
function generarNuevoQR() {
  orderId = null;
  generarQR();
}

// Mostrar error
function mostrarError(mensaje) {
  detenerPolling();
  detenerContador();
  
  ocultarTodosEstados();
  document.getElementById('statusError').style.display = 'block';
  document.getElementById('errorMessage').textContent = mensaje;
  document.getElementById('qrCodeContainer').style.display = 'none';
  document.getElementById('actionButtons').style.display = 'none';
  document.getElementById('btnGenerarNuevoContainer').style.display = 'block';
}

// Ocultar todos los estados
function ocultarTodosEstados() {
  document.getElementById('statusPending').style.display = 'none';
  document.getElementById('statusPaid').style.display = 'none';
  document.getElementById('statusExpired').style.display = 'none';
  document.getElementById('statusCancelled').style.display = 'none';
  document.getElementById('statusError').style.display = 'none';
}

// Iniciar contador de expiración
function iniciarContador() {
  detenerContador();
  
  countdownInterval = setInterval(function() {
    const now = new Date();
    const timeLeft = expirationTime - now;
    
    if (timeLeft <= 0) {
      detenerContador();
      ordenExpirada();
      return;
    }
    
    const minutes = Math.floor(timeLeft / 60000);
    const seconds = Math.floor((timeLeft % 60000) / 1000);
    
    document.getElementById('countdown').textContent = 
      String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
  }, 1000);
}

// Detener contador
function detenerContador() {
  if (countdownInterval) {
    clearInterval(countdownInterval);
    countdownInterval = null;
  }
}

// Detener polling
function detenerPolling() {
  if (pollingInterval) {
    clearInterval(pollingInterval);
    pollingInterval = null;
  }
}

// Limpiar intervalos al salir de la página
window.addEventListener('beforeunload', function() {
  detenerPolling();
  detenerContador();
});
</script>

@endsection

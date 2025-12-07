@extends('principal.principal')

@section('body')

<div class="container">

  <nav>
    <ul>
        <li>
            <h1>Confirmar Pago: ${{$importe}}</h1>
        </li>
        <li>
          {{-- <input type="search" id="search" name="search" placeholder="Search"> --}}
        </li>
    </ul>
    <ul>
        <li>

        </li>
    </ul>
</nav>

<!-- Información del Cliente -->
<article>
  <header>
    <strong>Información del Cliente</strong>
  </header>
  <div class="grid">
    <div>
      <small><strong>Nombre:</strong></small>
      <p>{{$servicio->nombreCliente}}</p>
    </div>
    <div>
      <small><strong>DNI:</strong></small>
      <p>{{$servicio->dniCliente}}</p>
    </div>
    <div>
      <small><strong>Teléfono:</strong></small>
      <p>
        @if($servicio->telefonoCliente)
          <a href="https://wa.me/+54{{$servicio->telefonoCliente}}" target="_blank" rel="noopener noreferrer">
            {{$servicio->telefonoCliente}}
          </a>
        @else
          -
        @endif
      </p>
    </div>
  </div>
  <div class="grid">
    <div>
      <small><strong>Correo:</strong></small>
      <p>{{$servicio->correoCliente ?? '-'}}</p>
    </div>
    <div>
      <small><strong>Domicilio:</strong></small>
      <p>{{$servicio->domicilioCliente ?? '-'}}</p>
    </div>
  </div>
</article>

<!-- Información del Servicio -->
<article>
  <header>
    <strong>Detalles del Servicio</strong>
  </header>
  <div class="grid">
    <div>
      <small><strong>Servicio:</strong></small>
      <p>{{$servicio->nombre}}</p>
    </div>
    <div>
      <small><strong>Descripción:</strong></small>
      <p>{{$servicio->descripcion}}</p>
    </div>
    <div>
      <small><strong>Periodicidad:</strong></small>
      <p style="text-transform: capitalize;">{{$servicio->tiempo}}</p>
    </div>
  </div>
  <div class="grid">
    <div>
      <small><strong>Precio Unitario:</strong></small>
      <p>${{number_format($servicio->precio, 2)}}</p>
    </div>
    <div>
      <small><strong>Cantidad:</strong></small>
      <p>{{$servicio->cantidad}} unidad(es)</p>
    </div>
    <div>
      <small><strong>Total:</strong></small>
      <p><mark>${{number_format($servicio->total, 2)}}</mark></p>
    </div>
  </div>
  <div class="grid">
    <div>
      <small><strong>Estado:</strong></small>
      <p>
        @if($servicio->estado == 'pago')
          <span style="color: green;">✓ Pagado</span>
        @else
          <span style="color: red;">✗ Impago</span>
        @endif
      </p>
    </div>
    <div>
      <small><strong>Fecha de Creación:</strong></small>
      <p>{{$servicio->created_at->format('d/m/Y H:i')}}</p>
    </div>
  </div>
</article>

</div>

<div class="container">
           
    <form method="POST" id="confirmarPago" action="{{route('ConfirmarPago')}}">
        @csrf

        <article>
          <header>
            <strong>Formulario de Pago</strong>
          </header>

          <input type="hidden" id="idServicioPagar" name="idServicioPagar" value="{{$idServicioPagar}}" readonly>
          <input type="hidden" id="importeOriginal" name="importeOriginal" value="{{$importe}}" readonly>

          <!-- Grid -->
          <div class="grid">      
            <label for="servicioNombre">
              Servicio
              <input type="text" id="servicioNombre" name="servicioNombre" value="{{$servicio->nombre}}" readonly>
            </label>
        
            <label for="importeOriginalDisplay">
              Importe Original $
              <input type="text" id="importeOriginalDisplay" name="importeOriginalDisplay"  value="{{$importe}}" readonly>
            </label>
        
          </div>

          <!-- Sección de Descuento/Incremento -->
          <HR></HR>
          <label>
            <input name="aplicarAjuste" id="aplicarAjuste" type="checkbox" role="switch" />
            Aplicar descuento o incremento al importe
          </label>
          <HR></HR>

          <!-- Campos de Descuento/Incremento (Ocultos por defecto) -->
          <div id="ajusteImporteContainer" style="display: none;">
            <div class="grid">
              <label for="tipoAjuste">
                Tipo de Ajuste <small>(Requerido)</small>
                <select id="tipoAjuste" name="tipoAjuste">
                  <option value="">-- Seleccionar --</option>
                  <option value="descuento">Descuento (-)</option>
                  <option value="incremento">Incremento (+)</option>
                </select>
              </label>

              <label for="ajusteTipo">
                Aplicar por <small>(Requerido)</small>
                <select id="ajusteTipo" name="ajusteTipo">
                  <option value="">-- Seleccionar --</option>
                  <option value="porcentaje">Porcentaje (%)</option>
                  <option value="monto">Monto Fijo ($)</option>
                </select>
              </label>

              <label for="valorAjuste">
                Valor <small>(Requerido)</small>
                <input type="number" id="valorAjuste" name="valorAjuste" step="0.01" min="0" value="0" placeholder="0.00">
              </label>
            </div>

            <div class="grid">
              <div>
                <small><strong>Importe Final:</strong></small>
                <p id="importeFinalDisplay" style="font-size: 1.5em; color: var(--primary);"><mark>$ {{number_format($importe, 2)}}</mark></p>
              </div>
              <div>
                <small id="detalleAjuste" style="display: none;"><strong>Detalle del ajuste:</strong></small>
                <p id="mensajeAjuste" style="font-size: 0.9em; color: #666;"></p>
              </div>
            </div>
          </div>

          <!-- Campo oculto para el importe final -->
          <input type="hidden" id="importe" name="importe" value="{{$importe}}">

          <HR></HR>

          <!-- Grid -->
          <div class="grid">      
        
            <label for="comentario">
              Comentario <small>(Opcional)</small>
              <textarea name="comentario" id="comentario" cols="30" rows="2" placeholder="Agregar un comentario sobre el pago..."></textarea>
            </label>

          </div>

          <HR></HR>

          <div class="grid">
            <!-- Checkbox para activar segunda forma de pago -->
            <label>
              <input name="dosFormasPago" id="dosFormasPago" type="checkbox" role="switch" />
              Dividir pago en dos formas de pago
            </label>

            @if($posMpId)
              <a href="{{ route('GenerarQRMercadoPago', ['posMpId' => $posMpId, 'importe' => $importe, 'servicioPagar' => $idServicioPagar]) }}" target="_blank" class="button">Generar QR MercadoPago</a>
            @endif

          </div>


          <HR></HR>

          <!-- Primera Forma de Pago -->
          <div class="grid">      
            <label for="formaPago">
              Forma de Pago 1 <small>(Requerido)</small>
              <select id="formaPago" name="formaPago" required>
                <option value="">-- Seleccionar --</option>
                @foreach ($formaPago as $elemento)
                  <option value="{{$elemento->id}}">{{$elemento->nombre}}</option>
                @endforeach

              </select>
            </label>

            <label for="importe1">
              Importe 1 $ <small id="labelImporte1">(Requerido)</small>
              <input type="number" id="importe1" name="importe1" step="0.01" min="0" value="{{$importe}}" readonly>
            </label>
          </div>

          <!-- Segunda Forma de Pago (Oculta por defecto) -->
          <div id="segundaFormaPagoContainer" style="display: none;">
            <div class="grid">      
              <label for="formaPago2">
                Forma de Pago 2 <small>(Requerido)</small>
                <select id="formaPago2" name="formaPago2">
                  <option value="">-- Seleccionar --</option>
                  @foreach ($formaPago as $elemento)
                    <option value="{{$elemento->id}}">{{$elemento->nombre}}</option>
                  @endforeach
                </select>
              </label>

              <label for="importe2">
                Importe 2 $ <small>(Requerido)</small>
                <input type="number" id="importe2" name="importe2" step="0.01" min="0" value="0">
              </label>
            </div>
            <div id="errorSuma" style="color: red; display: none; margin-top: -10px; margin-bottom: 10px;">
              <small>⚠️ La suma de los importes debe ser igual al total: ${{$importe}}</small>
            </div>
          </div>

          <HR></HR>
          <label>
            <input name="comprobantePDF" type="checkbox" role="switch" />
            Imprimir Comprobante PDF
          </label>
          <HR></HR>

        
          <!-- Button -->
          <div class="grid">
            <a href="{{ url()->previous() }}" role="button" class="secondary">Cancelar</a>
            <button type="submit">Confirmar Pago</button>
          </div>
        </article>
      
    </form>


</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dosFormasPagoCheckbox = document.getElementById('dosFormasPago');
    const segundaFormaPagoContainer = document.getElementById('segundaFormaPagoContainer');
    const formaPago2 = document.getElementById('formaPago2');
    const importe1 = document.getElementById('importe1');
    const importe2 = document.getElementById('importe2');
    const labelImporte1 = document.getElementById('labelImporte1');
    const errorSuma = document.getElementById('errorSuma');
    const form = document.getElementById('confirmarPago');
    const importeOriginal = parseFloat('{{$importe}}');
    
    // Elementos de ajuste de importe
    const aplicarAjusteCheckbox = document.getElementById('aplicarAjuste');
    const ajusteImporteContainer = document.getElementById('ajusteImporteContainer');
    const tipoAjuste = document.getElementById('tipoAjuste');
    const ajusteTipo = document.getElementById('ajusteTipo');
    const valorAjuste = document.getElementById('valorAjuste');
    const importeFinalDisplay = document.getElementById('importeFinalDisplay');
    const mensajeAjuste = document.getElementById('mensajeAjuste');
    const detalleAjuste = document.getElementById('detalleAjuste');
    const importeHidden = document.getElementById('importe');
    
    let totalPago = importeOriginal;

    // Función para calcular el importe final con ajuste
    function calcularImporteFinal() {
        if (!aplicarAjusteCheckbox.checked) {
            totalPago = importeOriginal;
            importeFinalDisplay.innerHTML = '<mark>$ ' + totalPago.toFixed(2) + '</mark>';
            importeHidden.value = totalPago.toFixed(2);
            importe1.value = totalPago.toFixed(2);
            mensajeAjuste.textContent = '';
            detalleAjuste.style.display = 'none';
            return;
        }

        const tipo = tipoAjuste.value;
        const metodo = ajusteTipo.value;
        const valor = parseFloat(valorAjuste.value || 0);

        if (!tipo || !metodo || valor <= 0) {
            totalPago = importeOriginal;
            importeFinalDisplay.innerHTML = '<mark>$ ' + totalPago.toFixed(2) + '</mark>';
            importeHidden.value = totalPago.toFixed(2);
            importe1.value = totalPago.toFixed(2);
            mensajeAjuste.textContent = 'Complete todos los campos para calcular el ajuste';
            detalleAjuste.style.display = 'block';
            return;
        }

        let ajusteCalculado = 0;
        let mensaje = '';

        // Calcular el ajuste según el tipo
        if (metodo === 'porcentaje') {
            ajusteCalculado = importeOriginal * (valor / 100);
            mensaje = valor.toFixed(2) + '% ';
        } else if (metodo === 'monto') {
            ajusteCalculado = valor;
            mensaje = '$ ' + valor.toFixed(2) + ' ';
        }

        // Aplicar descuento o incremento
        if (tipo === 'descuento') {
            totalPago = importeOriginal - ajusteCalculado;
            mensaje = 'Descuento de ' + mensaje + '(- $' + ajusteCalculado.toFixed(2) + ')';
            importeFinalDisplay.innerHTML = '<mark style="background-color: #d4edda; color: #155724;">$ ' + totalPago.toFixed(2) + '</mark>';
        } else if (tipo === 'incremento') {
            totalPago = importeOriginal + ajusteCalculado;
            mensaje = 'Incremento de ' + mensaje + '(+ $' + ajusteCalculado.toFixed(2) + ')';
            importeFinalDisplay.innerHTML = '<mark style="background-color: #fff3cd; color: #856404;">$ ' + totalPago.toFixed(2) + '</mark>';
        }

        // Validar que el total no sea negativo
        if (totalPago < 0) {
            totalPago = 0;
            mensaje += ' (Ajustado a $0.00 - no puede ser negativo)';
        }

        importeHidden.value = totalPago.toFixed(2);
        importe1.value = totalPago.toFixed(2);
        mensajeAjuste.textContent = mensaje;
        detalleAjuste.style.display = 'block';

        // Si hay dos formas de pago activas, recalcular
        if (dosFormasPagoCheckbox.checked) {
            const mitad = (totalPago / 2).toFixed(2);
            importe1.value = mitad;
            importe2.value = mitad;
        }
    }

    // Función para validar la suma de importes
    function validarSuma() {
        if (!dosFormasPagoCheckbox.checked) {
            errorSuma.style.display = 'none';
            return true;
        }

        const suma = parseFloat(importe1.value || 0) + parseFloat(importe2.value || 0);
        const diferencia = Math.abs(suma - totalPago);

        if (diferencia > 0.01) { // Tolerancia de 1 centavo por redondeos
            errorSuma.innerHTML = '<small>⚠️ La suma de los importes debe ser igual al total: $' + totalPago.toFixed(2) + '</small>';
            errorSuma.style.display = 'block';
            return false;
        } else {
            errorSuma.style.display = 'none';
            return true;
        }
    }

    // Toggle del ajuste de importe
    aplicarAjusteCheckbox.addEventListener('change', function() {
        if (this.checked) {
            ajusteImporteContainer.style.display = 'block';
            tipoAjuste.required = true;
            ajusteTipo.required = true;
            valorAjuste.required = true;
        } else {
            ajusteImporteContainer.style.display = 'none';
            tipoAjuste.required = false;
            ajusteTipo.required = false;
            valorAjuste.required = false;
            tipoAjuste.value = '';
            ajusteTipo.value = '';
            valorAjuste.value = '0';
            calcularImporteFinal();
        }
    });

    // Eventos para recalcular el importe final
    tipoAjuste.addEventListener('change', calcularImporteFinal);
    ajusteTipo.addEventListener('change', calcularImporteFinal);
    valorAjuste.addEventListener('input', calcularImporteFinal);

    // Toggle de la segunda forma de pago
    dosFormasPagoCheckbox.addEventListener('change', function() {
        if (this.checked) {
            segundaFormaPagoContainer.style.display = 'block';
            formaPago2.required = true;
            importe1.readOnly = false;
            importe2.required = true;
            labelImporte1.innerHTML = '(Requerido)';
            
            // Dividir el importe en dos partes iguales por defecto
            const mitad = (totalPago / 2).toFixed(2);
            importe1.value = mitad;
            importe2.value = mitad;
        } else {
            segundaFormaPagoContainer.style.display = 'none';
            formaPago2.required = false;
            formaPago2.value = '';
            importe1.readOnly = true;
            importe2.required = false;
            importe1.value = totalPago.toFixed(2);
            importe2.value = '0';
            labelImporte1.innerHTML = '(Requerido)';
            errorSuma.style.display = 'none';
        }
    });

    // Validar en tiempo real cuando cambian los importes
    importe1.addEventListener('input', validarSuma);
    importe2.addEventListener('input', validarSuma);

    // Validar antes de enviar el formulario
    form.addEventListener('submit', function(e) {
        if (!validarSuma()) {
            e.preventDefault();
            alert('La suma de los importes debe ser igual al total del pago: $' + totalPago.toFixed(2));
            return false;
        }

        // Validar campos de ajuste si está activo
        if (aplicarAjusteCheckbox.checked) {
            if (!tipoAjuste.value || !ajusteTipo.value || parseFloat(valorAjuste.value || 0) <= 0) {
                e.preventDefault();
                alert('Por favor complete todos los campos de descuento/incremento o desactive la opción.');
                return false;
            }
        }
    });
});
</script>
    
@endsection
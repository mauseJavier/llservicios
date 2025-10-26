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
           
    <form method="POST" action="{{route('ConfirmarPago')}}">
        @csrf

        <article>
          <header>
            <strong>Formulario de Pago</strong>
          </header>

          <input type="hidden" id="idServicioPagar" name="idServicioPagar" value="{{$idServicioPagar}}" readonly>

          <!-- Grid -->
          <div class="grid">      
            <label for="servicioNombre">
              Servicio
              <input type="text" id="servicioNombre" name="servicioNombre" value="{{$servicio->nombre}}" readonly>
            </label>
        
            <label for="importe">
              Importe a Pagar $
              <input type="text" id="importe" name="importe"  value="{{$importe}}" readonly>
            </label>
        
          </div>

          <!-- Grid -->
          <div class="grid">      
        
            <label for="comentario">
              Comentario <small>(Opcional)</small>
              <textarea name="comentario" id="comentario" cols="30" rows="2" placeholder="Agregar un comentario sobre el pago..."></textarea>
            </label>

            <!-- Select -->
            <label for="formaPago">
              Forma de Pago <small>(Requerido)</small>
              <select id="formaPago" name="formaPago" required>
                <option value="">-- Seleccionar --</option>
                @foreach ($formaPago as $elemento)
                  <option value="{{$elemento->id}}">{{$elemento->nombre}}</option>
                @endforeach
              </select>
            </label>
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
    
@endsection
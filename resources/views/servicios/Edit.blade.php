@extends('principal.principal')

@section('body')


<div class="container">
    <nav>
      <ul>
          <li>
              <h1>Editar: {{$Servicio->nombre}}</h1>
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
</div>
<div class="container">

  {{-- {
    "id": 202,
    "nombre": "jajajajaja",
    "descripcion": "jajajaj",
    "precio": 33.44,
    "tiempo": "mes",
    "empresa_id": 1,
    "linkPago": "jajaja.com",
    "imagen": "imagen.com",
    "created_at": "2024-03-09T00:01:18.000000Z",
    "updated_at": "2024-03-09T00:01:18.000000Z"
  }
            --}}

    <form method="POST" action="{{route('Servicios.update',['Servicio'=>$Servicio->id])}}">
        @csrf
        @method('PUT') 

        <!-- Grid -->
      <div class="grid">      
            <!-- Markup example 1: input is inside label -->
            <label for="nombre">
              Nombre Servicio
              <input type="text" id="nombre" name="nombre" placeholder="Nombre Servicio" value="{{$Servicio->nombre}}" required>
            </label>
        


            <label for="diasVencimiento">
              Días Vencimiento
              <input type="number" min="1" id="diasVencimiento" name="diasVencimiento" placeholder="Días Vencimiento" value="{{$Servicio->diasVencimiento ?? 10}}" required>
            </label>

            <label for="descripcion">
              Descripcion
              <input type="text" id="descripcion" name="descripcion" placeholder="Descripcion" value="{{$Servicio->descripcion}}" required>
            </label>

      
      </div>

      <div class="grid">

            <label for="precio">
              Precio
              <input type="numeric" id="precio" name="precio" placeholder="Precio" value="{{$Servicio->precio}}" required>
            </label>

            <label for="precio2">
              Precio 2
              <input type="number" step="0.01" min="0" id="precio2" name="precio2" placeholder="Precio 2 (opcional)" value="{{$Servicio->precio2}}">
            </label>

            <label for="precio3">
              Precio 3
              <input type="number" step="0.01" min="0" id="precio3" name="precio3" placeholder="Precio 3 (opcional)" value="{{$Servicio->precio3}}">
            </label>

      </div>

      <div class="grid">



          <label for="linkPago">
            Link de Pago
            <input type="text" id="linkPago" name="linkPago" placeholder="Link de Pago" value="{{$Servicio->linkPago}}">
          </label>

          <label for="imagen">
            Link de Imagen
            <input type="text" id="imagen" name="imagen" placeholder="Link de Imagen" value="{{$Servicio->imagen}}" >
          </label>

          <!-- Select -->
          <label for="tiempo">Tiempo
          <select id="tiempo" name="tiempo" required>
            <option value="hora" selected>Hora</option>
            <option value="dia" selected>Dia</option>
            <option value="semana" selected>Semana</option>
            <option value="mes" selected>Mes</option>
          </select>
        </label>


      </div>

      <!-- Recargo por mora -->
      <div class="grid">
        <label>
          <input type="hidden" name="aplicarIncrementoMora" value="0">
          <input name="aplicarIncrementoMora" id="aplicarIncrementoMora" type="checkbox" role="switch" value="1"
            {{ !empty($Servicio->incremento_mora_tipo) ? 'checked' : '' }} />
          Aplicar recargo por mora
        </label>
      </div>

      <div id="incrementoMoraContainer" style="{{ !empty($Servicio->incremento_mora_tipo) ? 'display: block;' : 'display: none;' }}">
        <div class="grid">
          <label for="incremento_mora_tipo">
            Tipo de Recargo
            <select id="incremento_mora_tipo" name="incremento_mora_tipo">
              <option value="">-- Seleccionar --</option>
              <option value="fijo" {{ ($Servicio->incremento_mora_tipo ?? '') == 'fijo' ? 'selected' : '' }}>Monto Fijo ($)</option>
              <option value="porcentaje" {{ ($Servicio->incremento_mora_tipo ?? '') == 'porcentaje' ? 'selected' : '' }}>Porcentaje (%)</option>
            </select>
          </label>

          <label for="incremento_mora_valor">
            Valor del Recargo
            <input type="number" id="incremento_mora_valor" name="incremento_mora_valor" step="0.01" min="0" placeholder="0.00" value="{{$Servicio->incremento_mora_valor ?? ''}}">
          </label>
        </div>
      </div>

      <script>
        document.addEventListener('DOMContentLoaded', function() {
          const aplicarIncrementoMoraCheckbox = document.getElementById('aplicarIncrementoMora');
          const incrementoMoraContainer = document.getElementById('incrementoMoraContainer');
          const incrementoMoraTipo = document.getElementById('incremento_mora_tipo');
          const incrementoMoraValor = document.getElementById('incremento_mora_valor');

          function toggleIncrementoMora() {
            if (aplicarIncrementoMoraCheckbox.checked) {
              incrementoMoraContainer.style.display = 'block';
              incrementoMoraTipo.required = true;
              incrementoMoraValor.required = true;
            } else {
              incrementoMoraContainer.style.display = 'none';
              incrementoMoraTipo.required = false;
              incrementoMoraValor.required = false;
            }
          }

          aplicarIncrementoMoraCheckbox.addEventListener('change', toggleIncrementoMora);
          toggleIncrementoMora();
        });
      </script>

        <!-- Button -->
        <button type="submit">Guardar</button>
      
    </form>
    <a href="{{ url()->previous() }}" role="button">Volver</a>


</div>
    
@endsection
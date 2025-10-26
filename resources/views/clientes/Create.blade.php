@extends('principal.principal')

@section('body')

<nav>
    <ul>
        <li>
            <h1>Nuevo Cliente</h1>
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
<div class="container">
              {{-- nombre
              correo
              dni
              empresa_id --}}

    <form method="POST" action="{{route('Cliente.store')}}">
        @csrf

        <!-- Grid -->
        <div class="grid">      
          <!-- Markup example 1: input is inside label -->
          <label for="nombre">
            Nombre Cliente
            <input type="text" id="nombre" name="nombre" placeholder="Nombre Cliente" value="{{old('nombre')}}" required>
          </label>
      
          <label for="dni">
            Dni Clinete
            <input type="text" id="dni" name="dni" placeholder="Dni" value="{{old('dni')}}" required>
          </label>
      
        </div>
      
        <label for="telefono">
          Telefono Cliente
          <input type="text" id="telefono" name="telefono" placeholder="Telefono Cliente" value="{{old('telefono')}}" >
        </label>


        <!-- Markup example 2: input is after label -->
        <label for="correo">Correo Electronico <small>(Opcional).</small></label>
        <input type="email" id="correo" name="correo" placeholder="Correo Electronico" value="{{old('correo')}}">
        

        <details>
          <summary>Mas Opciones</summary>

          <div class="grid">
            <label for="domicilio">
              Domicilio Cliente <small>(Opcional).</small>
              <input type="text" id="domicilio" name="domicilio" placeholder="Domicilio Cliente" value="{{old('domicilio')}}" >
            </label>

          </div>

          <div class="grid">
            <label for="servicio_id">
              Vincular a un Servicio <small>(Opcional).</small>
              <select id="servicio_id" name="servicio_id">
                <option value="">-- Seleccionar Servicio --</option>
                @if(isset($servicios))
                  @foreach($servicios as $servicio)
                    <option value="{{$servicio->id}}" {{ old('servicio_id') == $servicio->id ? 'selected' : '' }}>
                      {{$servicio->nombre}} - ${{$servicio->precio}}
                    </option>
                  @endforeach
                @endif
              </select>
            </label>

            <label for="vencimiento" id="vencimiento_label" style="display: none;">
              Fecha de Vencimiento
              <input type="datetime-local" id="vencimiento" name="vencimiento" value="{{old('vencimiento')}}">
            </label>
          </div>

          <div class="grid" id="cantidad_container" style="display: none;">
            <label for="cantidad">
              Cantidad
              <input type="number" id="cantidad" name="cantidad" value="{{old('cantidad', 1)}}" min="0.5" step="0.5">
            </label>
          </div>

        </details>

        <script>
          // Mostrar/ocultar campos de vencimiento y cantidad cuando se selecciona un servicio
          document.getElementById('servicio_id').addEventListener('change', function() {
            const vencimientoLabel = document.getElementById('vencimiento_label');
            const cantidadContainer = document.getElementById('cantidad_container');
            
            if (this.value) {
              vencimientoLabel.style.display = 'block';
              cantidadContainer.style.display = 'block';
              
              // Establecer fecha por defecto (1 año desde hoy)
              if (!document.getElementById('vencimiento').value) {
                const now = new Date();
                now.setFullYear(now.getFullYear() + 1);
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                document.getElementById('vencimiento').value = `${year}-${month}-${day}T${hours}:${minutes}`;
              }
            } else {
              vencimientoLabel.style.display = 'none';
              cantidadContainer.style.display = 'none';
            }
          });
        </script>

      
        <!-- Button -->
        <button type="submit">Guardar</button>
      
    </form>
    <a href="{{ url()->previous() }}" role="button">Volver</a>


</div>
    
@endsection
@extends('principal.principal')

@section('body')

<div class="container">
    <nav>
        <ul>
            <li>
                <h1>Editar: {{$Cliente->nombre}}</h1>
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
              {{-- nombre
              correo
              dni
              empresa_id --}}

    <form method="POST" action="{{route('Cliente.update',['Cliente'=>$Cliente->id])}}">
        @csrf
        @method('PUT') 

        <!-- Grid -->
        <div class="grid">      
          <!-- Markup example 1: input is inside label -->
          <label for="nombre">
            Nombre Cliente
            <input type="text" id="nombre" name="nombre" placeholder="Nombre Cliente" value="{{$Cliente->nombre}}" required>
          </label>

          <label for="titular">
            Titular Cliente <small>(Opcional).</small>
            <input type="text" id="titular" name="titular" placeholder="Titular Cliente" value="{{$Cliente->titular}}" >
          </label>
      
          <label for="dni">
            Dni Clinete
            <input type="text" id="dni" name="dni" placeholder="Dni" value="{{$Cliente->dni}}" required>
          </label>
      
        </div>
      
        <label for="telefono">
          Telefono Cliente <small>(Opcional).</small>
          <input type="text" id="telefono" name="telefono" placeholder="Telefono Cliente" value="{{$Cliente->telefono}}" >
        </label>

        <!-- Markup example 2: input is after label -->
        <label for="correo">Correo Electronico <small>(Opcional).</small></label>
        <input type="email" id="correo" name="correo" placeholder="Correo Electronico" value="{{$Cliente->correo}}">

        <details>
          <summary>Mas Opciones</summary>

          <div class="grid">
            <label for="domicilio">
              Domicilio Cliente <small>(Opcional).</small>
              <input type="text" id="domicilio" name="domicilio" placeholder="Domicilio Cliente" value="{{$Cliente->domicilio}}" >
            </label>

          </div>

          <div class="grid">
            <label for="condicion_iva_id">
              Condición frente al IVA <small>(Receptor).</small>
              <select id="condicion_iva_id" name="condicion_iva_id">
                @foreach(\App\Services\AfipService::tiposContribuyentes() as $codigo => $info)
                  <option value="{{ $codigo }}" {{ ($Cliente->condicion_iva_id ?? 5) == $codigo ? 'selected' : '' }}>
                    {{ $info['Desc'] }} ({{ $info['Cmp_Clase'] }})
                  </option>
                @endforeach
              </select>
            </label>
          </div>

          <div class="grid">
            <label>
              <input type="hidden" name="aplicar_recargos" value="0">
              <input name="aplicar_recargos" id="aplicar_recargos" type="checkbox" role="switch" value="1"
                {{ ($Cliente->aplicar_recargos ?? false) ? 'checked' : '' }} />
              Aplicar recargos por mora
              <small>Si está desactivado, no se le aplicarán recargos a este cliente aunque venza el pago.</small>
            </label>
          </div>

          

        </details>
      
        <!-- Button -->
        <button type="submit">Guardar</button>
      
    </form>
    <a href="{{ url()->previous() }}" role="button">Volver</a>


</div>
    
@endsection
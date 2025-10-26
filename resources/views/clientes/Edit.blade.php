@extends('principal.principal')

@section('body')

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
<div class="container">
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
        <label for="correo">Correo Electronico</label>
        <input type="email" id="correo" name="correo" placeholder="Correo Electronico" value="{{$Cliente->correo}}" required>
        <small>Opcional.</small>

        <details>
          <summary>Mas Opciones</summary>

          <div class="grid">
            <label for="domicilio">
              Domicilio Cliente <small>(Opcional).</small>
              <input type="text" id="domicilio" name="domicilio" placeholder="Domicilio Cliente" value="{{$Cliente->domicilio}}" >
            </label>

          </div>

          

        </details>
      
        <!-- Button -->
        <button type="submit">Guardar</button>
      
    </form>
    <a href="{{ url()->previous() }}" role="button">Volver</a>


</div>
    
@endsection
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
      
        <!-- Markup example 2: input is after label -->
        <label for="correo">Correo Electronico</label>
        <input type="email" id="correo" name="correo" placeholder="Correo Electronico" value="{{old('correo')}}" required>
        <small>Opcional.</small>
      
        <!-- Button -->
        <button type="submit">Guardar</button>
      
    </form>
    <a href="{{ url()->previous() }}" role="button">Volver</a>


</div>
    
@endsection
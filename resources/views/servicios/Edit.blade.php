@extends('principal.principal')

@section('body')

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
<div class="container">
           

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
      
          <label for="precio">
            Precio
            <input type="numeric" id="precio" name="precio" placeholder="Precio" value="{{$Servicio->precio}}" required>
          </label>
      
          <label for="descripcion">
            Descripcion
            <input type="text" id="descripcion" name="descripcion" placeholder="Descripcion" value="{{$Servicio->descripcion}}" required>
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
      
        <!-- Button -->
        <button type="submit">Guardar</button>
      
    </form>
    <a href="{{ url()->previous() }}" role="button">Volver</a>


</div>
    
@endsection
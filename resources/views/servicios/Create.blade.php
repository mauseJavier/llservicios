@extends('principal.principal')

@section('body')



<div class="container">
    <nav>
      <ul>
          <li>
              <h1>Nuevo Servicio</h1>
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
     

    <form method="POST" action="{{route('Servicios.store')}}">
        @csrf

        <!-- Grid -->
        <div class="grid">      
          <!-- Markup example 1: input is inside label -->
          <label for="nombre">
            Nombre Servicio
            <input type="text" id="nombre" name="nombre" placeholder="Nombre Servicio" value="{{old('nombre')}}" required>
          </label>

          <label for="precio">
            Precio
            <input type="number" step="0.01" min="0"  id="precio" name="precio" placeholder="Precio" value="{{old('precio')}}" required>            
          </label>
      
          <label for="descripcion">
            Descripcion
            <textarea name="descripcion" id="descripcion" cols="10" rows="1">{{old('descripcion')}}</textarea>
            {{-- <input type="textarea" id="descripcion" name="descripcion" placeholder="Descripcion" value="{{old('descripcion')}}" required> --}}
          </label>

      
        </div>

        <div class="grid">


          <label for="linkPago">
            Link de Pago
            <input type="text" id="linkPago" name="linkPago" placeholder="Link de Pago" value="{{old('linkPago')}}">
          </label>

          <label for="imagen">
            Link de Imagen
            <input type="text" id="imagen" name="imagen" placeholder="Link de Imagen" value="{{old('imagen')}}">
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
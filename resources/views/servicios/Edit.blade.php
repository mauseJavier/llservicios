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
        
            <label for="precio">
              Precio
              <input type="numeric" id="precio" name="precio" placeholder="Precio" value="{{$Servicio->precio}}" required>
            </label>

            <label for="descripcion">
              Descripcion
              <input type="text" id="descripcion" name="descripcion" placeholder="Descripcion" value="{{$Servicio->descripcion}}" required>
            </label>

      
      </div>

      <div class="grid">



          <label for="linkPago">
            Link de Pago
            <input type="text" id="linkPago" name="linkPago" placeholder="Link de Pago" value="{{$Servicio->linkPago}}" required>
          </label>

          <label for="imagen">
            Link de Imagen
            <input type="text" id="imagen" name="imagen" placeholder="Link de Imagen" value="{{$Servicio->imagen}}" required>
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
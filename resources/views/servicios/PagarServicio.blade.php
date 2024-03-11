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

</div>


<div class="container">
           

    <form method="POST" action="{{route('ConfirmarPago')}}">
        @csrf
        {{-- [ servicio
          {
            "id": 1,
            "cliente_id": 17,
            "servicio_id": 1,
            "cantidad": 1,
            "precio": 5000,
            "estado": "pago",
            "created_at": "2024-02-19 15:21:52",
            "updated_at": "2024-02-19 15:25:26",
            "nombre": "Gym Basico",
            "descripcion": "GYM BASICO",
            "tiempo": "mes",
            "empresa_id": 1
          }
        ] --}}

        <!-- Grid -->
        <div class="grid">      
          <!-- Markup example 1: input is inside label -->

            <input type="hidden" id="idServicioPagar" name="idServicioPagar" value="{{$idServicioPagar}}" readonly>


          <label for="Servicio">
            Servicio
            <input type="text" id="servicioNombre" name="servicioNombre" value="{{$servicio->nombre}}" readonly>
          </label>
      
          <label for="importe">
            importe
            <input type="numeric" id="importe" name="importe"  value="{{$importe}}" readonly>
          </label>
      
        </div>

        <!-- Grid -->
        <div class="grid">      

      
          <label for="comentario">
            Comentario
            <textarea name="comentario" id="comentario" cols="30" rows="1"></textarea>
            
          </label>

          <!-- Select -->
          <label for="formaPago">Forma Pago 
          <select id="formaPago" name="formaPago" required>
            
            @foreach ($formaPago as $elemento)
                <option value="{{$elemento->id}}">{{$elemento->nombre}}</option>
            @endforeach
          </select>
        </label>
        </div>

        <HR></HR>
        <label>
          <input name="comprobantePDF" type="checkbox" role="switch" />
          Iprimir Comprobante PDF
        </label>
        <HR></HR>

      
        <!-- Button -->
        <button type="submit">Pagar</button>
      
    </form>
    <a href="{{ url()->previous() }}" role="button">Volver</a>


</div>
    
@endsection
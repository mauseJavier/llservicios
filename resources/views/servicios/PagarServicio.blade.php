@extends('principal.principal')

@section('body')

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
<div class="container">
           

    <form method="POST" action="{{route('ConfirmarPago')}}">
        @csrf

        <!-- Grid -->
        <div class="grid">      
          <!-- Markup example 1: input is inside label -->
          <label for="idServicioPagar">
            idServicioPagar
            <input type="text" id="idServicioPagar" name="idServicioPagar" value="{{$idServicioPagar}}" readonly>
          </label>
      
          <label for="importe">
            importe
            <input type="numeric" id="importe" name="importe"  value="{{$importe}}" readonly>
          </label>
      
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
      
        <!-- Button -->
        <button type="submit">Pagar</button>
      
    </form>
    <a href="{{ url()->previous() }}" role="button">Volver</a>


</div>
    
@endsection
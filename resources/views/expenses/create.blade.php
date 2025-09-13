@extends('principal.principal')

@section('body')

<nav>
    <ul>
        <li>
            <h1>Nuevo Gasto</h1>
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

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{route('expenses.store')}}">
        @csrf

        <!-- Grid -->
        <div class="grid">      
          <!-- Markup example 1: input is inside label -->
          <label for="detalle">
            Detalle del Gasto
            <input type="text" id="detalle" name="detalle" placeholder="Detalle del Gasto" value="{{old('detalle')}}" required>
          </label>
      
          <label for="importe">
            Importe
            <input type="number" step="0.01" id="importe" name="importe" placeholder="0.00" value="{{old('importe')}}" required>
          </label>
      
        </div>

        <div class="grid">
          <label for="forma_pago">
            Forma de Pago
            <input type="text" id="forma_pago" name="forma_pago" placeholder="Efectivo, Tarjeta, etc." value="{{old('forma_pago')}}" required>
          </label>
      
          <label for="estado">
            Estado
            <input type="text" id="estado" name="estado" placeholder="Pendiente, Pagado, etc." value="{{old('estado')}}" required>
          </label>
        </div>
      
        <!-- Markup example 2: input is after label -->
        <label for="comentario">Comentario</label>
        <textarea id="comentario" name="comentario" placeholder="Comentarios adicionales (opcional)" rows="3">{{old('comentario')}}</textarea>
        <small>Opcional.</small>
      
        <!-- Button -->
        <button type="submit">Guardar</button>
      
    </form>
    <a href="{{ route('expenses.index') }}" role="button">Volver</a>

</div>
    
@endsection
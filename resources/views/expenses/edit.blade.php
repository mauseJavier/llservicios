@extends('principal.principal')

@section('body')

<nav>
    <ul>
        <li>
            <h1>Editar Gasto</h1>
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
        <article style="background-color: #f8d7da; border-color: #f5c6cb; color: #721c24;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </article>
    @endif

    <form method="POST" action="{{route('expenses.update', ['expense' => $expense->id])}}">
        @csrf
        @method('PUT')

        <!-- Grid -->
        <div class="grid">      
          <!-- Markup example 1: input is inside label -->
          <label for="detalle">
            Detalle del Gasto
            <input type="text" id="detalle" name="detalle" placeholder="Detalle del Gasto" value="{{old('detalle', $expense->detalle)}}" required>
          </label>
      
          <label for="importe">
            Importe
            <input type="number" step="0.01" id="importe" name="importe" placeholder="0.00" value="{{old('importe', $expense->importe)}}" required>
          </label>
      
        </div>

        <div class="grid">
          <label for="forma_pago">
            Forma de Pago
            <input type="text" id="forma_pago" name="forma_pago" placeholder="Efectivo, Tarjeta, etc." value="{{old('forma_pago', $expense->forma_pago)}}" required>
          </label>
      
          <label for="estado">
            Estado
            <input type="text" id="estado" name="estado" placeholder="Pendiente, Pagado, etc." value="{{old('estado', $expense->estado)}}" required>
          </label>
        </div>
      
        <!-- Markup example 2: input is after label -->
        <label for="comentario">Comentario</label>
        <textarea id="comentario" name="comentario" placeholder="Comentarios adicionales (opcional)" rows="3">{{old('comentario', $expense->comentario)}}</textarea>
        <small>Opcional.</small>
      
        <!-- Button -->
        <button type="submit">Actualizar</button>
      
    </form>
    <a href="{{ route('expenses.index') }}" role="button">Volver</a>

</div>
    
@endsection
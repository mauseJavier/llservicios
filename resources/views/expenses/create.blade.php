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
        <article style="background-color: #f8d7da; border-color: #f5c6cb; color: #721c24;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </article>
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
          <label for="forma_pago_id">
            Forma de Pago
            <select id="forma_pago_id" name="forma_pago_id" required>
              <option value="">Seleccionar forma de pago</option>
              @foreach($formasPago as $formaPago)
                <option value="{{ $formaPago->id }}" {{ old('forma_pago_id') == $formaPago->id ? 'selected' : '' }}>
                  {{ $formaPago->nombre }}
                </option>
              @endforeach
            </select>
          </label>
      
          <label for="estado">
            Estado
            <select id="estado" name="estado" required>
              <option value="">Seleccionar estado</option>
              <option value="impago" {{ old('estado') == 'impago' ? 'selected' : '' }}>Impago</option>
              <option value="pago" {{ old('estado') == 'pago' ? 'selected' : '' }}>Pago</option>
            </select>
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
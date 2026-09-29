@extends('principal.principal')

@section('body')


  <nav>
    <ul>
      <li><h1>Editar:</h1></li>
      <li>
        <h2>{{$usuario->name}}</h2>
      </li>
    </ul>
  </nav>

<div class="container">

  <form method="POST" action="{{route('UpdateUsuario')}}">
    @csrf
    <!-- Grid -->
    <div class="grid">

      <input type="hidden" name="id" value="{{$usuario->id}}">
  
      <!-- Markup example 1: input is inside label -->
      <label for="name">
        Nombre
        <input type="text" id="name" name="name" placeholder="Nombre" required value="{{$usuario->name}}">
      </label>
  
      <label for="dni">
        DNI
        <input type="text" id="dni" name="dni" placeholder="Dni" required value="{{$usuario->dni}}">
      </label>
  
    </div>
  
    <!-- Markup example 2: input is after label -->
    <label for="email">Correo</label>
    <input type="email" id="email" name="email" placeholder="correo" required value="{{$usuario->email}}"> 

    <!-- Select -->
    <label for="role_id">Rol</label>
    <select id="role_id" name="role_id" required>
      @foreach ($roles as $rol)
      @if ($usuario->role_id == $rol->id)
          <option value="{{$rol->id}}" selected>{{$rol->nombre}}</option>            
        @else
          <option value="{{$rol->id}}">{{$rol->nombre}}</option>
        @endif
      @endforeach      
    </select>

    <!-- Select -->
    <label for="empresa_id">Empresa</label>
    <select id="empresa_id" name="empresa_id" required>
      @foreach ($empresas as $emp)
      @if ($usuario->empresa_id == $emp->id)
          <option value="{{$emp->id}}" selected>{{$emp->nombre}}</option>            
        @else
          <option value="{{$emp->id}}">{{$emp->nombre}}</option>
        @endif
      @endforeach      
    </select>

    <label for="afip_punto_venta">Punto de venta AFIP</label>
    <div id="afip_punto_venta_select_wrapper">
      <select
        id="afip_punto_venta"
        name="afip_punto_venta"
        data-current="{{ $usuario->afip_punto_venta }}"
        data-url-base="{{ route('afip.puntos-venta.empresa', ['empresaId' => '__EMPRESA__'], false) }}"
      >
        <option value="">Seleccionar...</option>
        @foreach ($puntosVenta as $puntoVenta)
          @php
            $numero = is_array($puntoVenta)
              ? ($puntoVenta['Nro'] ?? $puntoVenta['nro'] ?? $puntoVenta['numero'] ?? $puntoVenta['id'] ?? $puntoVenta['number'] ?? null)
              : ($puntoVenta->Nro ?? $puntoVenta->nro ?? $puntoVenta->numero ?? $puntoVenta->id ?? $puntoVenta->number ?? null);
            $descripcion = is_array($puntoVenta)
              ? ($puntoVenta['Nombre'] ?? $puntoVenta['Desc'] ?? $puntoVenta['descripcion'] ?? $puntoVenta['name'] ?? null)
              : ($puntoVenta->Nombre ?? $puntoVenta->Desc ?? $puntoVenta->descripcion ?? $puntoVenta->name ?? null);
          @endphp
          @if ($numero !== null)
            <option value="{{ $numero }}" @selected($usuario->afip_punto_venta == $numero)>
              {{ $numero }}@if($descripcion) - {{ $descripcion }}@endif
            </option>
          @endif
        @endforeach
      </select>
    </div>
    <div id="afip_punto_venta_input_wrapper">
      <input
        type="number"
        id="afip_punto_venta_input"
        name="afip_punto_venta"
        min="1"
        placeholder="Ej: 1"
        value="{{ $usuario->afip_punto_venta }}"
      >
    </div>
    <small id="afip_punto_venta_error">{{ $puntosVentaError ?? '' }}</small>
  
    <!-- Button -->
    <button type="submit">Editar</button>
  
  </form>
  <a href="{{ url()->previous() }}" role="button">Volver</a>

</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const empresaSelect = document.getElementById('empresa_id');
    const puntosVentaSelect = document.getElementById('afip_punto_venta');
    const puntosVentaSelectWrapper = document.getElementById('afip_punto_venta_select_wrapper');
    const puntosVentaInputWrapper = document.getElementById('afip_punto_venta_input_wrapper');
    const puntosVentaInput = document.getElementById('afip_punto_venta_input');
    const puntosVentaError = document.getElementById('afip_punto_venta_error');
    const urlBase = puntosVentaSelect?.dataset?.urlBase;

    const setLoading = (isLoading) => {
      if (puntosVentaSelect) puntosVentaSelect.disabled = isLoading;
      if (puntosVentaInput) puntosVentaInput.disabled = isLoading;
    };

    const showSelect = () => {
      if (puntosVentaSelectWrapper) puntosVentaSelectWrapper.style.display = 'block';
      if (puntosVentaInputWrapper) puntosVentaInputWrapper.style.display = 'none';
      if (puntosVentaSelect) puntosVentaSelect.disabled = false;
      if (puntosVentaInput) puntosVentaInput.disabled = true;
    };

    const showInput = () => {
      if (puntosVentaSelectWrapper) puntosVentaSelectWrapper.style.display = 'none';
      if (puntosVentaInputWrapper) puntosVentaInputWrapper.style.display = 'block';
      if (puntosVentaSelect) puntosVentaSelect.disabled = true;
      if (puntosVentaInput) puntosVentaInput.disabled = false;
    };

    const normalizeNumero = (item) => {
      if (!item) return null;
      if (item.Nro !== undefined) return item.Nro;
      if (item.nro !== undefined) return item.nro;
      if (item.numero !== undefined) return item.numero;
      if (item.id !== undefined) return item.id;
      if (item.number !== undefined) return item.number;
      return null;
    };

    const normalizeDescripcion = (item) => {
      if (!item) return null;
      if (item.Nombre !== undefined) return item.Nombre;
      if (item.Desc !== undefined) return item.Desc;
      if (item.descripcion !== undefined) return item.descripcion;
      if (item.name !== undefined) return item.name;
      return null;
    };

    const populateSelect = (data) => {
      const current = puntosVentaSelect?.dataset?.current;
      if (!puntosVentaSelect) return;

      puntosVentaSelect.innerHTML = '<option value="">Seleccionar...</option>';
      data.forEach((item) => {
        const numero = normalizeNumero(item);
        if (numero === null || numero === undefined || numero === '') return;
        const descripcion = normalizeDescripcion(item);
        const option = document.createElement('option');
        option.value = numero;
        option.textContent = `${numero}${descripcion ? ' - ' + descripcion : ''}`;
        if (current !== undefined && current !== null && String(current) === String(numero)) {
          option.selected = true;
        }
        puntosVentaSelect.appendChild(option);
      });
    };

    const fetchPuntosVenta = async (empresaId) => {
      if (!urlBase || !empresaId) return;
      setLoading(true);
      if (puntosVentaError) puntosVentaError.textContent = '';

      try {
        const endpoint = urlBase.replace('__EMPRESA__', encodeURIComponent(empresaId));
        const response = await fetch(endpoint);
        const payload = await response.json();

        if (payload.success && Array.isArray(payload.data) && payload.data.length > 0) {
          populateSelect(payload.data);
          showSelect();
        } else {
          showInput();
          if (puntosVentaError) {
            puntosVentaError.textContent = payload.error || 'No se encontraron puntos de venta AFIP.';
          }
        }
      } catch (error) {
        showInput();
        if (puntosVentaError) {
          puntosVentaError.textContent = 'No se pudo obtener puntos de venta AFIP.';
        }
      } finally {
        setLoading(false);
      }
    };

    const initVisibility = () => {
      const hasOptions = puntosVentaSelect && puntosVentaSelect.options.length > 1;
      if (hasOptions) {
        showSelect();
      } else {
        showInput();
      }
    };

    initVisibility();

    if (empresaSelect) {
      empresaSelect.addEventListener('change', (event) => {
        const empresaId = event.target.value;
        fetchPuntosVenta(empresaId);
      });
    }
  });
</script>
    



@endsection
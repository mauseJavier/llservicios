@extends('principal.principal')

@section('body')


<div class="container">
<<<<<<< HEAD
  <h1>Servicios</h1>
=======
  <h1>Servicios a pagar</h1>
  <details viejo="margin-bottom: 24px;">
    <summary viejo="cursor: pointer; padding: 12px 16px; background: #8a929b; color: white; border-radius: 8px; margin-bottom: 16px; font-weight: 500; list-style: none;">
      Filtros
    </summary>
    <div viejo="padding: 16px; border: 1px solid #9ca3ab; border-radius: 8px; background: #8a929b;">
      <form method="GET" viejo="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; align-items: flex-end;">
        <div>
          <label for="fecha_desde">Desde</label>
          <input type="date" id="fecha_desde" name="fecha_desde" value="{{ request('fecha_desde', $fechaDesde ?? '') }}" viejo="width: 100%;">
        </div>
        <div>
          <label for="fecha_hasta">Hasta</label>
          <input type="date" id="fecha_hasta" name="fecha_hasta" value="{{ request('fecha_hasta', $fechaHasta ?? '') }}" viejo="width: 100%;">
        </div>
        <div>
          <label for="importe_min">Importe mínimo</label>
          <input type="number" step="0.01" id="importe_min" name="importe_min" value="{{ request('importe_min', $importeMin ?? '') }}" viejo="width: 100%;">
        </div>
        <div>
          <label for="importe_max">Importe máximo</label>
          <input type="number" step="0.01" id="importe_max" name="importe_max" value="{{ request('importe_max', $importeMax ?? '0') }}" viejo="width: 100%;">
        </div>
        <div>
          <label for="nombre_servicio">Servicio</label>
          <input type="text" id="nombre_servicio" name="nombre_servicio" value="{{ request('nombre_servicio', $nombreServicio ?? '') }}" placeholder="Nombre del servicio" viejo="width: 100%;">
        </div>
        <div>
          <label for="nombre_empresa">Empresa</label>
          <input type="text" id="nombre_empresa" name="nombre_empresa" value="{{ request('nombre_empresa', $nombreEmpresa ?? '') }}" placeholder="Nombre de la empresa" viejo="width: 100%;">
        </div>
        <div viejo="grid-column: 1 / -1; text-align: center;">
          <button type="submit" viejo="background: #28a745; color: white; border: none; border-radius: 8px; padding: 12px 24px; font-size: 16px; cursor: pointer;">Filtrar</button>
        </div>
      </form>
    </div>
  </details>
>>>>>>> 42e7409 (busquedas en vivo)
</div>

<div class="container">

  {{-- {
    "servicio_id": 17,
    "fechaCobro": "2023-12-27 15:31:00",
    "nombreServicio": "Servicio para javierD",
    "nombreEmpresa": "Empresa 1",
    "cantidadServicio": 1,
    "precioServicio": 657.85,
    "total": 657.85
  }, --}}

    <figure>
        <table>
            <thead>
              <tr>
                <th scope="col"></th>
                <th scope="col">Fecha</th>
                <th scope="col">Servicio</th>
                <th scope="col">Empresa</th>
                <th scope="col">Cantidad</th>
                <th scope="col">Precio</th>
                <th scope="col">Total</th>
                <th scope="col">Pagar</th>
              </tr>
            </thead>
            <tbody>

              @forelse ($serviciosImpagos as $s)
                <tr>
                  {{-- <th scope="row">{{$s->servicio_id}}</th> --}}
                  <th scope="row"><img width="30%" src="{{$s->imagenServicio}}" alt=""></th>
                  <td>{{$s->fechaCobro}}</td>
                  <td>{{$s->nombreServicio}}</td>
                  <td>{{$s->nombreEmpresa}}</td>
                  <td>{{$s->cantidadServicio}}</td>
                  <td>{{$s->precioServicio}}</td>
                  <td>{{$s->total}}</td>
                  <td><a href="https://{{$s->linkPago}}" role="button"><i class="fa-solid fa-dollar-sign"></i></a></td>
                </tr>
              @empty
                  <p>Sin Servicios Impagos</p>
              @endforelse



            </tfoot>
        </table>
    </figure>

</div>
    
@endsection
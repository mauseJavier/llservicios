@extends('principal.principal')

@section('body')


<div class="container">
  <h1>Servicios</h1>
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
                  <td><a href="{{$s->linkPago}}" role="button"><i class="fa-solid fa-dollar-sign"></i></a></td>
                </tr>
              @empty
                  <p>Sin Servicios Impagos</p>
              @endforelse



            </tfoot>
        </table>
    </figure>

</div>
    
@endsection
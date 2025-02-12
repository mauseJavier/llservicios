@extends('principal.principal')

@section('body')

<div class="container">

  <h1>Pagos</h1>

  <nav>
      <ul>

          <li>
            <form class="form" action="{{route('BuscarCliente')}}" method="GET">
                
                <div class="input-group">
                    <input type="search" class="input" id="buscar" name="buscar" 
                    @if (isset($buscar))
                        value="{{$buscar}}"
                    @endif  placeholder="Buscar...">
  
                </div>
            </form>
          </li>
      </ul>
      <ul>
          <li>
            <a href="{{route('Cliente.create')}}" role="button">Nuevo Cliente</a>
              {{-- <details role="list" dir="rtl">
                  <summary aria-haspopup="listbox" role="link" class="contrast">Acciones</summary>
                  <ul role="listbox">
                    <li><a href="{{route('empresas.create')}}">Nueva Empresa</a></li>
                    <li><a href="{{route('empresas.edit',['empresa'=>1])}}">editar</a></li>
                    <li><a href="{{route('empresas.show',['empresa'=>1])}}">borrar</a></li>
        
                  </ul>
                </details>  --}}
          </li>
      </ul>
  </nav>

</div>


<div class="container">

  <figure>
    <table>
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Nombre</th>
            <th scope="col">Dni</th>
            <th scope="col">Correo</th>
            <th scope="col">Empresa</th>
            <th scope="col">Domicilio</th>
            <th scope="col">Acciones</th>
          </tr>
        </thead>
        <tbody>
     
          @foreach ($pagos as $e)

  {{-- {
    "id": 1,
    "id_servicio_pagar": 8,
    "id_usuario": 1,
    "forma_pago": 3,
    "importe": 4081.17,
    "comentario": null,
    "created_at": "2024-01-21 18:44:17",
    "updated_at": "2024-01-21 18:44:17",
    "nombreUsuario": "DESMARET JAVIER NICOLAS",
    "Servicio": "Vito Daugherty",
    "Cliente": "Mr. Keenan O'Connell DDS",
    "idCliente": 1,
    "formaPago": "MercadoPago"
  }
] --}}
            <tr>              
              <td>{{$e->id}}</td>
              <td>{{$e->Servicio}}</td>
              <td>{{$e->Cliente}}</td>
              <td>{{$e->formaPago}}</td>
              <td>{{$e->nombreUsuario}}</td>
              <td>{{$e->importe}}</td>
              <th>                  
                  <strong><a href="{{route('PagosVer',['idServicioPagar'=>$e->idServicioPagar])}}" data-tooltip="Ver Pago">Ver</a></strong>
              </th>
            </tr>
          @endforeach
        
        </tfoot>
    </table>
</figure>





@if (method_exists($pagos, 'currentPage'))   

  {{-- //PAGINACION --}}
  <nav> 
    <ul>
      <li><strong>Pag. {{$pagos->currentPage()}} de: {{$pagos->lastPage()}} , Total Res.: {{$pagos->total()}}</strong></li>
    </ul>

    <ul>
      <li><a href=" {{$pagos->previousPageUrl()}}" role="button">Anterior</a></li>
          @if ($pagos->currentPage()-1 != 0)
            <li>
              <a href="{{$pagos->url($pagos->currentPage()-1)}}">{{$pagos->currentPage()-1}}</a> 
            </li>
          @endif
            <li>
              <strong>
                <a href="{{$pagos->url($pagos->currentPage())}}">{{$pagos->currentPage()}}</a>
              </strong>            
            </li>
          @if (($pagos->currentPage() +1 ) < round($pagos->total()/$pagos->perPage())+1)
            <li>
              <a href="{{$pagos->url($pagos->currentPage() +1)}}">{{$pagos->currentPage() +1}}</a>
            </li>
          @endif

      <li><a href="{{$pagos->nextPageUrl()}}" role="button">Siguiente</a></li>
    </ul>
  </nav>


@endif
</div>


@endsection 
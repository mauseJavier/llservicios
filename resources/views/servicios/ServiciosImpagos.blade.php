@extends('principal.principal')

@section('body')



<div class="container">
  <h1>Servicios Impagos</h1>

  {{-- <a href="{{route('ServiciosImpagos')}}" role="button" class="outline" style="font-size: 20px; padding: 4px 8px; ">Impagos</a>
  <a href="{{route('ServiciosPagos')}}" role="button" class="contrast outline" style="font-size: 20px; padding: 4px 8px; ">Pagos</a>
  <a href="{{route('Grilla')}}" role="button" class="contrast outline" style="font-size: 20px; padding: 4px 8px; ">Grilla</a> --}}
  
  <nav>
      <ul>
  
          <li>
            <form class="form" action="{{route('ServicioPagarBuscarCliente',['estado'=>'impago'])}}" method="GET">
                
                <div class="input-group">
                    <input type="search" class="input" id="buscar" name="buscar" 
                    @if (isset($buscar))
                        value="{{$buscar}}"
                    @endif  placeholder="Buscar...(Nombre,DNI,Correo)">
   
                </div>
            </form>
          </li>
      </ul>
      <ul>
        <li>
          <a href="{{route('NotificacionTodosServiciosImpagos')}}" role="button">Notif. Todos</a>
        </li>

      </ul>
  
  </nav>

</div>
<div class="container">

  <figure class="overflow-auto">
    <table>
        <thead>
          <tr>
            <th>id</th>
            <th scope="col">Cliente</th>
            <th scope="col">Servicio</th>  
            <th scope="col">Total</th>        
            <th scope="col">Estado</th>  
            <th scope="col">Fecha</th>
            <th scope="col">Acciones</th>
          </tr>
        </thead>
        <tbody>
     
          @foreach ($servicios as $e)
            <tr>   
              <td>{{$e->idServicioPagar}}</td>           
              <td>{{$e->nombreCliente}}({{$e->dniCliente}})</td>
              <td>{{$e->nombreServicio}}</td>
              <td>({{$e->cantidad}}U.)${{$e->total}}</td>
              <td>{{$e->estado}}</td>
              <td>{{$e->fechaCreacion}}</td>
              
              <th>                  
                  <strong><a href="{{route('PagarServicio',['idServicioPagar'=>$e->idServicioPagar,'importe'=>$e->total])}}" data-tooltip="Pagar">Pagar</a></strong> | 
                  <strong><a href="{{route('NotificacionNuevoServicio',['idServicioPagar'=>$e->idServicioPagar])}}"  data-tooltip="Enviar Notificacion">Enviar Notif.</a></strong>
                  
                  @if(Auth::user()->role_id == 2 || Auth::user()->role_id == 3)
                    | 
                    <strong>
                      <a href="#" 
                         onclick="event.preventDefault(); if(confirm('¿Está seguro que desea eliminar este servicio impago?\n\nCliente: {{$e->nombreCliente}}\nServicio: {{$e->nombreServicio}}\nTotal: ${{$e->total}}\n\nEsta acción no se puede deshacer.')) { document.getElementById('delete-form-{{$e->idServicioPagar}}').submit(); }" 
                         data-tooltip="Eliminar (Solo Admin/Super)"
                         style="color: #d32f2f;">
                        Eliminar
                      </a>
                    </strong>
                    <form id="delete-form-{{$e->idServicioPagar}}" 
                          action="{{route('EliminarServicioImpago', ['idServicioPagar' => $e->idServicioPagar])}}" 
                          method="POST" 
                          style="display: none;">
                      @csrf
                      @method('DELETE')
                    </form>
                  @endif
              </th>
            </tr>
          @endforeach
        
        </tfoot>
    </table>
  </figure>





@if (method_exists($servicios, 'currentPage'))   

  {{-- //PAGINACION --}}
  <nav> 
    <ul>
      <li><strong>Pag. {{$servicios->currentPage()}} de: {{$servicios->lastPage()}} , Total Res.: {{$servicios->total()}}</strong></li>
    </ul>

    <ul>
      <li><a href=" {{$servicios->previousPageUrl()}}" role="button">Anterior</a></li>
          @if ($servicios->currentPage()-1 != 0)
            <li>
              <a href="{{$servicios->url($servicios->currentPage()-1)}}">{{$servicios->currentPage()-1}}</a> 
            </li>
          @endif
            <li>
              <strong>
                <a href="{{$servicios->url($servicios->currentPage())}}">{{$servicios->currentPage()}}</a>
              </strong>            
            </li>
          @if (($servicios->currentPage() +1 ) < round($servicios->total()/$servicios->perPage())+1)
            <li>
              <a href="{{$servicios->url($servicios->currentPage() +1)}}">{{$servicios->currentPage() +1}}</a>
            </li>
          @endif

      <li><a href="{{$servicios->nextPageUrl()}}" role="button">Siguiente</a></li>
    </ul>
  </nav>


@endif
</div>


@endsection 
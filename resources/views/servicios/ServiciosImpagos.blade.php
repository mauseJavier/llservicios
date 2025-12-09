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

        @if(Auth::user()->role_id == 3)
          <li>
            <form method="POST" action="{{ route('EliminarTodosServiciosImpagos') }}" onsubmit="return confirm('¿Está seguro que desea eliminar TODOS los servicios impagos?\n\nEsta acción no se puede deshacer.');">
              @csrf
              <button type="submit" style="background: none; border: none; color: #d32f2f; cursor: pointer;" data-tooltip="Eliminar Todos (Solo Admin/Super)">
                Eliminar Todos
              </button>
            </form>
          </li>

          <li>
            <a href="{{ route('ContarServiciosImpagos') }}" role="button">Contar Impagos</a>
          </li>

        @endif

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
              
                <td style="white-space: nowrap;">                  
                  <strong><a role="button" href="{{route('PagarServicio',['idServicioPagar'=>$e->idServicioPagar,'importe'=>$e->total])}}" data-tooltip="Pagar">💵</a></strong> | 
                  <strong><a role="button" href="{{route('NotificacionNuevoServicio',['idServicioPagar'=>$e->idServicioPagar])}}"  data-tooltip="Enviar Notificacion">
                          <svg width="24" height="24" fill="#25D366" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                  </a></strong>
                  
                  @if(Auth::user()->role_id == 2 || Auth::user()->role_id == 3)
                  | 
                  <strong>
                  <a role="button" href="#" 
                   onclick="event.preventDefault(); if(confirm('¿Está seguro que desea eliminar este servicio impago?\n\nCliente: {{$e->nombreCliente}}\nServicio: {{$e->nombreServicio}}\nTotal: ${{$e->total}}\n\nEsta acción no se puede deshacer.')) { document.getElementById('delete-form-{{$e->idServicioPagar}}').submit(); }" 
                   data-tooltip="Eliminar (Solo Admin/Super)"
                   style="color: #d32f2f;">
                  🗑️
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
                </td>
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
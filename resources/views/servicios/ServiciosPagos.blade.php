@extends('principal.principal')

@section('body')


<h1>Servicios Pagos</h1>

<a href="{{route('ServiciosImpagos')}}" role="button" class="contrast outline" style="font-size: 20px; padding: 4px 8px; ">Impagos</a>
<a href="{{route('ServiciosPagos')}}" role="button" class="outline" style="font-size: 20px; padding: 4px 8px; ">Pagos</a>

<nav>
    <ul>

        <li>
          <form class="form" action="{{route('ServicioPagarBuscarCliente',['estado'=>'pago'])}}" method="GET">
              

            <li>
              <label for="fecha">Desde:</label>
              <input type="date" id="fechaDesde" name="fechaDesde" value="{{$fechaDesde}}">
            </li>
            <li>
              <label for="fecha">Hasta:</label>
              <input type="date" id="fechaHasta" name="fechaHasta" value="{{$fechaHasta}}">
            </li>
            <table >
              <tr>
                <td>
              
                  <div class="input-group">
                    <input type="search" class="input" id="buscar" name="buscar" 
                    @if (isset($buscar))
                        value="{{$buscar}}"
                    @endif  placeholder="Buscar...(Nombre,DNI,Correo,Serv.)">                    
                  </div>
              
                </td >
                <td >
                  <button type="submit" style="border-radius: 20px; padding: 10px 20px; font-size: 16px; ">Buscar</button>
                </td>
              </tr>
            </table>
          </form>
        </li>
        
    </ul>

</nav>
<div class="container">

  <figure>
    <table>
        <thead>
          <tr>
            <th>id</th>
            <th scope="col">Cliente</th>
            <th scope="col">Servicio</th>  
            <th scope="col">Precio</th>        
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
              <td>${{$e->precio}}</td>
              <td>{{$e->estado}}</td>
              <td>{{$e->fechaCreacion}}</td>
              
              <th>              
                <a href="">acciones futuras </a>    
                  {{-- <strong><a href="{{route('Servicios.edit',['Servicio'=>$e->idServicioPagar])}}" data-tooltip="Editar">Pagar</a></strong> | 
                  <strong><a href="{{route('ServiciosAgregarCliente',['Servicio'=>$e->idServicioPagar])}}"  data-tooltip="Agregar Cliente">Enviar Notif.</a></strong> --}}
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
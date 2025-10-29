@extends('principal.principal')

@section('body')



<div class="container">
  <h1>Servicios</h1>


          <form action="{{route('BuscarServicio')}}" method="GET">
              
            <fieldset role="group">

                <a href="{{route('Servicios.create')}}" role="button">Nuevo</a>

                <input id="buscar" name="buscar"  
                  @if (isset($buscar))
                      value="{{$buscar}}"
                  @endif  placeholder="Buscar..." />
                

              
            </fieldset>

          </form>





<div class="container">

  <figure>
    
    <div class="overflow-auto">

      <table>
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Nombre</th>
              <th scope="col">Precio</th>
              <th scope="col">Precio 2</th>
              <th scope="col">Precio 3</th>
              <th scope="col">Días Venc.</th>
              <th scope="col">Descripcion</th>        
              <th scope="col">Tiempo</th>  
              
              
              <th scope="col">Empresa</th>
              <th scope="col">Imagen</th>    
              <th scope="col">Link Pago</th>
              <th scope="col">Editar</th>
              <th scope="col">Agregar</th>
            </tr>
          </thead>
          <tbody>
       
            @foreach ($servicios as $e)
              <tr>              
                <td>{{$e->id}}</td>
                <td>{{$e->nombre}}</td>
                <td>${{$e->precio}}</td>
                <td>{{ $e->precio2 ? '$' . $e->precio2 : '-' }}</td>
                <td>{{ $e->precio3 ? '$' . $e->precio3 : '-' }}</td>
                <td>{{$e->diasVencimiento ?? 10}}</td>
                <td>{{$e->descripcion}}</td>
                <td>{{$e->tiempo}}</td>
    
    
                
                <td>{{$e->empresa->nombre .' '.$e->empresa->id}}</td>
                <td><img src="{{$e->imagen}}" alt=""></td>
                <td>
                  <a href="https://{{$e->linkPago}}" role="button">
                
                    <i class="fa-solid fa-dollar-sign"></i>
                  
                  </a>
                </td>
                <th>                
                      <a  href="{{route('Servicios.edit',['Servicio'=>$e->id])}}" data-tooltip="Editar" role="button">
                    
                        <i class="fa-regular fa-pen-to-square"></i>
                    
                      </a>
                </th>
                <td>
                  <a  href="{{route('ServiciosAgregarCliente',['Servicio'=>$e->id])}}"  data-tooltip="Agregar Cliente" role="button">
                    
                    <i class="fa-solid fa-user-plus"></i>
                
                  </a>
                </td>
              </tr>
            @endforeach
          
          </tfoot>
      </table>

  </div>

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
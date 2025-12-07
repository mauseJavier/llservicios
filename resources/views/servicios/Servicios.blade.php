@extends('principal.principal')

@section('body')



<div class="container">
  
  {{-- Mensajes de éxito y error --}}
  @if(session('status'))
    <div style="padding: 1rem; margin-bottom: 1rem; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px;">
      {{ session('status') }}
    </div>
  @endif
  
  @if(session('error'))
    <div style="padding: 1rem; margin-bottom: 1rem; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px;">
      {{ session('error') }}
    </div>
  @endif


  <h1>Servicios</h1>


  
                <div class="container">
                    
                    
                    <form action="{{route('BuscarServicio')}}" method="GET" >
                      <input id="buscar" name="buscar"  type="search"
                      @if (isset($buscar))
                      value="{{$buscar}}"
                      @endif  placeholder="Buscar..." />
                    </form>        
                    
                
              </div>
              
                <article style="display: flex; justify-content: flex-end; align-items: center; gap: 1rem;">

                <a href="{{route('Servicios.create')}}" role="button" style="flex: 0 0 10%; max-width: 10%;">
              
                  <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-square-plus"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 12h6" /><path d="M12 9v6" /><path d="M3 5a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-14z" /></svg>
              
                </a>

                <form action="{{route('Servicios.index')}}" method="get">
              
                  <div class="grid">
                  <label style="color:red">
                  <input type="checkbox" name="inactivos" value="1" 
                  @if(isset($mostrarInactivos) && $mostrarInactivos) checked @endif
                  onchange="this.form.submit()">
                  Mostrar inactivos
                  </label>
              
                  </div>
              
              
                </form>

                </article>
                
              





<div class="container">

  <figure>
    
    <div class="overflow-auto">

      <table>
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Estado</th>
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
              <th scope="col">Activar/Desactivar</th>
            </tr>
          </thead>
          <tbody>
       
            @foreach ($servicios as $e)
              <tr class="{{ !$e->activo ? 'opacity-50' : '' }}" style="{{ !$e->activo ? 'background-color: #f0f0f0;' : '' }}">              
                <td>{{$e->id}}</td>
                <td>
                  @if($e->activo)
                    <span style="color: green; font-weight: bold;">● Activo</span>
                  @else
                    <span style="color: red; font-weight: bold;">● Inactivo</span>
                  @endif
                </td>
                <td>{{$e->nombre}}</td>
                <td>${{$e->precio}}</td>
                <td>{{ $e->precio2 ? '$' . $e->precio2 : '-' }}</td>
                <td>{{ $e->precio3 ? '$' . $e->precio3 : '-' }}</td>
                <td>{{$e->diasVencimiento ?? 10}}</td>
                <td>{{$e->descripcion}}</td>
                <td>{{$e->tiempo}}</td>
    
    
                
                <td>{{$e->empresa->nombre .'('.$e->empresa->id.')'}}</td>
                <td><img src="{{$e->imagen}}" alt=""></td>
                <td>
                  <a href="https://{{$e->linkPago}}" role="button">
                
                    <i class="fa-solid fa-dollar-sign"></i>
                  
                  </a>
                </td>

                  @if ($e->activo)
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

                  @else
                    <th>--</th>
                    <td>--</td>
                  @endif
                    <td>
                      <form action="{{route('Servicios.toggleEstado', ['Servicio' => $e->id])}}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" 
                          data-tooltip="{{ $e->activo ? 'Desactivar servicio' : 'Activar servicio' }}"
                          style="background-color: {{ $e->activo ? '#dc3545' : '#28a745' }}; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer;">
                          @if($e->activo)
                            <i class="fa-solid fa-ban"></i> Desactivar
                          @else
                            <i class="fa-solid fa-check-circle"></i> Activar
                          @endif
                        </button>
                      </form>
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
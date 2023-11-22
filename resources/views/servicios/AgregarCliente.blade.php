@extends('principal.principal')

@section('body')

{{-- "id": 150,
"nombre": "Prof. Karson Strosin Sr.",
"descripcion": "Destany Stoltenberg Una descripcion de prueba",
"precio": 6686.77, --}}

<h1>Servicio: {{$servicio->nombre}}</h1>

{{-- {{($request->Buscar)}} --}}

<nav>
    <ul>

        <li>
          <form class="form" action="{{route('ServiciosAgregarCliente', ['Servicio' => $servicio->id])}}" method="GET">
              
              <div class="input-group">
                  <input type="search" class="input" id="Buscar" name="Buscar" 
                  @if (isset($buscar))
                      value="{{$buscar}}"
                  @endif  placeholder="Buscar...">
 
              </div>
          </form>
        </li>
    </ul>
    <ul>
        <li>
         

        </li>
    </ul>
</nav>
<div class="container">

  <figure>
    <table>
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Nombre</th>
            <th scope="col">Correo</th>  
            <th scope="col">Dni</th>        
            <th scope="col">Acciones</th>
          </tr>
        </thead>
        <tbody>
     
          @foreach ($clientes as $e)

            <tr>              
              <td>{{$e->id}}</td>
              <td>{{$e->nombre}}</td>
              <td>{{$e->correo}}</td>
              <td>{{$e->dni}}</td>

              <th>                  
                  <strong><a href="{{route('agregarClienteAServicio',['Servicio'=>$servicio->id,'Cliente'=>$e->id])}}" 
                          onclick="return confirm('¿Estás seguro de que quieres agregar este Cliente?')" data-tooltip="Agregar Cliente">Agregar-Cliente-A-Servicio</a></strong>
              </th>
            </tr>
          @endforeach
        
        </tfoot>
    </table>
</figure>





@if (method_exists($clientes, 'currentPage'))   

  {{-- //PAGINACION --}}
  <nav> 
    <ul>
      <li><strong>Pag. {{$clientes->currentPage()}} de: {{$clientes->lastPage()}} , Total Res.: {{$clientes->total()}}</strong></li>
    </ul>

    <ul>
      <li><a href=" {{$clientes->previousPageUrl()}}" role="button">Anterior</a></li>
          @if ($clientes->currentPage()-1 != 0)
            <li>
              <a href="{{$clientes->url($clientes->currentPage()-1)}}">{{$clientes->currentPage()-1}}</a> 
            </li>
          @endif
            <li>
              <strong>
                <a href="{{$clientes->url($clientes->currentPage())}}">{{$clientes->currentPage()}}</a>
              </strong>            
            </li>
          @if (($clientes->currentPage() +1 ) < round($clientes->total()/$clientes->perPage())+1)
            <li>
              <a href="{{$clientes->url($clientes->currentPage() +1)}}">{{$clientes->currentPage() +1}}</a>
            </li>
          @endif

      <li><a href="{{$clientes->nextPageUrl()}}" role="button">Siguiente</a></li>
    </ul>
  </nav>


@endif

<h1>Clientes de este Servicio</h1>

<figure>
  <table>
      <thead>
        <tr>
          <th scope="col">#</th>
          <th scope="col">Nombre</th>
          <th scope="col">Correo</th>  
          <th scope="col">Dni</th>        
          <th scope="col">Acciones</th>
        </tr>
      </thead>
      <tbody>
   
        @foreach ($clientesMiembro as $e)
          <tr>              
            <td>{{$e->id}}</td>
            <td>{{$e->nombre}}</td>
            <td>{{$e->correo}}</td>
            <td>{{$e->dni}}</td>

            <th>                  
              <strong><a href="{{route('quitarClienteAServicio',['Servicio'=>$servicio->id,'Cliente'=>$e->id])}}" onclick="return confirm('¿Estás seguro de que quieres quitar este Cliente?')" data-tooltip="Quitar Cliente">Quitar-Cliente-A-Servicio</a></strong>
 
            </th>
          </tr>
        @endforeach
      
      </tfoot>
  </table>
</figure>
</div>


@endsection 
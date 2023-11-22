@extends('principal.principal')

@section('body')

<nav>
    <ul>
        <li>
            <h1>Usuarios Em.</h1>
        </li>            
        <li>
          <h3><strong>{{$empresa->nombre}}</strong></h3>          
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
            
            <th scope="col">Acciones</th>
          </tr>
        </thead>
        <tbody>
     
          @foreach ($empresa->users as $e)
            <tr>              
              <td>{{$e->id}}</td>
              <td>{{$e->name}}</td>
              
              <th>
                  
                  <strong><a href="#" data-tooltip="Ver">Ver</a></strong>
              </th>
            </tr>
          @endforeach
        
        </tfoot>
    </table>
</figure>
<a href="{{ url()->previous() }}" role="button">Volver</a>


@if (method_exists($empresa, 'currentPage'))   

  {{-- //PAGINACION --}}
  <nav> 
    <ul>
      <li><strong>Pag. {{$empresa->currentPage()}} de: {{$empresa->lastPage()}} , Total Res.: {{$empresa->total()}}</strong></li>
    </ul>

    <ul>
      <li><a href=" {{$empresa->previousPageUrl()}}" role="button">Anterior</a></li>
          @if ($empresa->currentPage()-1 != 0)
            <li>
              <a href="{{$empresa->url($empresa->currentPage()-1)}}">{{$empresa->currentPage()-1}}</a> 
            </li>
          @endif
            <li>
              <strong>
                <a href="{{$empresa->url($empresa->currentPage())}}">{{$empresa->currentPage()}}</a>
              </strong>            
            </li>
          @if (($empresa->currentPage() +1 ) < round($empresa->total()/$empresa->perPage())+1)
            <li>
              <a href="{{$empresa->url($empresa->currentPage() +1)}}">{{$empresa->currentPage() +1}}</a>
            </li>
          @endif

      <li><a href="{{$empresa->nextPageUrl()}}" role="button">Siguiente</a></li>
    </ul>
  </nav>


@endif

</div>
    
@endsection
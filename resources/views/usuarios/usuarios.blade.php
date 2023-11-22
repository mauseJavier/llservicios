@extends('principal.principal')

@section('body')

<h1>Usuarios</h1>
  <nav>
    <ul>

      <li>
        <form class="form" action="{{route('BuscarUsuario')}}" method="GET">
            {{-- @csrf --}}
            <div class="input-group">
                <input type="search" class="input" id="buscar" name="buscar"  placeholder="Buscar..." value="{{old('buscar')}}">

            </div>
        </form>
      </li>
    </ul>
  </nav>

<div class="container">

  <figure>
    <table>
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Email</th>
            <th scope="col">Nombre</th>
            <th scope="col">Dni</th>
            <th scope="col">Rol</th>
            <th scope="col">Empresa</th>
            <th scope="col">Acciones</th>
          </tr>
        </thead>
        <tbody>
          
          @foreach ($usuarios as $u)
            <tr>              
              <td>{{$u->id}}</td>
              <td>{{$u->email}}</td>
              <td>{{$u->name}}</td>
              <td>{{$u->dni}}</td>
              <td>{{$u->role->nombre}}</td>
              <td>{{$u->empresa->nombre}}</td>
              <th>
                <a href="{{route('EditarUsuario',['id'=>$u->id])}}" data-tooltip="Editar">Editar</a></strong>
              </th>
            </tr>
          @endforeach
        
        </tfoot>
    </table>
  </figure>

  @if (method_exists($usuarios, 'currentPage'))   

   {{-- //PAGINACION --}}
   <nav> 
    <ul>
      <li><strong>Pag. {{$usuarios->currentPage()}} de: {{$usuarios->lastPage()}}, Total Res.: {{$usuarios->total()}}</strong></li>
    </ul>

    <ul>
      <li><a href=" {{$usuarios->previousPageUrl()}}" role="button">Anterior</a></li>
          @if ($usuarios->currentPage()-1 != 0)
            <li>
              <a href="{{$usuarios->url($usuarios->currentPage()-1)}}">{{$usuarios->currentPage()-1}}</a> 
            </li>
          @endif
            <li>
              <strong>
                <a href="{{$usuarios->url($usuarios->currentPage())}}">{{$usuarios->currentPage()}}</a>
              </strong>            
            </li>
          @if (($usuarios->currentPage() +1 ) < round($usuarios->total()/$usuarios->perPage())+1)
            <li>
              <a href="{{$usuarios->url($usuarios->currentPage() +1)}}">{{$usuarios->currentPage() +1}}</a>
            </li>
          @endif

      <li><a href="{{$usuarios->nextPageUrl()}}" role="button">Siguiente</a></li>
    </ul>
  </nav>

@endif


 
{{-- 
  {{ round($usuarios->total()/$usuarios->perPage())}}
  <br>
  {{($usuarios->currentPage() +1 )}}
  <br>
  {{$usuarios->total()}}
  <br>
  {{$usuarios->perPage()}}
  <br>
 --}}



</div>
    

@endsection
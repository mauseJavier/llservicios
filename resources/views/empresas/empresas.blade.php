@extends('principal.principal')

@section('body')

<div class="container">

  <h1>Empresas</h1>
  <nav>
      <ul>
          <li>
            <form class="form" action="{{route('BuscarEmpresa')}}" method="GET">              
                <div class="input-group">
                    <input type="search" class="input" id="buscar" name="buscar"  placeholder="Buscar...">
            
                </div>
            </form>
          </li>
      </ul>
      <ul>
          <li>
            <a href="{{route('empresas.create')}}" role="button">Nueva Empresa</a>
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
            <th scope="col">Empresa</th>
            <th scope="col">Cuit</th>
            <th scope="col">Correo</th>
            <th scope="col">Acciones</th>
          </tr>
        </thead>
        <tbody>
     
          @foreach ($empresas as $e)
            <tr>              
              <td>{{$e->id}}</td>
              <td>{{$e->nombre}}</td>
              <td>{{$e->cuit}}</td>
              <td>{{$e->correo}}</td>
              <th>
                  <strong><a href="{{route('UsuariosEmpresasVer',['idEmpresa'=>$e->id])}}" data-tooltip="Editar">Usuarios</a></strong> |
                  <strong><a href="{{route('empresas.edit',['empresa'=>$e->id])}}" data-tooltip="Editar">Editar</a></strong> |
                  <strong><a href="#" data-tooltip="Borrar">Borrar</a></strong>
              </th>
            </tr>
          @endforeach
        
        </tfoot>
    </table>
</figure>


@if (method_exists($empresas, 'currentPage'))   

  {{-- //PAGINACION --}}
  <nav> 
    <ul>
      <li><strong>Pag. {{$empresas->currentPage()}} de: {{$empresas->lastPage()}} , Total Res.: {{$empresas->total()}}</strong></li>
    </ul>

    <ul>
      <li><a href=" {{$empresas->previousPageUrl()}}" role="button">Anterior</a></li>
          @if ($empresas->currentPage()-1 != 0)
            <li>
              <a href="{{$empresas->url($empresas->currentPage()-1)}}">{{$empresas->currentPage()-1}}</a> 
            </li>
          @endif
            <li>
              <strong>
                <a href="{{$empresas->url($empresas->currentPage())}}">{{$empresas->currentPage()}}</a>
              </strong>            
            </li>
          @if (($empresas->currentPage() +1 ) < round($empresas->total()/$empresas->perPage())+1)
            <li>
              <a href="{{$empresas->url($empresas->currentPage() +1)}}">{{$empresas->currentPage() +1}}</a>
            </li>
          @endif

      <li><a href="{{$empresas->nextPageUrl()}}" role="button">Siguiente</a></li>
    </ul>
  </nav>


@endif
   


</div>
   
@endsection 
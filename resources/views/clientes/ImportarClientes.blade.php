@extends('principal.principal')

@section('body')

<div class="container">

  <h1>Importar Clientes Excel CSV</h1>

<nav>
    <ul>

        <li>
          {{-- <form class="form" action="{{route('BuscarCliente')}}" method="GET">
              
              <div class="input-group">
                  <input type="search" class="input" id="buscar" name="buscar" 
                  @if (isset($buscar))
                      value="{{$buscar}}"
                  @endif  placeholder="Buscar...">
 
              </div>
          </form> --}}
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

    <form action="{{route('ImportarClientes')}}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('POST')

        <div class="gird">
            <input type="file" name="archivo_CSV" id="">
            <button type="submit">Cargar</button>
        </div>

    </form>

</div>





@endsection 
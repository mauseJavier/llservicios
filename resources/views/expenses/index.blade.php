@extends('principal.principal')

@section('body')

<div class="container">

  <h1>Gastos</h1>

<nav>
    <ul>
        <li>
          <form class="form" action="{{route('expenses.index')}}" method="GET">
              <div class="input-group">
                  <input type="search" class="input" id="buscar" name="buscar" 
                  @if (isset($buscar))
                      value="{{$buscar}}"
                  @endif  placeholder="Buscar...">
              </div>
          </form>
        </li>
    </ul>
    <ul>
        <li>
            <a href="{{route('expenses.create')}}" role="button" data-tooltip="Nuevo Gasto"><i class="fa-regular fa-square-plus"></i></a>
        </li>
    </ul>
</nav>

</div>

<div class="container">

  @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
  @endif

  <figure>
    <table>
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Detalle</th>
            <th scope="col">Forma de Pago</th>
            <th scope="col">Estado</th>
            <th scope="col">Importe</th>
            <th scope="col">Comentario</th>
            <th scope="col">Acciones</th>
          </tr>
        </thead>
        <tbody>
     
          @foreach ($expenses as $expense)
            <tr>              
              <td>{{$expense->id}}</td>
              <td>{{$expense->detalle}}</td>
              <td>{{$expense->forma_pago}}</td>
              <td>{{$expense->estado}}</td>
              <td>${{number_format($expense->importe, 2)}}</td>
              <td>{{Str::limit($expense->comentario, 50)}}</td>
              <th>                  
                  <strong><a href="{{route('expenses.edit',['expense'=>$expense->id])}}" data-tooltip="Editar">Editar</a></strong>
                  <form method="POST" action="{{route('expenses.destroy', ['expense'=>$expense->id])}}" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" onclick="return confirm('¿Está seguro de que desea eliminar este gasto?')" style="background: none; border: none; color: red; cursor: pointer;">Eliminar</button>
                  </form>
              </th>
            </tr>
          @endforeach
        
        </tbody>
    </table>
</figure>

@if (method_exists($expenses, 'currentPage'))   
  {{-- //PAGINACION --}}
  <nav> 
    <ul>
      <li><strong>Pág. {{$expenses->currentPage()}} de: {{$expenses->lastPage()}} , Total Res.: {{$expenses->total()}}</strong></li>
    </ul>

    <ul>
      <li><a href=" {{$expenses->previousPageUrl()}}" role="button">Anterior</a></li>
          @if ($expenses->currentPage()-1 != 0)
            <li>
              <a href="{{$expenses->url($expenses->currentPage()-1)}}">{{$expenses->currentPage()-1}}</a> 
            </li>
          @endif
          
          <li>
            <a href="#">{{$expenses->currentPage()}}</a>
          </li>

          @if ($expenses->currentPage()+1 <= $expenses->lastPage())
            <li>
              <a href="{{$expenses->url($expenses->currentPage()+1)}}">{{$expenses->currentPage()+1}}</a> 
            </li>
          @endif

      <li><a href="{{$expenses->nextPageUrl()}}" role="button">Siguiente</a></li>
    </ul>
  </nav>
@endif

</div>

@endsection
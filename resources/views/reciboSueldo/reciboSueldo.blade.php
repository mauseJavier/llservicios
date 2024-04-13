@extends('principal.principal')

@section('body')


@if (Session::has('mensaje'))
   <div class="alert alert-info">{{ Session::get('mensaje') }}</div>
@endif

<div class="container">
  <h1>Recibos de Sueldo</h1>

<nav>
    <ul>

        <li>
          <form class="form" action="" method="GET">
              
              <div class="input-group">
                <input type="month" id="start" name="start" min="2018-03" value="2018-05" />
 
              </div>
          </form>
        </li>

    </ul>
    @if (Auth::User()->role_id == 3 || Auth::User()->role_id == 2)
      <ul>
        <li>
          <a role="button" href="{{route('subirRecibos')}}">Subir Recibos</a>
        </li>
      </ul>
    @else


        
    @endif
</nav>

</div>
<div class="container">

  <figure>
    <table>
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Periodo</th>
            <th scope="col">Empleador</th>  
            <th scope="col">creado</th>        
            <th scope="col">Acciones</th>    
            
          </tr>
        </thead>
        <tbody>
     
          @foreach ($recibos as $e)
            <tr>   
                <td>{{$e->id}}</td>           
              <td>{{$e->periodo}}</td>
              <td>{{$e->empleador}}</td>
              <td>{{$e->created_at}}</td>
              <td><a href="{{route('imprimirRecibo', ['idRecibo' => $e->id])}}">Imprimir</a></td>
            </tr>
          @endforeach
        
        </tfoot>
    </table>
</figure>





</div>


@endsection 
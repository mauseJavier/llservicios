@extends('principal.principal')

@section('body')




<div class="container">

    @if (Session::has('mensaje'))
      <article>
        <div >{{ Session::get('mensaje') }}</div>
      </article>
    @endif

  <h1>Recibos de Sueldo</h1>

<nav>
    <ul>

        {{-- <li>
          <form class="form" action="{{route('reciboSueldo')}}" method="POST" style="display:inline;">
              @csrf
              @method('POST')

              <div class="input-group">
                <input type="month" id="start" name="fecha" value="{{date('Y-m')}}" />
 
              </div>

              <button type="submit" style="display:inline;">Buscar</button>
          </form>
        </li> --}}

        <li>
          <form class="form" action="{{route('reciboSueldo')}}" method="POST">
            @csrf
            @method('POST')
            <fieldset class="grid">
              <input type="month" id="start" name="fecha" value="{{date('Y-m',strtotime( $fechaFiltro))}}" width="200" />

              <input
                type="submit"
                value="Buscar"
              />
            </fieldset>
          </form>
        </li>


    </ul>
     <ul>
        <li>
          @if (Auth::User()->role_id == 3 || Auth::User()->role_id == 2)      
                <a role="button" href="{{route('subirRecibos')}}">Subir Recibos</a>
          @else        
          @endif
        </li>
        <li>
          <a role="button" href="{{route('reciboSueldo')}}">Todo</a>
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
              <td>{{date("m-Y", strtotime($e['periodo']))}}</td>
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
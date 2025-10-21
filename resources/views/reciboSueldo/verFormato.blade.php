@extends('principal.principal')

@section('body')




<div class="container">

    @if (Session::has('mensaje'))
      <article>
        <div >{{ Session::get('mensaje') }}</div>
      </article>
    @endif

  <h1>Formato Recibos de Sueldo</h1>

  {{-- {
    "id": 1,
    "tipo": "ingresos",
    "codigo": "codigo_basico",
    "descripcion": "Basico",
    "cantidad": "cantidad_basico",
    "importe": "monto_basico",
    "empresa_id": 1
  }, --}}

  <form action="{{route('formatoRegistroSerch')}}" method="POST">
    @method('POST')
    @csrf

    <div class="grid">
      <div>    <input
        type="search"
        name="busqueda"
        placeholder="Buscar"
        aria-label="Buscar"
      /></div>
      <div><button type="submit">Buscar</button></div>

    </div>
    
  </form>
    
      <figure>

        <table>
          <thead>
            <tr>
              <th>Editar</th>
              <th scope="col">Tipo</th>
              <th scope="col">Codigo</th>
              <th scope="col">Descripcion</th>
              <th scope="col">Cantidad</th>
              <th scope="col">Importe</th>
            </tr>
          </thead>
          <tbody>

              @foreach ($filas as $item)

              <tr>
                <td><a href="{{route('formatoRegistroUpdate',['id'=>$item->id])}}" role="button">Editar</a> </td>
                  <th scope="row">{{$item->tipo}}</th>
                  <td>{{$item->codigo}}</td>
                  <td>{{$item->descripcion}}</td>
                  <td>{{$item->cantidad}}</td>
                  <td>{{$item->importe}}</td>

                </tr>
                  
              @endforeach

            
          </tbody>
          <tfoot>
          
          </tfoot>
        </table>

      </figure>


</div>



@endsection 
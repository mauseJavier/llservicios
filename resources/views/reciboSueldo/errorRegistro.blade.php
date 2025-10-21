@extends('principal.principal')

@section('body')




<div class="container">

    @if (Session::has('mensaje'))
      <article>
        <div >{{ Session::get('mensaje') }}</div>
      </article>
    @endif

  <h1>ERRORES Formato Recibos de Sueldo</h1>

  {{-- {
    "id": 1,
    "tipo": "ingresos",
    "codigo": "codigo_basico",
    "descripcion": "Basico",
    "cantidad": "cantidad_basico",
    "importe": "monto_basico",
    "empresa_id": 1
  }, --}}
    
      <figure>

        <table>
          <thead>
            <tr>
              <th scope="col">Estado</th>
              <th scope="col">Columna</th>

            </tr>
          </thead>
          <tbody>

            <tr>
                <td colspan="2" style="text-align: center;">TOTAL MAL  {{count($control['mal'])}}</td>
            </tr>

              @foreach ($control['mal'] as $item)

              <tr>
                  <th scope="row">MAL</th>
                  <td>{{$item['columna']}}</td>


                </tr>
                  
              @endforeach


            <tr>
                <td colspan="2" style="text-align: center;">TOTAL BIEN  {{count($control['ok'])}}</td>
            </tr>


              @foreach ($control['ok'] as $item)

              <tr>
                  <th scope="row">BIEN</th>
                  <td>{{$item['columna']}}</td>


                </tr>
                  
              @endforeach

            
          </tbody>
          <tfoot>
          
          </tfoot>
        </table>

      </figure>


</div>



@endsection 
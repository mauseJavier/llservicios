@extends('principal.principal')

@section('body')

<div class="container">

  <h1>Clientes</h1>
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
  
</div>

<div class="container-fluid">

  <figure>
    <table>
        <thead>
          <tr>
            <th scope="col">Cliente</th>
            <th scope="col">Enero</th>
            <th scope="col">Febrero</th>
            <th scope="col">Marzo</th>
            <th scope="col">Abril</th>
            <th scope="col">Mayo</th>
            <th scope="col">Junio</th>
            <th scope="col">Julio</th>
            <th scope="col">Agosto</th>
            <th scope="col">Septiembre</th>
            <th scope="col">Octubre</th>
            <th scope="col">Noviembre</th>
            <th scope="col">Diciembre</th>
          </tr>
        </thead>
        <tbody>
          
          {{-- "id": 1,
          "nombre": "Mr. Keenan O'Connell DDS",
          "correo": "miCorreo",
          "dni": 11,
          "domicilio": null,
          "created_at": "2024-01-18 19:03:44",
          "updated_at": "2024-01-18 19:03:44",
          "datos": [
            {
              "mes_creado": "January",
              "suma_precios": 25820.009999999995,
              "estado_pago": "impago"
            },
            {
              "mes_creado": "February",
              "suma_precios": 0,
              "estado_pago": "impago"
            },
            {
              "mes_creado": "March",
              "suma_precios": 0,
              "estado_pago": "impago"
            },
            {
              "mes_creado": "April",
              "suma_precios": 0,
              "estado_pago": "impago"
            },
            {
              "mes_creado": "May",
              "suma_precios": 0,
              "estado_pago": "impago"
            },
            {
              "mes_creado": "June",
              "suma_precios": 0,
              "estado_pago": "impago"
            },
            {
              "mes_creado": "July",
              "suma_precios": 0,
              "estado_pago": "impago"
            },
            {
              "mes_creado": "August",
              "suma_precios": 0,
              "estado_pago": "impago"
            },
            {
              "mes_creado": "September",
              "suma_precios": 0,
              "estado_pago": "impago"
            },
            {
              "mes_creado": "October",
              "suma_precios": 0,
              "estado_pago": "impago"
            },
            {
              "mes_creado": "November",
              "suma_precios": 0,
              "estado_pago": "impago"
            },
            {
              "mes_creado": "December",
              "suma_precios": 0,
              "estado_pago": "impago"
            }
          ]
        }, --}}

          @foreach ($clientes as $c)
            <tr>              

              <td>{{$c->nombre}}</td>
             

              @foreach ($c->datos as $item)    
              
              @if ($item['estado_pago'] == 'pago')
              <td class="pico-color-jade-500">{{$item ['suma_precios']}}</td>
              @else
              <td class="pico-color-red-450">{{$item ['suma_precios']}}</td>
              @endif
                  

                  
              @endforeach


              <th>
                {{-- <a href="{{route('EditarUsuario',['id'=>$u->id])}}" data-tooltip="Editar">Editar</a></strong> --}}
              </th>
            </tr>
          @endforeach
        
        </tfoot>
    </table>
  </figure>

{{--   
  @if (method_exists($usuarios, 'currentPage'))   

    //PAGINACION 
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
  --}}

 
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
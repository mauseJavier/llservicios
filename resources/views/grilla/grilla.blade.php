@extends('principal.principal')

@section('body')

<div class="container">

  <h1>Grilla Clientes</h1>

      <a href="{{route('ServiciosImpagos')}}" role="button" class="contrast outline" style="font-size: 20px; padding: 4px 8px; ">Impagos</a>
      <a href="{{route('ServiciosPagos')}}" role="button" class="contrast outline" style="font-size: 20px; padding: 4px 8px; ">Pagos</a>
      <a href="{{route('Grilla')}}" role="button" class=" outline" style="font-size: 20px; padding: 4px 8px; ">Grilla</a>


  <nav>
    <ul>

      <li>
        <form class="form" action="{{route('GrillaBuscarCliente')}}" method="GET" id="bucarCliente">
            {{-- @csrf --}}
            <div class="input-group">
                <input type="search" class="input" id="buscar" name="buscar"                      
                
                @if (isset($buscar))
                  value="{{$buscar}}"
                @endif  placeholder="Buscar...">


            </div>
        </form>
      </li>
    </ul>

    </ul>
      <li>
        <a href="{{route('NuevoCobro')}}" role="button">Nuevo Cobro</a>
      </li>
    <ul>
   
  </nav>
  
</div>

<div class="container-fluid">

  <figure>
    <table>
        <thead>
          <tr>
            <th scope="col">Cliente</th>
            <th scope="col" style="text-align: right;">Enero</th>
            <th scope="col" style="text-align: right;">Febrero</th>
            <th scope="col" style="text-align: right;">Marzo</th>
            <th scope="col" style="text-align: right;">Abril</th>
            <th scope="col" style="text-align: right;">Mayo</th>
            <th scope="col" style="text-align: right;">Junio</th>
            <th scope="col" style="text-align: right;">Julio</th>
            <th scope="col" style="text-align: right;">Agosto</th>
            <th scope="col" style="text-align: right;">Septiembre</th>
            <th scope="col" style="text-align: right;">Octubre</th>
            <th scope="col" style="text-align: right;">Noviembre</th>
            <th scope="col" style="text-align: right;">Diciembre</th>
          </tr>
        </thead>
        <tbody>

          @foreach ($clientes as $c)
            <tr>              

              
              <td><a href="{{route('ServicioPagarBuscarCliente',['estado'=>'impago','buscar'=>$c->nombre])}}" data-tooltip="Ver Impagos">{{$c->nombre}}</a></td>
             

              @foreach ($c->datos as $item)    
              
              @if ($item['estado_pago'] == 'pago')
              <td style="text-align: right;" class="pico-color-jade-500">${{$item ['suma_precios']}}</td>
              @else
              <td style="text-align: right;" class="pico-color-red-450">${{$item ['suma_precios']}}</td>
              @endif
                  

                  
              @endforeach



            
          @endforeach

        </tr>          

            <td><h5 class="pico-color-red-450">Impago</h5></td>
            @foreach ($total as $item)
              <td style="text-align: right;"><h5 class="pico-color-red-450">${{$item ['impago']}}</h5></td>
            @endforeach


          </tr>
          <tr>

            <td><h5 class="pico-color-jade-500">Pago</h5></td>
            @foreach ($total as $item)
              <td style="text-align: right;"><h5 class="pico-color-jade-500">${{$item ['pago']}}</h5></td>
            @endforeach

          </tr>
          <tr>


            <td><h5 class="pico-color-pumpkin-300">Total</h5></td>
            @foreach ($total as $item)
              <td style="text-align: right;"><h5 class="pico-color-pumpkin-300">${{$item ['total']}}</h5></td>
            @endforeach

          </tr>


        
        </tbody>
    </table>
  </figure>

  
  @if (method_exists($clientes, 'currentPage'))   

    {{-- //PAGINACION  --}}
   <nav> 
    <ul>
      <li><strong>Pag. {{$clientes->currentPage()}} de: {{$clientes->lastPage()}}, Total Res.: {{$clientes->total()}}</strong></li>
    </ul>

    <ul>
      <li><a href=" {{$clientes->previousPageUrl()}}" role="button">Anterior</a></li>
          @if ($clientes->currentPage()-1 != 0)
            <li>
              <a href="{{$clientes->url($clientes->currentPage()-1)}}">{{$clientes->currentPage()-1}}</a> 
            </li>
          @endif
            <li>
              <strong>
                <a href="{{$clientes->url($clientes->currentPage())}}">{{$clientes->currentPage()}}</a>
              </strong>            
            </li>
          @if (($clientes->currentPage() +1 ) < round($clientes->total()/$clientes->perPage())+1)
            <li>
              <a href="{{$clientes->url($clientes->currentPage() +1)}}">{{$clientes->currentPage() +1}}</a>
            </li>
          @endif

      <li><a href="{{$clientes->nextPageUrl()}}" role="button">Siguiente</a></li>
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
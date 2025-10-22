@extends('principal.principal')



@section('body')

<div class="container">

  <h1>Pagos</h1>


  

  <!-- Filtro por Fechas - Diseño con Radio Buttons -->
  <div class="grid">
      <!-- Filtros Rápidos con Radio Buttons -->
      <fieldset class="filtros-rapidos">
        <legend>🎯 Filtros Rápidos:</legend>
        
        <form id="filtroRapidoForm" method="GET" action="{{route('Pagos')}}">
          <label>
            <input type="radio" name="filtro_rapido" value="todos" 
                   {{ !request('fecha_inicio') && !request('fecha_fin') ? 'checked' : '' }}
                   onchange="aplicarFiltroRapido(this)" />
            📊 Todos los pagos
          </label>
          
          <label>
            <input type="radio" name="filtro_rapido" value="hoy" 
                   {{ request('fecha_inicio') == date('Y-m-d') && request('fecha_fin') == date('Y-m-d') ? 'checked' : '' }}
                   onchange="aplicarFiltroRapido(this)" />
            📅 Hoy
          </label>
          
          <label>
            <input type="radio" name="filtro_rapido" value="mes" 
                   {{ request('fecha_inicio') == date('Y-m-01') && request('fecha_fin') == date('Y-m-t') ? 'checked' : '' }}
                   onchange="aplicarFiltroRapido(this)" />
            📆 Este Mes
          </label>
          
          <label>
            <input type="radio" name="filtro_rapido" value="año" 
                   {{ request('fecha_inicio') == date('Y-01-01') && request('fecha_fin') == date('Y-12-31') ? 'checked' : '' }}
                   onchange="aplicarFiltroRapido(this)" />
            📈 Este Año
          </label>
          
          <label>
            <input type="radio" name="filtro_rapido" value="personalizado"
                   {{ (request('fecha_inicio') || request('fecha_fin')) && 
                      !(request('fecha_inicio') == date('Y-m-d') && request('fecha_fin') == date('Y-m-d')) &&
                      !(request('fecha_inicio') == date('Y-m-01') && request('fecha_fin') == date('Y-m-t')) &&
                      !(request('fecha_inicio') == date('Y-01-01') && request('fecha_fin') == date('Y-12-31')) ? 'checked' : '' }} />
            🔧 Personalizado
          </label>
        </form>
        
      </fieldset>


            <!-- Estado del Filtro -->
    @if(request('fecha_inicio') || request('fecha_fin'))
        <div class="estado-filtro activo">
          <div class="estado-content">
            <span class="estado-icon">✅</span>
            <div class="estado-text">
              <strong>Filtro Activo:</strong>
              @if(request('fecha_inicio') && request('fecha_fin'))
                Del {{ \Carbon\Carbon::parse(request('fecha_inicio'))->format('d/m/Y') }} al {{ \Carbon\Carbon::parse(request('fecha_fin'))->format('d/m/Y') }}
              @elseif(request('fecha_inicio'))
                Desde {{ \Carbon\Carbon::parse(request('fecha_inicio'))->format('d/m/Y') }}
              @elseif(request('fecha_fin'))
                Hasta {{ \Carbon\Carbon::parse(request('fecha_fin'))->format('d/m/Y') }}
              @endif
            </div>
          </div>
        </div>
      @else
        <div class="estado-filtro">
          <div class="estado-content">
            <span class="estado-icon">📋</span>
            <div class="estado-text">Mostrando <strong>todos los pagos</strong> registrados</div>
          </div>
        </div>
      @endif
    </div>


    
    
    <!-- Filtro Personalizado -->
    <div class="grid">
      <form method="GET" action="{{route('Pagos')}}" class="form-filtro">
        <div class="grid">
          <div class="input-group">
            <label for="fecha_inicio">Desde:</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" 
                   value="{{ request('fecha_inicio') }}" 
                   class="input-fecha">
          </div>
          
          <div class="input-group">
            <label for="fecha_fin">Hasta:</label>
            <input type="date" id="fecha_fin" name="fecha_fin" 
                   value="{{ request('fecha_fin') }}" 
                   class="input-fecha">
          </div>
          
        </div>
        <div class="acciones">
          <button type="submit" class="btn-aplicar">
            <span>�</span>
            Aplicar Filtro
          </button>
        </div>
      </form>
    </div>
    
  </div>
  

<div class="container">

  <div class="resumen-pagos">
    <h2>📊 Resumen de Pagos por Forma de Pago</h2>
    
    <div class="resumen-cards">
      @if(isset($resumenPagos) && count($resumenPagos) > 0)
        @foreach($resumenPagos as $resumen)
          <div class="resumen-card">
            <strong>{{ $resumen->formaPago }}</strong>
            <div class="total">${{ number_format($resumen->totalImporte, 2) }}</div>
            <div class="cantidad">{{ $resumen->cantidadPagos }} pagos</div>
            <div class="promedio">Promedio: ${{ number_format($resumen->totalImporte / $resumen->cantidadPagos, 2) }}</div>
          </div>
          <hr>
        @endforeach
      @else
        <div style="grid-column: 1 / -1; text-align: center; padding: 2rem;">
          <p>📊 No hay pagos registrados</p>
        </div>
      @endif
    </div>
  </div>

  @if(isset($resumenPagos) && count($resumenPagos) > 0)
    <div class="resumen-tabla">
      <h3>📈 Análisis Detallado</h3>
      <figure>
        <table>
          <thead>
            <tr>
              <th scope="col">Forma de Pago</th>
              <th scope="col">Cantidad</th>
              <th scope="col">Total</th>
              <th scope="col">Promedio</th>
              <th scope="col">Porcentaje</th>
            </tr>
          </thead>
          <tbody>
            @php $totalGeneral = collect($resumenPagos)->sum('totalImporte'); @endphp
            @foreach($resumenPagos as $resumen)
              <tr>
                <td><strong>{{ $resumen->formaPago }}</strong></td>
                <td>{{ $resumen->cantidadPagos }}</td>
                <td>${{ number_format($resumen->totalImporte, 2) }}</td>
                <td>${{ number_format($resumen->totalImporte / $resumen->cantidadPagos, 2) }}</td>
                <td>
                  {{ number_format(($resumen->totalImporte / $totalGeneral) * 100, 1) }}%
                  <div class="porcentaje-bar" style="width: {{ ($resumen->totalImporte / $totalGeneral) * 100 }}px;"></div>
                </td>
              </tr>
            @endforeach
            <tr class="total-row">
              <td><strong>TOTAL GENERAL</strong></td>
              <td><strong>{{ collect($resumenPagos)->sum('cantidadPagos') }}</strong></td>
              <td><strong>${{ number_format($totalGeneral, 2) }}</strong></td>
              <td><strong>${{ number_format($totalGeneral / collect($resumenPagos)->sum('cantidadPagos'), 2) }}</strong></td>
              <td><strong>100%</strong></td>
            </tr>
          </tbody>
        </table>
      </figure>
    </div>
  @endif

  <hr>

  <h2>Detalle de Pagos</h2>

    <nav>
        <ul>
            <li>
              <form class="form" action="{{route('BuscarCliente')}}" method="GET">
                  
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

        </ul>
    </nav>

  <figure>
    <table>
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Servicio</th>
            <th scope="col">Cliente</th>
            <th scope="col">Forma de Pago</th>
            <th scope="col">Usuario</th>
            <th scope="col">Importe</th>
            <th scope="col">Acciones</th>
          </tr>
        </thead>
        <tbody>
     
          @foreach ($pagos as $e)

  {{-- {
    "id": 1,
    "id_servicio_pagar": 8,
    "id_usuario": 1,
    "forma_pago": 3,
    "importe": 4081.17,
    "comentario": null,
    "created_at": "2024-01-21 18:44:17",
    "updated_at": "2024-01-21 18:44:17",
    "nombreUsuario": "DESMARET JAVIER NICOLAS",
    "Servicio": "Vito Daugherty",
    "Cliente": "Mr. Keenan O'Connell DDS",
    "idCliente": 1,
    "formaPago": "MercadoPago"
  }
] --}}
            <tr>              
              <td>{{$e->id}}</td>
              <td>{{$e->Servicio}}</td>
              <td>{{$e->Cliente}}</td>
              <td>{{$e->formaPago}}</td>
              <td>{{$e->nombreUsuario}}</td>
              <td>${{number_format($e->importe, 2)}}</td>
              <th>                  
                  <strong><a href="{{route('PagosVer',['idServicioPagar'=>$e->idServicioPagar])}}" data-tooltip="Ver Pago">Ver</a></strong>
              </th>
            </tr>
          @endforeach
        
        </tfoot>
    </table>
</figure>





@if (method_exists($pagos, 'currentPage'))   

  {{-- //PAGINACION --}}
  <nav> 
    <ul>
      <li><strong>Pag. {{$pagos->currentPage()}} de: {{$pagos->lastPage()}} , Total Res.: {{$pagos->total()}}</strong></li>
    </ul>

    <ul>
      <li><a href=" {{$pagos->previousPageUrl()}}" role="button">Anterior</a></li>
          @if ($pagos->currentPage()-1 != 0)
            <li>
              <a href="{{$pagos->url($pagos->currentPage()-1)}}">{{$pagos->currentPage()-1}}</a> 
            </li>
          @endif
            <li>
              <strong>
                <a href="{{$pagos->url($pagos->currentPage())}}">{{$pagos->currentPage()}}</a>
              </strong>            
            </li>
          @if (($pagos->currentPage() +1 ) < round($pagos->total()/$pagos->perPage())+1)
            <li>
              <a href="{{$pagos->url($pagos->currentPage() +1)}}">{{$pagos->currentPage() +1}}</a>
            </li>
          @endif

      <li><a href="{{$pagos->nextPageUrl()}}" role="button">Siguiente</a></li>
    </ul>
  </nav>


@endif

    <script>
      function aplicarFiltroRapido(radio) {
          const valor = radio.value;
          let url = "{{ route('Pagos') }}";
          
          switch(valor) {
              case 'todos':
                  window.location.href = url;
                  break;
              case 'hoy':
                  window.location.href = url + '?fecha_inicio={{ date("Y-m-d") }}&fecha_fin={{ date("Y-m-d") }}';
                  break;
              case 'mes':
                  window.location.href = url + '?fecha_inicio={{ date("Y-m-01") }}&fecha_fin={{ date("Y-m-t") }}';
                  break;
              case 'año':
                  window.location.href = url + '?fecha_inicio={{ date("Y-01-01") }}&fecha_fin={{ date("Y-12-31") }}';
                  break;
              case 'personalizado':
                  // Para personalizado, enfocamos el primer input de fecha
                  document.getElementById('fecha_inicio').focus();
                  break;
          }
      }
    </script>


</div>


@endsection 
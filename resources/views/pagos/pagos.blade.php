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
                   {{ (!request('fecha_inicio') && !request('fecha_fin')) ? 'checked' : '' }}
                   onchange="aplicarFiltroRapido(this)" />
            📊 Todos los pagos
          </label>
          
          <label>
            <input type="radio" name="filtro_rapido" value="hoy" 
                   {{ (request('fecha_inicio') == date('Y-m-d') && request('fecha_fin') == date('Y-m-d')) ? 'checked' : '' }}
                   onchange="aplicarFiltroRapido(this)" />
            📅 Hoy
          </label>
          
          <label>
            <input type="radio" name="filtro_rapido" value="mes" 
                   {{ (request('fecha_inicio') == date('Y-m-01') && request('fecha_fin') == date('Y-m-t')) ? 'checked' : '' }}
                   onchange="aplicarFiltroRapido(this)" />
            📆 Este Mes
          </label>
          
          <label>
            <input type="radio" name="filtro_rapido" value="año" 
                   {{ (request('fecha_inicio') == date('Y-01-01') && request('fecha_fin') == date('Y-12-31')) ? 'checked' : '' }}
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
    @if($fechaInicio || $fechaFin || $usuarioId)
        <div class="estado-filtro activo">
          <div class="estado-content">
            <span class="estado-icon">✅</span>
            <div class="estado-text">
              <strong>Filtro Activo:</strong>
              @if($fechaInicio && $fechaFin)
                @if($fechaInicio == $fechaFin)
                  {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }}
                @else
                  Del {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}
                @endif
              @elseif($fechaInicio)
                Desde {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }}
              @elseif($fechaFin)
                Hasta {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}
              @endif
              @if($usuarioId)
                @php
                  $usuarioSeleccionado = $usuarios->firstWhere('id', $usuarioId);
                @endphp
                @if($usuarioSeleccionado)
                  <br>👤 Usuario: <strong>{{ $usuarioSeleccionado->name }}</strong>
                @endif
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
                   value="{{ $fechaInicio }}" 
                   class="input-fecha">
          </div>
          
          <div class="input-group">
            <label for="fecha_fin">Hasta:</label>
            <input type="date" id="fecha_fin" name="fecha_fin" 
                   value="{{ $fechaFin }}" 
                   class="input-fecha">
          </div>

          <div class="input-group">
            <label for="usuario_id">👤 Usuario:</label>
            <select id="usuario_id" name="usuario_id" class="input-fecha">
              <option value="">Todos los usuarios</option>
              @foreach($usuarios as $usuario)
                <option value="{{ $usuario->id }}" 
                  {{ (isset($usuarioId) && $usuarioId == $usuario->id) ? 'selected' : '' }}>
                  {{ $usuario->name }}
                </option>
              @endforeach
            </select>
          </div>
          
        </div>
        <div class="acciones">
          <button type="submit" class="btn-aplicar">
            <span>🔍</span>
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

  {{-- Resumen por Usuario que realizó el cobro --}}
  <div class="resumen-pagos">
    <h2>👥 Resumen de Pagos por Usuario (Quien Cobró)</h2>
    
    <div class="resumen-cards">
      @if(isset($resumenPorUsuario) && count($resumenPorUsuario) > 0)
        @foreach($resumenPorUsuario as $resumen)
          <div class="resumen-card" style="border-left: 4px solid #2196f3; padding-left: 0.5rem; margin-bottom: 1rem; margin-right: 1px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
              <strong style="font-size: 1.1rem;">👤 {{ $resumen->nombreUsuario }}</strong>
            </div>
            <div class="total" style="color: #2196f3;">${{ number_format($resumen->totalImporte, 2) }}</div>
            <div class="cantidad">{{ $resumen->cantidadPagos }} pagos</div>
            <div class="promedio">Promedio: ${{ number_format($resumen->totalImporte / $resumen->cantidadPagos, 2) }}</div>
          </div>
          <hr>
        @endforeach
      @else
        <div style="grid-column: 1 / -1; text-align: center; padding: 2rem;">
          <p>👥 No hay pagos registrados por usuarios</p>
        </div>
      @endif
    </div>
  </div>

  @if(isset($resumenPorUsuario) && count($resumenPorUsuario) > 0)
    <div class="resumen-tabla">
      <h3>📊 Análisis Detallado por Usuario</h3>
      <figure>
        <table>
          <thead>
            <tr>
              <th scope="col">👤 Usuario</th>
              <th scope="col">Cantidad Pagos</th>
              <th scope="col">Total Cobrado</th>
              <th scope="col">Promedio por Pago</th>
              <th scope="col">Porcentaje del Total</th>
            </tr>
          </thead>
          <tbody>
            @php 
              $totalGeneralUsuarios = collect($resumenPorUsuario)->sum('totalImporte');
              $totalPagosUsuarios = collect($resumenPorUsuario)->sum('cantidadPagos');
            @endphp
            @foreach($resumenPorUsuario as $resumen)
              <tr>
                <td><strong>{{ $resumen->nombreUsuario }}</strong></td>
                <td>{{ $resumen->cantidadPagos }}</td>
                <td>${{ number_format($resumen->totalImporte, 2) }}</td>
                <td>${{ number_format($resumen->totalImporte / $resumen->cantidadPagos, 2) }}</td>
                <td>
                  {{ number_format(($resumen->totalImporte / $totalGeneralUsuarios) * 100, 1) }}%
                  <div class="porcentaje-bar" style="width: {{ ($resumen->totalImporte / $totalGeneralUsuarios) * 100 }}px; background-color: #2196f3;"></div>
                </td>
              </tr>
            @endforeach
            <tr class="total-row">
              <td><strong>TOTAL GENERAL</strong></td>
              <td><strong>{{ $totalPagosUsuarios }}</strong></td>
              <td><strong>${{ number_format($totalGeneralUsuarios, 2) }}</strong></td>
              <td><strong>${{ number_format($totalGeneralUsuarios / $totalPagosUsuarios, 2) }}</strong></td>
              <td><strong>100%</strong></td>
            </tr>
          </tbody>
        </table>
      </figure>
    </div>
  @endif

  <hr>

  {{-- Resumen de Facturación ARCA --}}
  <div class="resumen-pagos">
    <h2>🧾 Resumen de Facturación ARCA</h2>
    
    @if($resumenFacturacion['total']->cantidad > 0)
      <div class="resumen-cards">
        {{-- Pagos Facturados --}}
        <div class="resumen-card" style="border-left: 4px solid #4caf50; padding-left: 0.5rem; margin-bottom: 1rem;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
            <strong style="font-size: 1.1rem;">✅ Facturados con ARCA</strong>
          </div>
          <div class="total" style="color: #4caf50;">${{ number_format($resumenFacturacion['facturados']->total, 2) }}</div>
          <div class="cantidad">{{ $resumenFacturacion['facturados']->cantidad }} pagos</div>
          <div class="promedio">Promedio: ${{ number_format($resumenFacturacion['facturados']->promedio, 2) }}</div>
          <div class="porcentaje">
            {{ $resumenFacturacion['total']->total > 0 ? number_format(($resumenFacturacion['facturados']->total / $resumenFacturacion['total']->total) * 100, 1) : 0 }}% del total
          </div>
        </div>
        <hr>
        
        {{-- Pagos No Facturados --}}
        <div class="resumen-card" style="border-left: 4px solid #ff9800; padding-left: 0.5rem; margin-bottom: 1rem;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
            <strong style="font-size: 1.1rem;">⚠️ Sin Facturar</strong>
          </div>
          <div class="total" style="color: #ff9800;">${{ number_format($resumenFacturacion['noFacturados']->total, 2) }}</div>
          <div class="cantidad">{{ $resumenFacturacion['noFacturados']->cantidad }} pagos</div>
          <div class="promedio">Promedio: ${{ number_format($resumenFacturacion['noFacturados']->promedio, 2) }}</div>
          <div class="porcentaje">
            {{ $resumenFacturacion['total']->total > 0 ? number_format(($resumenFacturacion['noFacturados']->total / $resumenFacturacion['total']->total) * 100, 1) : 0 }}% del total
          </div>
        </div>
      </div>

      {{-- Tabla detallada --}}
      <div class="resumen-tabla">
        <h3>📊 Comparativa de Facturación</h3>
        <figure>
          <table>
            <thead>
              <tr>
                <th scope="col">Estado</th>
                <th scope="col">Cantidad</th>
                <th scope="col">Total</th>
                <th scope="col">Promedio</th>
                <th scope="col">Porcentaje</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>✅ Facturados</strong></td>
                <td>{{ $resumenFacturacion['facturados']->cantidad }}</td>
                <td>${{ number_format($resumenFacturacion['facturados']->total, 2) }}</td>
                <td>${{ number_format($resumenFacturacion['facturados']->promedio, 2) }}</td>
                <td>
                  {{ $resumenFacturacion['total']->total > 0 ? number_format(($resumenFacturacion['facturados']->total / $resumenFacturacion['total']->total) * 100, 1) : 0 }}%
                  <div class="porcentaje-bar" style="width: {{ $resumenFacturacion['total']->total > 0 ? ($resumenFacturacion['facturados']->total / $resumenFacturacion['total']->total) * 100 : 0 }}px; background-color: #4caf50;"></div>
                </td>
              </tr>
              <tr>
                <td><strong>⚠️ Sin Facturar</strong></td>
                <td>{{ $resumenFacturacion['noFacturados']->cantidad }}</td>
                <td>${{ number_format($resumenFacturacion['noFacturados']->total, 2) }}</td>
                <td>${{ number_format($resumenFacturacion['noFacturados']->promedio, 2) }}</td>
                <td>
                  {{ $resumenFacturacion['total']->total > 0 ? number_format(($resumenFacturacion['noFacturados']->total / $resumenFacturacion['total']->total) * 100, 1) : 0 }}%
                  <div class="porcentaje-bar" style="width: {{ $resumenFacturacion['total']->total > 0 ? ($resumenFacturacion['noFacturados']->total / $resumenFacturacion['total']->total) * 100 : 0 }}px; background-color: #ff9800;"></div>
                </td>
              </tr>
              <tr class="total-row">
                <td><strong>TOTAL GENERAL</strong></td>
                <td><strong>{{ $resumenFacturacion['total']->cantidad }}</strong></td>
                <td><strong>${{ number_format($resumenFacturacion['total']->total, 2) }}</strong></td>
                <td><strong>${{ number_format($resumenFacturacion['total']->total / max($resumenFacturacion['total']->cantidad, 1), 2) }}</strong></td>
                <td><strong>100%</strong></td>
              </tr>
            </tbody>
          </table>
        </figure>
      </div>
    @else
      <div style="text-align: center; padding: 2rem;">
        <p>📊 No hay pagos registrados para mostrar estadísticas de facturación</p>
      </div>
    @endif
  </div>

  <hr>

  <h2>Detalle de Pagos</h2>

  @if(isset($buscar) && $buscar)
    <article style="margin-bottom: 1rem; background: #4543d1ff; border-left: 4px solid #2196f3;">
      <p style="margin: 0;">
        🔍 <strong>Búsqueda activa:</strong> Filtrando por cliente que coincida con "{{ $buscar }}"
        <a href="{{route('Pagos', ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'usuario_id' => $usuarioId])}}" style="margin-left: 1rem;">
          ✖ Limpiar
        </a>
      </p>
    </article>
  @endif

    <nav>
        <ul>
            <li>
              <form class="form" action="{{route('Pagos')}}" method="GET">
                  
                  <div class="input-group">
                      <input type="search" class="input" id="buscar" name="buscar" 
                      @if (isset($buscar))
                          value="{{$buscar}}"
                      @endif  placeholder="Buscar por cliente (nombre, correo, DNI)...">
                      
                      {{-- Mantener los filtros de fecha y usuario al buscar --}}
                      <input type="hidden" name="fecha_inicio" value="{{ $fechaInicio }}">
                      <input type="hidden" name="fecha_fin" value="{{ $fechaFin }}">
                      <input type="hidden" name="usuario_id" value="{{ $usuarioId }}">
                  </div>
              </form>
            </li>
        </ul>
        <ul>
            @if(isset($buscar) && $buscar)
                <li>
                    <a href="{{route('Pagos', ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'usuario_id' => $usuarioId])}}" role="button" class="secondary">
                        Limpiar búsqueda
                    </a>
                </li>
            @endif
        </ul>
    </nav>

  <figure>
    <div class="overflow-auto">
      <table>
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Cliente</th>
              <th scope="col">Servicio</th>
              <th scope="col">Forma de Pago 1</th>
              <th scope="col">Importe 1</th>
              <th scope="col">Forma de Pago 2</th>
              <th scope="col">Importe 2</th>
              <th scope="col">Total</th>
              <th scope="col">Usuario</th>
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
                <td>{{$e->Cliente}}</td>
                <td>{{$e->Servicio}}</td>
                <td>{{$e->formaPago}}</td>
                <td>${{number_format($e->importe, 2)}}</td>
                <td>
                  @if($e->formaPago2)
                    {{$e->formaPago2}}
                  @else
                    <small style="color: #999;">-</small>
                  @endif
                </td>
                <td>
                  @if($e->importe2 && $e->importe2 > 0)
                    ${{number_format($e->importe2, 2)}}
                  @else
                    <small style="color: #999;">-</small>
                  @endif
                </td>
                <td>
                  <strong>${{number_format($e->importe + ($e->importe2 ?? 0), 2)}}</strong>
                </td>
                <td>{{$e->nombreUsuario}}</td>
                <th>                  
                    <strong><a href="{{route('PagosVer',['idServicioPagar'=>$e->idServicioPagar])}}" data-tooltip="Ver Pago">Detalle</a></strong>
                    @if ( isset($e->afip_cae) && $e->afip_cae != null )
                      
                      <strong><a href="{{route('FacturaAfipPDF',['pagoId'=>$e->id])}}" data-tooltip="Ver Comprobante AFIP" target="_blank">Factura</a></strong>
                        
                    @endif
                </th>
              </tr>
            @endforeach
          
          </tfoot>
      </table>
    </div>
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
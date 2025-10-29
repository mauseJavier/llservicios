@extends('principal.principal')

@section('body')


<div class="container">
  <h1>Panel de Servicios</h1>
  
  {{-- Estadísticas resumidas --}}
  <div class="grid" style="margin-bottom: 20px;">
    <article style="background: #ff6b6b; color: white; text-align: center;">
      <h3 style="margin: 0;">{{ $serviciosImpagos->count() }}</h3>
      <p style="margin: 0;">Servicios Impagos</p>
      <h4 style="margin: 0;">${{ number_format($serviciosImpagos->sum('total'), 2) }}</h4>
    </article>
    <article style="background: #21652cff; color: white; text-align: center;">
      <h3 style="margin: 0;">{{ $serviciosPagos->count() }}</h3>
      <p style="margin: 0;">Servicios Pagos</p>
      <h4 style="margin: 0;">${{ number_format($serviciosPagos->sum('total'), 2) }}</h4>
    </article>
    <article style="background: #339af0; color: white; text-align: center;">
      <h3 style="margin: 0;">{{ $serviciosImpagos->count() + $serviciosPagos->count() }}</h3>
      <p style="margin: 0;">Total Servicios</p>
      <h4 style="margin: 0;">${{ number_format($serviciosImpagos->sum('total') + $serviciosPagos->sum('total'), 2) }}</h4>
    </article>
  </div>

  <details {{ request()->hasAny(['fecha_desde', 'fecha_hasta', 'importe_min', 'importe_max', 'nombre_servicio', 'nombre_empresa']) ? 'open' : '' }}>
    <summary style="cursor: pointer; padding: 12px 16px; background: #495057; color: white; border-radius: 8px; margin-bottom: 16px; font-weight: 500; list-style: none;">
      <strong>🔍 Filtros de Búsqueda</strong>
      @if(request()->hasAny(['fecha_desde', 'fecha_hasta', 'importe_min', 'importe_max', 'nombre_servicio', 'nombre_empresa']))
        <span style="background: #51cf66; padding: 4px 8px; border-radius: 4px; font-size: 12px; margin-left: 10px;">Filtros activos</span>
      @endif
    </summary>
    <div style="padding: 20px; border: 2px solid #495057; border-radius: 8px; background: #315579ff;">
      <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; align-items: flex-end;">
        <div>
          <label for="fecha_desde"><strong>📅 Desde</strong></label>
          <input type="date" id="fecha_desde" name="fecha_desde" value="{{ $fechaDesde ?? '' }}" style="width: 100%;">
        </div>
        <div>
          <label for="fecha_hasta"><strong>📅 Hasta</strong></label>
          <input type="date" id="fecha_hasta" name="fecha_hasta" value="{{ $fechaHasta ?? '' }}" style="width: 100%;">
        </div>
        <div>
          <label for="importe_min"><strong>💵 Importe mínimo</strong></label>
          <input type="number" step="0.01" id="importe_min" name="importe_min" value="{{ $importeMin ?? '' }}" placeholder="0.00" style="width: 100%;">
        </div>
        <div>
          <label for="importe_max"><strong>💵 Importe máximo</strong></label>
          <input type="number" step="0.01" id="importe_max" name="importe_max" value="{{ $importeMax ?? '' }}" placeholder="Sin límite" style="width: 100%;">
        </div>
        <div>
          <label for="nombre_servicio"><strong>🛍️ Servicio</strong></label>
          <input type="text" id="nombre_servicio" name="nombre_servicio" value="{{ $nombreServicio ?? '' }}" placeholder="Nombre del servicio" style="width: 100%;">
        </div>
        <div>
          <label for="nombre_empresa"><strong>🏢 Empresa</strong></label>
          <input type="text" id="nombre_empresa" name="nombre_empresa" value="{{ $nombreEmpresa ?? '' }}" placeholder="Nombre de la empresa" style="width: 100%;">
        </div>
        <div style="grid-column: 1 / -1; display: flex; gap: 10px; justify-content: center;">
          <button type="submit" style="background: #28a745; color: white; border: none; border-radius: 8px; padding: 12px 32px; font-size: 16px; cursor: pointer; font-weight: bold;">
            🔍 Filtrar
          </button>
          <a href="{{ route('panelServicios') }}" role="button" class="secondary" style="padding: 12px 32px; font-size: 16px; text-decoration: none;">
            🔄 Limpiar Filtros
          </a>
        </div>
      </form>
    </div>
  </details>
</div>

<div class="container">

  <h2 style="color: #ff6b6b; border-bottom: 3px solid #ff6b6b; padding-bottom: 10px; margin-bottom: 20px;">
    ⚠️ Servicios Impagos 
    @if($serviciosImpagos->count() > 0)
      <span style="background: #ff6b6b; color: white; padding: 4px 12px; border-radius: 20px; font-size: 16px; margin-left: 10px;">
        {{ $serviciosImpagos->count() }}
      </span>
    @endif
  </h2>

  @if($serviciosImpagos->count() > 0)
    <figure>
        <table>
            <thead>
              <tr style="background: #f8f9fa;">
                <th scope="col" style="width: 80px;">Imagen</th>
                <th scope="col">Fecha</th>
                <th scope="col">Servicio</th>
                <th scope="col">Empresa</th>
                <th scope="col">Período</th>
                <th scope="col">Fecha Vencimiento</th>
                <th scope="col" style="text-align: center;">Cantidad</th>
                <th scope="col" style="text-align: right;">Precio U.</th>
                <th scope="col" style="text-align: right;">Total</th>
                <th scope="col" style="text-align: center;">Acción</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($serviciosImpagos as $s)
                <tr>
                  <td><img width="100%" src="{{$s->imagenServicio}}" alt="{{$s->nombreServicio}}" style="border-radius: 8px;"></td>
                  <td>{{ \Carbon\Carbon::parse($s->fechaCobro)->format('d/m/Y') }}</td>
                  <td><strong>{{$s->nombreServicio}}</strong></td>
                  <td>{{$s->nombreEmpresa}}</td>
                  <td>{{$s->periodo_servicio}}</td>
                  <td>
                    @if($s->fecha_vencimiento)
                      {{ \Carbon\Carbon::parse($s->fecha_vencimiento)->format('d/m/Y') }}
                    @else
                      <span style="color: #6c757d; font-style: italic;">No especificada</span>
                    @endif
                  </td>
                  <td style="text-align: center;">{{$s->cantidadServicio}}</td>
                  <td style="text-align: right;">${{ number_format($s->precioServicio, 2) }}</td>
                  <td style="text-align: right;"><strong>${{ number_format($s->total, 2) }}</strong></td>
                  <td style="text-align: center;">
                      <a href="{{route('pago.generar', $s->servicio_id)}}" role="button" style="background: #28a745; margin: 0; padding: 8px 16px; font-size: 14px;">
                        💳 Pagar
                      </a>
                  </td>
                </tr>
              @endforeach
            </tbody>
            <tfoot>
              <tr style="background: #fff3cd; font-weight: bold;">
                <td colspan="6" style="text-align: right; padding: 12px;">TOTAL A PAGAR:</td>
                <td style="text-align: right; font-size: 18px; color: #ff6b6b;">${{ number_format($serviciosImpagos->sum('total'), 2) }}</td>
                <td></td>
              </tr>
            </tfoot>
        </table>
    </figure>
  @else
    <article style="background: #d4edda; border: 2px solid #c3e6cb; color: #155724; text-align: center; padding: 30px;">
      <h3 style="margin: 0;">✅ ¡Excelente! No tienes servicios pendientes de pago</h3>
    </article>
  @endif

</div>

<div class="container" style="margin-top: 40px;">
  <h2 style="color: #51cf66; border-bottom: 3px solid #51cf66; padding-bottom: 10px; margin-bottom: 20px;">
    ✅ Servicios Pagos 
    @if($serviciosPagos->count() > 0)
      <span style="background: #51cf66; color: white; padding: 4px 12px; border-radius: 20px; font-size: 16px; margin-left: 10px;">
        {{ $serviciosPagos->count() }}
      </span>
    @endif
  </h2>

  @if($serviciosPagos->count() > 0)
    <figure>
        <table>
            <thead>
              <tr style="background: #f8f9fa;">
                <th scope="col" style="width: 80px;">Imagen</th>
                <th scope="col">Fecha</th>
                <th scope="col">Servicio</th>
                <th scope="col">Empresa</th>
                <th scope="col" style="text-align: center;">Cantidad</th>
                <th scope="col" style="text-align: right;">Precio U.</th>
                <th scope="col" style="text-align: right;">Total Pagado</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($serviciosPagos as $s)
                <tr>
                  <td><img width="100%" src="{{$s->imagenServicio}}" alt="{{$s->nombreServicio}}" style="border-radius: 8px;"></td>
                  <td>{{ \Carbon\Carbon::parse($s->fechaCobro)->format('d/m/Y') }}</td>
                  <td><strong>{{$s->nombreServicio}}</strong></td>
                  <td>{{$s->nombreEmpresa}}</td>
                  <td style="text-align: center;">{{$s->cantidadServicio}}</td>
                  <td style="text-align: right;">${{ number_format($s->precioServicio, 2) }}</td>
                  <td style="text-align: right;">
                    <span style="color: #51cf66; font-weight: bold; font-size: 16px;">
                      ✓ ${{ number_format($s->total, 2) }}
                    </span>
                  </td>
                </tr>
              @endforeach
            </tbody>
            <tfoot>
              <tr style="background: #d4edda; font-weight: bold;">
                <td colspan="6" style="text-align: right; padding: 12px;">TOTAL PAGADO:</td>
                <td style="text-align: right; font-size: 18px; color: #51cf66;">${{ number_format($serviciosPagos->sum('total'), 2) }}</td>
              </tr>
            </tfoot>
        </table>
    </figure>
  @else
    <article style="background: #f8f9fa; border: 2px solid #dee2e6; color: #6c757d; text-align: center; padding: 30px;">
      <h3 style="margin: 0;">📋 No hay servicios pagados registrados</h3>
    </article>
  @endif

</div>
    
@endsection
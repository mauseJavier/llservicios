@extends('principal.principal')

@section('body')


    <div class="container">
        <h1>Panel de Servicios</h1>

        {{-- mostrar mensajes de éxito o error --}}
        @if (session('success'))
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20       px;">
                {{ session('error') }}
            </div>
        @endif  

        {{-- Estadísticas resumidas --}}
        <div class="grid" style="margin-bottom: 20px;">
            <article style="background: transparent; color: rgb(117, 23, 23); text-align: center; border: 2px solid #ff6b6b; border-radius: 8px;">
                <h3 style="margin: 0;">{{ $serviciosImpagos->count() }}</h3>
                <p style="margin: 0;">Servicios Impagos</p>
                <h4 style="margin: 0;">${{ number_format($serviciosImpagos->sum('total'), 2) }}</h4>
            </article>
            <article style="background: transparent; color: rgb(117, 23, 23); text-align: center; border: 2px solid #b0ff6b; border-radius: 8px;">
                <h3 style="margin: 0;">{{ $serviciosPagos->count() }}</h3>
                <p style="margin: 0;">Servicios Pagos</p>
                <h4 style="margin: 0;">${{ number_format($serviciosPagos->sum('total'), 2) }}</h4>
            </article>
            <article style="background: transparent; color: rgb(117, 23, 23); text-align: center; border: 2px solid #6b9fff; border-radius: 8px;">
                <h3 style="margin: 0;">{{ $serviciosImpagos->count() + $serviciosPagos->count() }}</h3>
                <p style="margin: 0;">Total Servicios</p>
                <h4 style="margin: 0;">
                    ${{ number_format($serviciosImpagos->sum('total') + $serviciosPagos->sum('total'), 2) }}</h4>
            </article>
        </div>

        <details
            {{ request()->hasAny(['fecha_desde', 'fecha_hasta', 'importe_min', 'importe_max', 'nombre_servicio', 'nombre_empresa']) ? 'open' : '' }}>
            <summary
                style="cursor: pointer; padding: 12px 16px; background: #495057; color: rgb(117, 23, 23); border-radius: 8px; margin-bottom: 16px; font-weight: 500; list-style: none;">
                <strong>🔍 Filtros de Búsqueda</strong>
                @if (request()->hasAny([
                        'fecha_desde',
                        'fecha_hasta',
                        'importe_min',
                        'importe_max',
                        'nombre_servicio',
                        'nombre_empresa',
                    ]))
                    <span
                        style="background: #51cf66; padding: 4px 8px; border-radius: 4px; font-size: 12px; margin-left: 10px;">Filtros
                        activos</span>
                @endif
            </summary>
            <div style="padding: 20px; border: 2px solid #495057; border-radius: 8px; background: #315579ff;">
                <form method="GET"
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; align-items: flex-end;">
                    <div>
                        <label for="fecha_desde"><strong>📅 Desde</strong></label>
                        <input type="date" id="fecha_desde" name="fecha_desde" value="{{ $fechaDesde ?? '' }}"
                            style="width: 100%;">
                    </div>
                    <div>
                        <label for="fecha_hasta"><strong>📅 Hasta</strong></label>
                        <input type="date" id="fecha_hasta" name="fecha_hasta" value="{{ $fechaHasta ?? '' }}"
                            style="width: 100%;">
                    </div>
                    {{-- <div>
                        <label for="importe_min"><strong>💵 Importe mínimo</strong></label>
                        <input type="number" step="0.01" id="importe_min" name="importe_min"
                            value="{{ $importeMin ?? '' }}" placeholder="0.00" style="width: 100%;">
                    </div>
                    <div>
                        <label for="importe_max"><strong>💵 Importe máximo</strong></label>
                        <input type="number" step="0.01" id="importe_max" name="importe_max"
                            value="{{ $importeMax ?? '' }}" placeholder="Sin límite" style="width: 100%;">
                    </div> --}}
                    <div>
                        <label for="nombre_servicio"><strong>🛍️ Servicio</strong></label>
                        <input type="text" id="nombre_servicio" name="nombre_servicio"
                            value="{{ $nombreServicio ?? '' }}" placeholder="Nombre del servicio" style="width: 100%;">
                    </div>
                    <div>
                        <label for="nombre_empresa"><strong>🏢 Empresa</strong></label>
                        {{-- <input type="text" id="nombre_empresa" name="nombre_empresa" value="{{ $nombreEmpresa ?? '' }}"
                            placeholder="Nombre de la empresa" style="width: 100%;"> --}}

                        <select id="nombre_empresa" name="nombre_empresa" style="width: 100%;">
                            <option value="">Todo</option>
                            @if ($empresas)
                            @foreach ($empresas as $empresa)
                                <option value="{{ $empresa }}" {{ (isset($nombreEmpresa) && $nombreEmpresa == $empresa) ? 'selected' : '' }}>
                                    {{ $empresa }}
                                </option>
                            @endforeach
                            @endif
                        </select>
                    </div>
                    <div style="grid-column: 1 / -1; display: flex; gap: 10px; justify-content: center;">
                        <button type="submit"
                            style="background: #28a745; color: rgb(117, 23, 23); border: none; border-radius: 8px; padding: 12px 32px; font-size: 16px; cursor: pointer; font-weight: bold;">
                            🔍 Filtrar
                        </button>
                        <a href="{{ route('panelServicios') }}" role="button" class="secondary"
                            style="padding: 12px 32px; font-size: 16px; text-decoration: none;">
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
            @if ($serviciosImpagos->count() > 0)
                <span
                    style="background: #ff6b6b; color: rgb(117, 23, 23); padding: 4px 12px; border-radius: 20px; font-size: 16px; margin-left: 10px;">
                    {{ $serviciosImpagos->count() }}
                </span>
            @endif
        </h2>

        @if ($serviciosImpagos->count() > 0)
            <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-bottom: 20px;">
                @foreach ($serviciosImpagos as $s)
                    <article style="border: 2px solid #ff6b6b; border-radius: 8px;">
                        <header style="padding: 0; position: relative;">
                            <img src="{{ $s->imagenServicio }}"
                                style="width: 100%; height: 180px; object-fit: cover; border-radius: 6px 6px 0 0;
                                @if ($s->imagenServicio == '') display: none; @endif">
                            <div style="position: absolute; top: 10px; right: 10px; background: #ff6b6b; color: rgb(117, 23, 23); padding: 8px 12px; border-radius: 6px; font-weight: bold; font-size: 18px;">
                                ${{ number_format($s->total, 2) }}
                            </div>
                        </header>
                        <div style="padding: 16px; padding-top: 30px;">
                            <h3 style="margin: 0 0 12px 0; color: #356390;">{{ $s->nombreServicio }}</h3>
                            <p style="margin: 4px 0; color: #6c757d;"><strong>🏢 Empresa:</strong> {{ $s->nombreEmpresa }}</p>
                            <p style="margin: 4px 0; color: #6c757d;"><strong>📅 Fecha:</strong> {{ \Carbon\Carbon::parse($s->fechaCobro)->format('d/m/Y') }}</p>
                            <p style="margin: 4px 0; color: #6c757d;"><strong>📆 Período:</strong> {{ $s->periodo_servicio }}</p>
                            <p style="margin: 4px 0; color: #6c757d;">
                                <strong>⏰ Vencimiento:</strong> 
                                @if ($s->fecha_vencimiento)
                                    {{ \Carbon\Carbon::parse($s->fecha_vencimiento)->format('d/m/Y') }}
                                @else
                                    <span style="font-style: italic;">No especificada</span>
                                @endif
                            </p>
                            <hr style="margin: 12px 0; border: none; border-top: 1px solid #dee2e6;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span><strong>Cantidad:</strong> {{ $s->cantidadServicio }}</span>
                                <span><strong>Precio U.:</strong> ${{ number_format($s->precioServicio, 2) }}</span>
                            </div>
                        </div>

                        @if (!empty($s->MP_PUBLIC_KEY))
                            <footer style="padding: 16px; text-align: center; border-top: 1px solid #dee2e6;">
                                <a href="{{ route('pago.generar', $s->servicio_id) }}" role="button"
                                    style="background: #28a745; width: 100%; margin: 0; padding: 12px 20px; font-size: 16px;">
                                                                    
                                    <img width="50%" src="https://i.postimg.cc/3rq4kqvt/MP-RGB-HANDSHAKE-color-horizontal.png" alt="">

                                </a>
                            </footer>
                            
                        @else

                                <footer style="padding: 16px; text-align: center; border-top: 1px solid #dee2e6;">
                                    Alias Transferencia: <strong>{{ $s->aliasTranferencia ?? 'No disponible' }}</strong>
                                    <div style="margin-top: 10px;">
                                        <button type="button"
                                            class="copy-alias-btn"
                                            data-alias="{{ $s->aliasTranferencia }}"
                                            style="background: #356390; color: #fff; border: none; border-radius: 6px; padding: 6px 12px; cursor: pointer; font-size: 14px;">
                                            Copiar
                                        </button>
                                        <span class="copy-alias-feedback" style="margin-left: 8px; font-size: 12px; color: #6c757d;"></span>
                                    </div>
                                </footer>
                            
                        @endif
                    </article>
                @endforeach
            </div>

            <article style="background: transparent; border: 2px solid #ffc107; padding: 20px; text-align: center; margin-top: 20px;">
                <h3 style="margin: 0; color: #856404;">
                    💰 TOTAL A PAGAR: <span style="color: #ff6b6b; font-size: 24px;">${{ number_format($serviciosImpagos->sum('total'), 2) }}</span>
                </h3>
            </article>
        @else
            <article
                style="background: transparent; border: 2px solid #c3e6cb; color: #155724; text-align: center; padding: 30px;">
                <h3 style="margin: 0;">✅ ¡Excelente! No tienes servicios pendientes de pago</h3>
            </article>
        @endif

    </div>

    <div class="container" style="margin-top: 40px;">
        <h2 style="color: #51cf66; border-bottom: 3px solid #51cf66; padding-bottom: 10px; margin-bottom: 20px;">
            ✅ Servicios Pagos
            @if ($serviciosPagos->count() > 0)
                <span
                    style="background: #51cf66; color: rgb(117, 23, 23); padding: 4px 12px; border-radius: 20px; font-size: 16px; margin-left: 10px;">
                    {{ $serviciosPagos->count() }}
                </span>
            @endif
        </h2>

        @if ($serviciosPagos->count() > 0)
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
                                <td><img width="100%" src="{{ $s->imagenServicio }}" alt="{{ $s->nombreServicio }}"
                                        style="border-radius: 8px;"></td>
                                <td>{{ \Carbon\Carbon::parse($s->fechaCobro)->format('d/m/Y') }}</td>
                                <td><strong>{{ $s->nombreServicio }}</strong></td>
                                <td>{{ $s->nombreEmpresa }}</td>
                                <td style="text-align: center;">{{ $s->cantidadServicio }}</td>
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
                            <td style="text-align: right; font-size: 18px; color: #51cf66;">
                                ${{ number_format($serviciosPagos->sum('total'), 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </figure>
        @else
            <article
                style="background: transparent; border: 2px solid #173c62; color: #6c757d; text-align: center; padding: 30px;">
                <h3 style="margin: 0;">📋 No hay servicios pagados registrados</h3>
            </article>
        @endif

    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.copy-alias-btn').forEach(function (btn) {
                btn.addEventListener('click', async function () {
                    var alias = (btn.getAttribute('data-alias') || '').trim();
                    var feedback = btn.parentElement.querySelector('.copy-alias-feedback');

                    if (!alias) {
                        if (feedback) {
                            feedback.textContent = 'No disponible';
                        }
                        return;
                    }

                    try {
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            await navigator.clipboard.writeText(alias);
                        } else {
                            var tempInput = document.createElement('input');
                            tempInput.value = alias;
                            document.body.appendChild(tempInput);
                            tempInput.select();
                            document.execCommand('copy');
                            document.body.removeChild(tempInput);
                        }

                        if (feedback) {
                            feedback.textContent = 'Copiado';
                            setTimeout(function () {
                                feedback.textContent = '';
                            }, 1500);
                        }
                    } catch (e) {
                        if (feedback) {
                            feedback.textContent = 'Error al copiar';
                        }
                    }
                });
            });
        });
    </script>
@endpush

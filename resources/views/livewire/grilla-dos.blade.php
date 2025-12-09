

<div>


    <div class="container">
        <h1>Grilla Clientes</h1>
        {{-- <div style="display: flex; gap: 10px; margin-bottom: 16px;">
            <a href="{{route('ServiciosImpagos')}}" role="button" style="font-size: 20px; padding: 8px 24px; min-width: 120px; text-align: center;">Impagos</a>
            <a href="{{route('ServiciosPagos')}}" role="button" style="font-size: 20px; padding: 8px 24px; min-width: 120px; text-align: center;">Pagos</a>
        </div> --}}
    

        <fieldset role="group">

            {{-- <a href="{{route('NuevoCobro')}}" role="button" style="white-space: nowrap;">Agregar Servicio</a> --}}
            <input type="search" class="input" id="buscar" name="buscar" wire:model.live="buscar" placeholder="Buscar...">

        </fieldset>


    </div>

    <div class="container-fluid">
        <div class="overflow-auto" style="max-width: 100%; overflow-x: auto;">

            <figure>
                <table id="grilla" style="position: relative;">
                        <thead>
                            <tr>
                                <th scope="col" style="position: sticky; left: 0; background-color: var(--pico-background-color, white); z-index: 10; box-shadow: 2px 0 5px rgba(0,0,0,0.1);">Cliente</th>
                                <th scope="col" style="text-align: right; min-width: 100px;">Enero</th>
                                <th scope="col" style="text-align: right; min-width: 100px;">Febrero</th>
                                <th scope="col" style="text-align: right; min-width: 100px;">Marzo</th>
                                <th scope="col" style="text-align: right; min-width: 100px;">Abril</th>
                                <th scope="col" style="text-align: right; min-width: 100px;">Mayo</th>
                                <th scope="col" style="text-align: right; min-width: 100px;">Junio</th>
                                <th scope="col" style="text-align: right; min-width: 100px;">Julio</th>
                                <th scope="col" style="text-align: right; min-width: 100px;">Agosto</th>
                                <th scope="col" style="text-align: right; min-width: 100px;">Septiembre</th>
                                <th scope="col" style="text-align: right; min-width: 100px;">Octubre</th>
                                <th scope="col" style="text-align: right; min-width: 100px;">Noviembre</th>
                                <th scope="col" style="text-align: right; min-width: 100px;">Diciembre</th>
                                <th scope="col" style="text-align: center; min-width: 100px;">Cliente</th>

                            </tr>
                        </thead>
                        <tbody>
                                @foreach ($clientes as $c)
                                    <tr>
                                        <td style="position: sticky; left: 0; background-color: var(--pico-background-color, white); z-index: 5; box-shadow: 2px 0 5px rgba(0,0,0,0.1);">
                                            <a href="{{route('ServicioPagarBuscarCliente',['estado'=>'impago','buscar'=>$c->nombre])}}" data-tooltip="Ver Impagos">{{$c->nombre}}</a>
                                        </td>
                                        @if (!empty($c->datos))
                                            @foreach ($c->datos as $item)
                                                <td style="text-align: right;">
                                                    @if($item['importe_impago'] > 0)
                                                        <div style="color: #e3342f;">${{$item['importe_impago']}}</div>
                                                    @endif
                                                    @if($item['importe_pagado'] > 0)
                                                        <div style="color: #38c172;">${{$item['importe_pagado']}}</div>
                                                    @endif
                                                </td>
                                            @endforeach
                                        @endif

                                        <td style="text-align: center;">
                                            <a  href="{{route('ServicioPagarBuscarCliente',['estado'=>'impago','buscar'=>$c->nombre])}}" data-tooltip="Ver Impagos">{{$c->nombre}}</a>
                                        </td>
                                    </tr>
                                @endforeach

                            <tr>
                                <td style="position: sticky; left: 0; background-color: var(--pico-background-color, white); z-index: 5; box-shadow: 2px 0 5px rgba(0,0,0,0.1);"><h5 class="pico-color-red-450">Impago</h5></td>
                                @if (!empty($total))
                                    @foreach ($total as $item)
                                        <td style="text-align: right;"><h5 style="color: #e3342f;">${{$item['impago']}}</h5></td>
                                    @endforeach
                                @endif
                                <td style="text-align: center; background-color: var(--pico-background-color, white); box-shadow: 2px 0 5px rgba(0,0,0,0.1);"><h5 class="pico-color-red-450">Impago</h5></td>

                            </tr>
                            <tr>
                                <td style="position: sticky; left: 0; background-color: var(--pico-background-color, white); z-index: 5; box-shadow: 2px 0 5px rgba(0,0,0,0.1);"><h5 class="pico-color-jade-500">Pago</h5></td>
                                @if (!empty($total))
                                    @foreach ($total as $item)
                                        <td style="text-align: right;"><h5 style="color: #38c172;">${{$item['pago']}}</h5></td>
                                    @endforeach
                                @endif
                                <td style="text-align: center; background-color: var(--pico-background-color, white); box-shadow: 2px 0 5px rgba(0,0,0,0.1);"><h5 class="pico-color-jade-500">Pago</h5></td>

                            </tr>
                        </tbody>
                </table>
            </figure>


        </div>


        {{-- Paginación eliminada --}}
    </div>
</div>

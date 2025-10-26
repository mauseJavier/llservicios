

<div>
    <div class="container">
        <h1>Grilla Clientes</h1>
        {{-- <div style="display: flex; gap: 10px; margin-bottom: 16px;">
            <a href="{{route('ServiciosImpagos')}}" role="button" style="font-size: 20px; padding: 8px 24px; min-width: 120px; text-align: center;">Impagos</a>
            <a href="{{route('ServiciosPagos')}}" role="button" style="font-size: 20px; padding: 8px 24px; min-width: 120px; text-align: center;">Pagos</a>
        </div> --}}
        <nav>
            <ul>
                <li>
                            <div class="input-group">
                                    <input type="search" class="input" id="buscar" name="buscar" wire:model.live="buscar" placeholder="Buscar...">
                            </div>
                </li>

            </ul>
            <ul>
                <li>
                    <a href="{{route('NuevoCobro')}}" role="button">Agregar Servicio</a>
                </li>
            </ul>
        </nav>
    </div>

    <div class="container-fluid">
        <figure>
            <table id="grilla" >
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
                                    <td>
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
                                </tr>
                            @endforeach

                        <tr>
                            <td><h5 class="pico-color-red-450">Impago</h5></td>
                            @if (!empty($total))
                                @foreach ($total as $item)
                                    <td style="text-align: right;"><h5 style="color: #e3342f;">${{$item['impago']}}</h5></td>
                                @endforeach
                            @endif
                        </tr>
                        <tr>
                            <td><h5 class="pico-color-jade-500">Pago</h5></td>
                            @if (!empty($total))
                                @foreach ($total as $item)
                                    <td style="text-align: right;"><h5 style="color: #38c172;">${{$item['pago']}}</h5></td>
                                @endforeach
                            @endif
                        </tr>
                    </tbody>
            </table>
        </figure>

        {{-- Paginación eliminada --}}
    </div>
</div>

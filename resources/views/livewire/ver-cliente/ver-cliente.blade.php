<div>


    <div class="container">




        <div class="grid">

            <fieldset role="group">

                <a href="{{ route('Cliente.create') }}" role="button" data-tooltip="Nuevo Cliente">
                    Nuevo
                </a>
                <input type="text" placeholder="Buscar cliente..." wire:model.live="buscarCliente" />


            </fieldset>


        </div>

        <div class="overflow-auto">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>DNI</th>
                        <th>Domicilio</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($clientes as $cliente)
                        <tr>
                            <td>{{ $cliente->nombre }}</td>
                            <td>{{ $cliente->correo }}</td>
                            <td><a href="https://wa.me/+54{{ $cliente->telefono }}" target="_blank"
                                    rel="noopener noreferrer">{{ $cliente->telefono }}</a></td>
                            <td>{{ $cliente->dni }}</td>
                            <td>{{ $cliente->domicilio }}</td>
    
                            <th>
                                <strong>
                                    <a href="{{ route('DetalleCliente', ['clienteId' => $cliente->id]) }}"
                                        data-tooltip="Ver Detalle" style="margin-right: 10px;">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                    <a href="{{ route('Cliente.edit', ['Cliente' => $cliente->id]) }}"
                                        data-tooltip="Editar">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                </strong>
                            </th>
    
    
                        </tr>
                    @endforeach
                </tbody>
            </table>

        </div>



    </div>







</div>

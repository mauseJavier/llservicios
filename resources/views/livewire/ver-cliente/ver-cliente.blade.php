<div>


    <div class="container">



        
    <div class="grid">
    
            <fieldset role="group">
                
                <a href="{{route('Cliente.create')}}" role="button" data-tooltip="Nuevo Cliente">
                    Nuevo
                </a>
                <input type="text" placeholder="Buscar cliente..." wire:model.live="buscarCliente" />


            </fieldset>

            
    </div>

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
                        <td><a href="https://wa.me/+54{{$cliente->telefono}}" target="_blank" rel="noopener noreferrer">{{$cliente->telefono}}</a></td>
                        <td>{{ $cliente->dni }}</td>
                        <td>{{ $cliente->domicilio }}</td>

                    <th>                  
                        <strong><a href="{{route('Cliente.edit',['Cliente'=>$cliente->id])}}" data-tooltip="Editar">Editar</a></strong>
                    </th>


                    </tr>
                @endforeach
            </tbody>
        </table>
        
    </div>

    




    
</div>

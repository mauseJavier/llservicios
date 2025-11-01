<div>


    <div class="container">

        {{-- Mensajes de éxito y error --}}
        @if (session()->has('success'))
            <article style="background-color: #28a745; color: white; padding: 1rem; margin-bottom: 1rem; border-radius: 5px;">
                <strong>✓ Éxito:</strong> {{ session('success') }}
            </article>
        @endif

        @if (session()->has('error'))
            <article style="background-color: #dc3545; color: white; padding: 1rem; margin-bottom: 1rem; border-radius: 5px;">
                <strong>✗ Error:</strong> {{ session('error') }}
            </article>
        @endif


        <div class="grid">

            <fieldset role="group">

                
                <a href="{{ route('ImportarClientesCSV') }}" role="button" data-tooltip="Importar Clientes" style="background-color: green;">
                    Importar
                </a>

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
                                        data-tooltip="Editar" style="margin-right: 10px;">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <button 
                                        wire:click="confirmarEliminarCliente({{ $cliente->id }})"
                                        data-tooltip="Eliminar Cliente y sus Servicios"
                                        style="background-color: #dc3545; border: none; cursor: pointer; padding: 0.3rem 0.6rem; border-radius: 3px; color: white;">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </strong>
                            </th>
    
    
                        </tr>
                    @endforeach
                </tbody>
            </table>

        </div>



    </div>


    {{-- Modal de Confirmación --}}
    @if ($mostrarModalConfirmacion && $clienteAEliminar)
        <dialog open>
            <article>
                <header>
                    <h3>⚠️ Confirmar Eliminación</h3>
                </header>
                <p>
                    <strong>¿Está seguro de que desea eliminar al cliente?</strong>
                </p>
                <p>
                    <strong>Cliente:</strong> {{ $clienteAEliminar->nombre }}<br>
                    <strong>DNI:</strong> {{ $clienteAEliminar->dni }}
                </p>
                <div style="background-color: #fff3cd; padding: 1rem; border-radius: 5px; margin: 1rem 0;">
                    <strong style="color: #856404;">⚠️ ADVERTENCIA:</strong>
                    <p style="color: #856404; margin: 0.5rem 0 0 0;">
                        Esta acción eliminará de forma <strong>permanente</strong>:
                    </p>
                    <ul style="color: #856404; margin: 0.5rem 0;">
                        <li>El cliente</li>
                        <li>Todos los servicios pagos del cliente</li>
                        <li>Todos los servicios impagos del cliente</li>
                        <li>Todos los pagos registrados</li>
                        <li>Las vinculaciones del cliente con servicios</li>
                    </ul>
                    <p style="color: #856404; margin: 0.5rem 0 0 0;">
                        <strong>Esta acción NO se puede deshacer.</strong>
                    </p>
                </div>
                <footer style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button 
                        wire:click="cancelarEliminacion"
                        class="secondary">
                        Cancelar
                    </button>
                    <button 
                        wire:click="eliminarCliente"
                        style="background-color: #dc3545;">
                        Sí, Eliminar Definitivamente
                    </button>
                </footer>
            </article>
        </dialog>
    @endif




</div>

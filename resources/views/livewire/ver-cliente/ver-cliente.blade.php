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
                <input type="search" placeholder="Buscar cliente..." wire:model.live="buscarCliente" />
                <select wire:model.live="segmentoFiltro">
                    <option value="">Todos los segmentos</option>
                    @foreach ($segmentos as $seg)
                        <option value="{{ $seg->id }}">{{ $seg->nombre }}</option>
                    @endforeach
                </select>
            </fieldset>


        </div>

        <article style="display: flex; justify-content: flex-end;">

            <a href="{{ route('Cliente.create') }}" role="button" data-tooltip="Nuevo Cliente" style="margin-right: 10px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-square-plus"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 12h6" /><path d="M12 9v6" /><path d="M3 5a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-14z" /></svg>
            </a>

            <a href="{{ route('ImportarClientesCSV') }}" role="button" data-tooltip="Importar Clientes" style="background-color: green;">
                Importar
            </a>

            <a href="{{ route('ExportarClientes') }}" role="button" data-tooltip="Exportar CSV" style="background-color: #FFD43B; color: #333;">
                Exportar
            </a>

        </article>


        <div class="overflow-auto">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th style="min-width: 160px;">Servicios</th>
                        <th>Titular</th>
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
                            <td>
                                @if (($cliente->servicios_vinculados_count ?? 0) > 0)
                                    <a role="button" href="{{ route('DetalleCliente', ['clienteId' => $cliente->id]) }}" style="background-color: transparent; cursor: pointer; padding: 0.3rem 0.6rem; border-radius: 3px; color: rgb(170, 172, 183);"
                                
                                        data-tooltip="Ver Detalle" style="margin-right: 10px;">
                                        <i class="fas fa-eye"></i> {{ $cliente->nombre }}
                                    </a>
                                @else
                                    <a role="button" href="{{ route('DetalleCliente', ['clienteId' => $cliente->id]) }}" style="background-color: transparent; cursor: pointer; padding: 0.3rem 0.6rem; border-radius: 3px; color: rgb(228, 106, 106);"
                                
                                        data-tooltip="Ver Detalle" style="margin-right: 10px;">
                                        <i class="fas fa-eye"></i> {{ $cliente->nombre }}
                                    </a>
                                @endif


                            </td>
                            <td style="min-width: 160px;">
                                @if ($cliente->servicios->count() > 0)
                                    @foreach ($cliente->servicios as $servicio)
                                        <div style="border: 1px solid var(--pico-muted-border-color, #ccc); border-radius: 8px; padding: 2px 8px; margin-bottom: 4px; display: inline-block; white-space: nowrap;">{{ $servicio->nombre }} — ${{ number_format($servicio->precio, 2, ',', '.') }}</div>
                                    @endforeach
                                @else
                                    <span style="color: #999;">Sin servicios</span>
                                @endif
                            </td>
                            <td>{{ $cliente->titular }}</td>
                            <td>{{ $cliente->correo }}</td>
                            <td><a href="https://wa.me/+54{{ $cliente->telefono }}" target="_blank"
                                    rel="noopener noreferrer">{{ $cliente->telefono }}</a></td>
                            <td>{{ $cliente->dni }}</td>
                            <td>{{ $cliente->domicilio }}</td>
    
                            <th>

                                <div role="group">
                                    
                                        <button
                                            wire:click="verEventosCerradura({{ $cliente->id }})"
                                            data-tooltip="Ver registros de cerradura"
                                            style="background-color: transparent; cursor: pointer; padding: 0.3rem 0.6rem; border-radius: 3px; color: #FFD43B;">
                                            <i class="fas fa-door-closed"></i>
                                        </button>
                                        <a role="button" href="{{ route('Cliente.edit', ['Cliente' => $cliente->id]) }}" style="background-color: transparent; cursor: pointer; padding: 0.3rem 0.6rem; border-radius: 3px; color: white;"
                                    
                                            data-tooltip="Editar" style="margin-right: 10px;">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button 
                                            wire:click="confirmarEliminarCliente({{ $cliente->id }})"
                                            data-tooltip="Eliminar Cliente y sus Servicios"
                                            style="background-color: #dc3545; border: none; cursor: pointer; padding: 0.3rem 0.6rem; border-radius: 3px; color: white;">
                                            <i class="fas fa-trash"></i>
                                        </button>

                                </div>


                            </th>
    
    
                        </tr>
                    @endforeach
                </tbody>
            </table>

        </div>

        {{-- Controles de paginación --}}
        <div style="margin-top: 1rem;">
            {{ $clientes->links('vendor.pagination.custom') }}
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

    {{-- Modal de Registros de Cerradura --}}
    @if ($mostrarModalEventos && $clienteEventos)
        <dialog open>
            <article style="max-width: 900px; width: 95%;">
                <header>
                    <h3>🔓 Registros de cerradura</h3>
                </header>
                <p>
                    <strong>Cliente:</strong> {{ $clienteEventos->nombre }}
                    @if ($clienteEventos->dni)
                        — <strong>DNI:</strong> {{ $clienteEventos->dni }}
                    @endif
                </p>

                @if ($eventosCerradura->isEmpty())
                    <p style="color: #999;">Este cliente no tiene registros de cerradura.</p>
                @else
                    <div class="overflow-auto" style="max-height: 60vh;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Fecha y hora</th>
                                    <th>Cerradura</th>
                                    <th>Tipo</th>
                                    <th>Dispositivo</th>
                                    <th>Datos extra</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($eventosCerradura as $evento)
                                    <tr>
                                        <td>{{ $evento->fecha_hora?->format('d/m/Y H:i:s') }}</td>
                                        <td>{{ $evento->nombre_cerradura }}</td>
                                        <td>{{ $evento->tipo ?? '-' }}</td>
                                        <td>{{ $evento->dispositivo_id ?? '-' }}</td>
                                        <td>
                                            @if ($evento->datos_extra)
                                                <code style="font-size: 0.75rem;">{{ json_encode($evento->datos_extra, JSON_UNESCAPED_UNICODE) }}</code>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p style="margin-top: 0.5rem; color: #999; font-size: 0.85rem;">
                        Mostrando {{ $eventosCerradura->count() }} registro(s) más reciente(s).
                    </p>
                @endif

                <footer style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button wire:click="cerrarModalEventos" class="secondary">Cerrar</button>
                </footer>
            </article>
        </dialog>
    @endif




</div>

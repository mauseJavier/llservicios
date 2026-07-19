<div>
    @if ($cliente)
        <div class="container">
            <!-- Header con información del cliente -->
            <div class="grid">
                <div>
                    <hgroup>
                        <h1>{{ $cliente->nombre }}</h1>
                        <p>Detalles del cliente y servicios vinculados</p>
                    </hgroup>
                </div>
                <div style="text-align: right;">
                    <a href="{{ route('Cliente.index') }}" role="button" class="secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    <a href="{{ route('Cliente.edit', ['Cliente' => $cliente->id]) }}" role="button">
                        <i class="fas fa-edit"></i> Editar Cliente
                    </a>
                </div>
            </div>

            <!-- Información del Cliente -->
            <article>
                <header>
                    <strong><i class="fas fa-user"></i> Información del Cliente</strong>
                </header>
                <div class="grid">
                    <div>
                        <strong>DNI:</strong> {{ $cliente->dni ?? 'No registrado' }}
                    </div>
                    <div>
                        <strong>Correo:</strong> 
                        <a href="mailto:{{ $cliente->correo }}">{{ $cliente->correo }}</a>
                    </div>
                    <div>
                        <strong>Teléfono:</strong> 
                        @if($cliente->telefono)
                            <a href="https://wa.me/+54{{ $cliente->telefono }}" target="_blank" rel="noopener noreferrer">
                                <i class="fab fa-whatsapp"></i> {{ $cliente->telefono }}
                            </a>
                        @else
                            No registrado
                        @endif
                    </div>
                    <div>
                        <strong>Domicilio:</strong> {{ $cliente->domicilio ?? 'No registrado' }}
                    </div>
                </div>
            </article>

            <!-- Segmentos del Cliente -->
            <article>
                <header>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <strong><i class="fas fa-tags"></i> Segmentos</strong>
                        <button wire:click="abrirModalSegmentos" class="outline" style="padding: 0.25rem 0.75rem; font-size: 0.85em;">
                            <i class="fas fa-plus"></i> Gestionar
                        </button>
                    </div>
                </header>
                @if (count($segmentosCliente) > 0)
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                        @foreach ($segmentosCliente as $seg)
                            <span style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.3rem 0.7rem; border-radius: 1rem; font-size: 0.85em; background-color: {{ $seg->color ?? '#e5e7eb' }}20; border: 1px solid {{ $seg->color ?? '#d1d5db' }}; color: {{ $seg->color ?? '#374151' }};">
                                <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background-color: {{ $seg->color ?? '#6b7280' }};"></span>
                                <strong>{{ $seg->nombre }}</strong>
                                <button wire:click="toggleSegmento({{ $seg->id }})" wire:confirm="¿Quitar segmento '{{ $seg->nombre }}'?" style="border: none; background: none; cursor: pointer; padding: 0; font-size: 1.1em; line-height: 1; color: inherit; opacity: 0.6;" title="Quitar segmento">&times;</button>
                            </span>
                        @endforeach
                    </div>
                @else
                    <p><em>Este cliente no tiene segmentos asignados.</em></p>
                @endif
            </article>

            <!-- Resumen de Servicios -->
            <div class="grid">
                <article style="background-color: var(--pico-card-background-color);">
                    <header style="padding-bottom: 0;">
                        <h5><i class="fas fa-boxes"></i> Servicios Vinculados</h5>
                    </header>
                    <h2 style="margin: 0;">{{ count($serviciosVinculados) }}</h2>
                </article>

                <article style="background-color: var(--pico-card-background-color);">
                    <header style="padding-bottom: 0;">
                        <h5><i class="fas fa-exclamation-triangle"></i> Servicios Impagos</h5>
                    </header>
                    <h2 style="margin: 0; color: var(--pico-color-red-500);">{{ count($serviciosImpagos) }}</h2>
                </article>

                <article style="background-color: var(--pico-card-background-color);">
                    <header style="padding-bottom: 0;">
                        <h5><i class="fas fa-dollar-sign"></i> Total Adeudado</h5>
                    </header>
                    <h2 style="margin: 0; color: var(--pico-color-red-500);">
                        ${{ number_format($totalImpago, 2) }}
                    </h2>
                </article>

                <article style="background-color: var(--pico-card-background-color);">
                    <header style="padding-bottom: 0;">
                        <h5><i class="fas fa-check-circle"></i> Total Pagado (últimos)</h5>
                    </header>
                    <h2 style="margin: 0; color: var(--pico-color-green-500);">
                        ${{ number_format($totalPagado, 2) }}
                    </h2>
                </article>
            </div>

            <!-- Mensajes de éxito/error -->
            @if (session()->has('success'))
                <article style="background-color: var(--pico-color-green-50); border-left: 4px solid var(--pico-color-green-500);">
                    <i class="fas fa-check-circle" style="color: var(--pico-color-green-500);"></i>
                    {{ session('success') }}
                </article>
            @endif

            @if (session()->has('error'))
                <article style="background-color: var(--pico-color-red-50); border-left: 4px solid var(--pico-color-red-500);">
                    <i class="fas fa-exclamation-circle" style="color: var(--pico-color-red-500);"></i>
                    {{ session('error') }}
                </article>
            @endif

            <!-- Servicios Vinculados desde cliente_servicio -->
            <article>
                <header>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <strong><i class="fas fa-link"></i> Servicios Vinculados (Cliente-Servicio)</strong>
                        <button wire:click="abrirModalVincular" class="outline">
                            <i class="fas fa-plus"></i> Vincular Servicio
                        </button>
                    </div>
                </header>
                @if (count($serviciosVinculados) > 0)
                    <div class="overflow-auto">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Servicio</th>
                                    <th>Descripción</th>
                                    <th>Precio Unitario</th>
                                    <th>Cantidad</th>
                                    <th>Subtotal</th>
                                    <th>Periodicidad</th>
                                    <th>Vencimiento</th>
                                    <th>Fecha Vinculación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($serviciosVinculados as $servicio)
                                    <tr>
                                        <td>{{ $servicio->servicio_id }}</td>
                                        <td>
                                            <strong>{{ $servicio->servicio_nombre }}</strong>
                                        </td>
                                        <td>
                                            <small>{{ Str::limit($servicio->servicio_descripcion, 50) }}</small>
                                        </td>
                                        <td>${{ number_format($servicio->servicio_precio, 2) }}</td>
                                        <td>{{ $servicio->cantidad }}</td>
                                        <td>
                                            <strong>${{ number_format($servicio->subtotal, 2) }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge">{{ ucfirst($servicio->servicio_tiempo) }}</span>
                                        </td>
                                        <td>
                                            <small>{{ \Carbon\Carbon::parse($servicio->vencimiento)->format('d/m/Y H:i') }}</small>
                                        </td>
                                        <td>
                                            <small>{{ \Carbon\Carbon::parse($servicio->fecha_vinculacion)->format('d/m/Y') }}</small>
                                        </td>
                                        <td>
                                            <button 
                                                wire:click="desvincularServicio({{ $servicio->vinculo_id }})" 
                                                wire:confirm="¿Está seguro de que desea desvincular este servicio?"
                                                class="outline secondary"
                                                style="padding: 0.25rem 0.5rem; font-size: 0.85em;"
                                                data-tooltip="Desvincular servicio">
                                                <i class="fas fa-unlink"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p><em>No hay servicios vinculados a este cliente.</em></p>
                @endif
            </article>

            <!-- Servicios Impagos -->
            @if (count($serviciosImpagos) > 0)
                <article style="border-left: 4px solid var(--pico-color-red-500);">
                    <header>
                        <strong><i class="fas fa-exclamation-circle"></i> Servicios Impagos</strong>
                    </header>
                    <div class="overflow-auto">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Servicio</th>
                                    <th>Cantidad</th>
                                    <th>Precio</th>
                                    <th>Total</th>
                                    <th>Período</th>
                                    <th>Fecha Generación</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($serviciosImpagos as $impago)
                                    <tr>
                                        <td>{{ $impago->id }}</td>
                                        <td>{{ $impago->servicio_nombre }}</td>
                                        <td>{{ $impago->cantidad }}</td>
                                        <td>${{ number_format($impago->precio, 2) }}</td>
                                        <td>
                                            <strong style="color: var(--pico-color-red-500);">
                                                ${{ number_format($impago->total, 2) }}
                                            </strong>
                                        </td>
                                        <td>
                                            @if($impago->periodo_servicio)
                                                {{ \Carbon\Carbon::parse($impago->periodo_servicio)->format('m/Y') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            <small>{{ \Carbon\Carbon::parse($impago->fecha_creacion)->format('d/m/Y H:i') }}</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" style="text-align: right;"><strong>Total Adeudado:</strong></td>
                                    <td colspan="3">
                                        <strong style="color: var(--pico-color-red-500); font-size: 1.2em;">
                                            ${{ number_format($totalImpago, 2) }}
                                        </strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </article>
            @endif

            <!-- Últimos Servicios Pagados -->
            @if (count($serviciosPagos) > 0)
                <article style="border-left: 4px solid var(--pico-color-green-500);">
                    <header>
                        <strong><i class="fas fa-check-circle"></i> Últimos Servicios Pagados</strong>
                    </header>
                    <div class="overflow-auto">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Servicio</th>
                                    <th>Cantidad</th>
                                    <th>Precio</th>
                                    <th>Total</th>
                                    <th>Período</th>
                                    <th>Fecha Pago</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($serviciosPagos as $pago)
                                    <tr>
                                        <td>{{ $pago->id }}</td>
                                        <td>{{ $pago->servicio_nombre }}</td>
                                        <td>{{ $pago->cantidad }}</td>
                                        <td>${{ number_format($pago->precio, 2) }}</td>
                                        <td>
                                            <strong style="color: var(--pico-color-green-500);">
                                                ${{ number_format($pago->total, 2) }}
                                            </strong>
                                        </td>
                                        <td>
                                            @if($pago->periodo_servicio)
                                                {{ \Carbon\Carbon::parse($pago->periodo_servicio)->format('m/Y') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            <small>{{ \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y H:i') }}</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </article>
        @endif

        <!-- Modal para gestionar segmentos -->
        @if ($mostrarModalSegmentos)
            <dialog open>
                <article style="max-width: 500px; margin: 0 auto;">
                    <header>
                        <button aria-label="Close" rel="prev" wire:click="cerrarModalSegmentos" style="border: none; background: none; cursor: pointer; font-size: 1.5em;"></button>
                        <strong><i class="fas fa-tags"></i> Gestionar Segmentos de {{ $cliente->nombre }}</strong>
                    </header>

                    <div>
                        @if (count($segmentosCliente) > 0)
                            <p><strong>Segmentos asignados:</strong></p>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem;">
                                @foreach ($segmentosCliente as $seg)
                                    <span style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.3rem 0.7rem; border-radius: 1rem; font-size: 0.85em; background-color: {{ $seg->color ?? '#e5e7eb' }}20; border: 1px solid {{ $seg->color ?? '#d1d5db' }}; color: {{ $seg->color ?? '#374151' }};">
                                        <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background-color: {{ $seg->color ?? '#6b7280' }};"></span>
                                        {{ $seg->nombre }}
                                        <button wire:click="toggleSegmento({{ $seg->id }})" style="border: none; background: none; cursor: pointer; padding: 0; font-size: 1.1em; line-height: 1; color: inherit; opacity: 0.6;" title="Quitar">&times;</button>
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <p><em>No hay segmentos asignados.</em></p>
                        @endif

                        @if (count($todosSegmentos) > 0)
                            <hr>
                            <p><strong>Segmentos disponibles:</strong></p>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                @foreach ($todosSegmentos as $seg)
                                    <button wire:click="toggleSegmento({{ $seg->id }})" style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.3rem 0.7rem; border-radius: 1rem; font-size: 0.85em; cursor: pointer; border: 1px dashed {{ $seg->color ?? '#d1d5db' }}; background: transparent; color: {{ $seg->color ?? '#374151' }};">
                                        <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background-color: {{ $seg->color ?? '#6b7280' }};"></span>
                                        + {{ $seg->nombre }}
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <p><em>No hay más segmentos disponibles.
                                @if(count($segmentosCliente) > 0)
                                    El cliente ya está en todos los segmentos.
                                @else
                                    <br><a href="{{ route('segmentos') }}" target="_blank">Crear segmentos</a>
                                @endif
                            </em></p>
                        @endif
                    </div>

                    <footer style="display: flex; justify-content: flex-end;">
                        <button type="button" class="secondary" wire:click="cerrarModalSegmentos">Cerrar</button>
                    </footer>
                </article>
            </dialog>
        @endif

        <!-- Modal para vincular servicio -->
        @if ($mostrarModalVincular)
            <dialog open>
                <article style="max-width: 600px; margin: 0 auto;">
                    <header>
                        <button aria-label="Close" rel="prev" wire:click="cerrarModalVincular" style="border: none; background: none; cursor: pointer; font-size: 1.5em;"></button>
                        <strong><i class="fas fa-link"></i> Vincular Servicio a {{ $cliente->nombre }}</strong>
                    </header>

                    <div>
                        @if (session()->has('error'))
                            <div style="color: red; margin-bottom: 0.5rem;">
                                <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                            </div>
                        @endif

                        @error('servicioSeleccionado')
                            <div style="color: red; margin-bottom: 0.5rem;">
                                <i class="fas fa-exclamation-circle"></i> {{ $message }}
                            </div>
                        @enderror

                        <!-- Buscador de servicios -->
                        <div style="margin-bottom: 1rem;">
                            <label for="buscarServicio">Buscar servicio</label>
                            <input type="text" id="buscarServicio" wire:model.live="buscarServicio" placeholder="Escriba para buscar servicios..." style="margin-bottom: 0.5rem;">
                        </div>

                        <!-- Lista de servicios disponibles -->
                        <div style="margin-bottom: 1rem;">
                            <label>Seleccione un servicio</label>
                            @if (count($serviciosDisponibles) > 0)
                                <div style="display: flex; flex-direction: column; gap: 0.3rem; max-height: 200px; overflow-y: auto; border: 1px solid var(--pico-form-element-border-color); border-radius: var(--pico-border-radius); padding: 0.5rem;">
                                    @foreach ($serviciosDisponibles as $servicio)
                                        <button type="button" wire:click="$set('servicioSeleccionado', {{ $servicio->id }})" 
                                            style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem; border-radius: 0.3rem; cursor: pointer; border: 1px solid {{ $servicioSeleccionado == $servicio->id ? 'var(--pico-primary-background)' : 'transparent' }}; background-color: {{ $servicioSeleccionado == $servicio->id ? 'var(--pico-primary-background)' : 'transparent' }}; color: {{ $servicioSeleccionado == $servicio->id ? 'var(--pico-primary-inverse)' : 'inherit' }}; text-align: left; width: 100%;">
                                            <span>
                                                <strong>{{ $servicio->nombre }}</strong>
                                                @if($servicio->descripcion)
                                                    <br><small>{{ Str::limit($servicio->descripcion, 40) }}</small>
                                                @endif
                                            </span>
                                            <span style="text-align: right; white-space: nowrap;">
                                                <strong>${{ number_format($servicio->precio, 2) }}</strong>
                                                <br><small>{{ ucfirst($servicio->tiempo) }}</small>
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                            @else
                                <p><em>No hay servicios disponibles. 
                                    @if($buscarServicio)
                                        No se encontraron servicios con "{{ $buscarServicio }}".
                                    @else
                                        Todos los servicios ya están vinculados a este cliente.
                                    @endif
                                </em></p>
                            @endif
                        </div>

                        <!-- Campos de cantidad y vencimiento -->
                        <div class="grid" style="grid-template-columns: 1fr 1fr;">
                            <div>
                                <label for="cantidadVincular">Cantidad</label>
                                <input type="number" id="cantidadVincular" wire:model="cantidadVincular" min="0.5" step="0.5">
                                @error('cantidadVincular') <small style="color: red;">{{ $message }}</small> @enderror
                            </div>
                            <div>
                                <label for="vencimientoVincular">Fecha de Vencimiento</label>
                                <input type="datetime-local" id="vencimientoVincular" wire:model="vencimientoVincular">
                                @error('vencimientoVincular') <small style="color: red;">{{ $message }}</small> @enderror
                            </div>
                        </div>
                    </div>

                    <footer style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                        <button type="button" class="secondary" wire:click="cerrarModalVincular">Cancelar</button>
                        <button type="button" wire:click="vincularServicio">
                            <i class="fas fa-link"></i> Vincular Servicio
                        </button>
                    </footer>
                </article>
            </dialog>
        @endif

    </div>
    @endif

    <style>
        .badge {
            display: inline-block;
            padding: 0.25em 0.6em;
            font-size: 0.75em;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
            background-color: var(--pico-primary-background);
            color: var(--pico-primary-inverse);
        }
    
        .overflow-auto {
            overflow-x: auto;
        }
    
        article header h5 {
            margin: 0;
            font-size: 0.9em;
            font-weight: 400;
            opacity: 0.8;
        }

        /* Estilos para el modal */
        dialog {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            padding: 1rem;
        }

        dialog article {
            max-height: 90vh;
            overflow-y: auto;
            background-color: var(--pico-background-color);
        }

        /* Mejora para botones pequeños */
        button.outline.secondary {
            border-color: var(--pico-color-red-500);
            color: var(--pico-color-red-500);
        }

        button.outline.secondary:hover {
            background-color: var(--pico-color-red-500);
            color: white;
        }
    </style>
</div>


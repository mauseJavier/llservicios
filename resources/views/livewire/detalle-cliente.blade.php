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

        </div>

        <!-- Modal para vincular servicio -->
        @if ($mostrarModalVincular)
            <dialog open>
                <article style="max-width: 600px; margin: 0 auto;">
                    <header>
                        <button 
                            aria-label="Close" 
                            rel="prev" 
                            wire:click="cerrarModalVincular"
                            style="border: none; background: none; cursor: pointer; font-size: 1.5em;">
                        </button>
                        <strong><i class="fas fa-link"></i> Vincular Servicio a {{ $cliente->nombre }}</strong>
                    </header>
                    
                    <form wire:submit.prevent="vincularServicio">
                        <!-- Buscador de servicios -->
                        <label for="buscarServicio">
                            Buscar servicio
                            <input 
                                type="text" 
                                id="buscarServicio" 
                                wire:model.live="buscarServicio" 
                                placeholder="Buscar por nombre o descripción...">
                        </label>

                        <!-- Selector de servicio -->
                        <label for="servicioSeleccionado">
                            Servicio *
                            <select 
                                id="servicioSeleccionado" 
                                wire:model="servicioSeleccionado" 
                                required>
                                <option value="">-- Seleccione un servicio --</option>
                                @foreach ($serviciosDisponibles as $servicio)
                                    <option value="{{ $servicio->id }}">
                                        {{ $servicio->nombre }} - ${{ number_format($servicio->precio, 2) }} por {{ $servicio->tiempo }}
                                    </option>
                                @endforeach
                            </select>
                            @error('servicioSeleccionado') 
                                <small style="color: var(--pico-color-red-500);">{{ $message }}</small> 
                            @enderror
                        </label>

                        @if (empty($serviciosDisponibles) && !$buscarServicio)
                            <p><em>No hay servicios disponibles para vincular. Todos los servicios ya están vinculados a este cliente.</em></p>
                        @elseif (empty($serviciosDisponibles) && $buscarServicio)
                            <p><em>No se encontraron servicios con ese criterio de búsqueda.</em></p>
                        @endif

                        <div class="grid">
                            <!-- Cantidad -->
                            <label for="cantidadVincular">
                                Cantidad *
                                <input 
                                    type="number" 
                                    id="cantidadVincular" 
                                    wire:model="cantidadVincular" 
                                    min="0.5" 
                                    step="0.5" 
                                    required>
                                @error('cantidadVincular') 
                                    <small style="color: var(--pico-color-red-500);">{{ $message }}</small> 
                                @enderror
                            </label>

                            <!-- Vencimiento -->
                            <label for="vencimientoVincular">
                                Fecha de Vencimiento *
                                <input 
                                    type="datetime-local" 
                                    id="vencimientoVincular" 
                                    wire:model="vencimientoVincular" 
                                    required>
                                @error('vencimientoVincular') 
                                    <small style="color: var(--pico-color-red-500);">{{ $message }}</small> 
                                @enderror
                            </label>
                        </div>

                        <footer style="display: flex; justify-content: flex-end; gap: 1rem;">
                            <button 
                                type="button" 
                                class="secondary" 
                                wire:click="cerrarModalVincular">
                                Cancelar
                            </button>
                            <button 
                                type="submit" 
                                :disabled="!$servicioSeleccionado"
                                @if(empty($serviciosDisponibles)) disabled @endif>
                                <i class="fas fa-link"></i> Vincular Servicio
                            </button>
                        </footer>
                    </form>
                </article>
            </dialog>
        @endif

    @else
        <div class="container">
            <article>
                <p>No se pudo cargar la información del cliente.</p>
                <a href="{{ route('Cliente.index') }}" role="button">Volver al listado</a>
            </article>
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


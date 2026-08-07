<div class="container">
    <h1>Historial de Cierre de Caja</h1>

    <div style="display: flex; justify-content: flex-end; margin-bottom: 16px;">
        <a href="{{ route('cierre-caja') }}"
           style="background: #6c757d; color: white; padding: 10px 16px; border-radius: 8px; text-decoration: none;">
            <i class="fas fa-arrow-left"></i> Volver a Caja
        </a>
    </div>

    {{-- Filtros --}}
    <div class="card" style="padding: 20px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 8px;">
        <h3><i class="fas fa-filter"></i> Filtros</h3>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 10px;">
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Desde</label>
                <input type="date" wire:model.live="fechaDesde"
                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Hasta</label>
                <input type="date" wire:model.live="fechaHasta"
                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Movimiento</label>
                <select wire:model.live="tipoMovimiento"
                        style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                    <option value="">Todos</option>
                    <option value="inicio">Inicio</option>
                    <option value="cierre">Cierre</option>
                </select>
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Usuario</label>
                <select wire:model.live="usuarioId"
                        style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                    <option value="">Todos los usuarios</option>
                    @foreach($usuarios as $usuarioFiltro)
                        <option value="{{ $usuarioFiltro->id }}">{{ $usuarioFiltro->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div style="margin-top: 15px;">
            <button wire:click="limpiarFiltros"
                    style="background: #6c757d; color: white; border: none; padding: 10px 16px; border-radius: 8px; cursor: pointer;">
                <i class="fas fa-eraser"></i> Limpiar filtros
            </button>
        </div>
    </div>

    {{-- Registros --}}
    <div class="card" style="padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
        <h3><i class="fas fa-history"></i> Registros</h3>

        @if($registros->count() > 0)
            <div class="table-responsive">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Fecha</th>
                            <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Usuario</th>
                            <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Movimiento</th>
                            <th style="padding: 12px; border: 1px solid #ddd; text-align: right;">Importe</th>
                            <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Comentario</th>
                            <th style="padding: 12px; border: 1px solid #ddd; text-align: center;">PDF</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($registros as $registro)
                            <tr>
                                <td style="padding: 12px; border: 1px solid #ddd;">
                                    {{ $registro->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td style="padding: 12px; border: 1px solid #ddd;">
                                    {{ $registro->usuario_nombre }}
                                </td>
                                <td style="padding: 12px; border: 1px solid #ddd;">
                                    @if($registro->movimiento === 'inicio')
                                        <span style="color: #28a745; font-weight: bold;">
                                            <i class="fas fa-play"></i> INICIO
                                        </span>
                                    @else
                                        <span style="color: #dc3545; font-weight: bold;">
                                            <i class="fas fa-stop"></i> CIERRE
                                        </span>
                                    @endif
                                </td>
                                <td style="padding: 12px; border: 1px solid #ddd; text-align: right;">
                                    ${{ number_format($registro->importe, 2, ',', '.') }}
                                </td>
                                <td style="padding: 12px; border: 1px solid #ddd;">
                                    {{ $registro->comentario ?: '-' }}
                                </td>
                                <td style="padding: 12px; border: 1px solid #ddd; text-align: center; white-space: nowrap;">
                                    <button wire:click="generarPdf('a4', '{{ $registro->created_at->format('Y-m-d') }}', {{ $registro->usuario_id }})"
                                            style="background: #007BFF; color: white; border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 13px;">
                                        A4
                                    </button>
                                    <button wire:click="generarPdf('80mm', '{{ $registro->created_at->format('Y-m-d') }}', {{ $registro->usuario_id }})"
                                            style="background: #28a745; color: white; border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 13px;">
                                        80mm
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 20px;">
                {{ $registros->links('vendor.pagination.custom') }}
            </div>
        @else
            <p style="color: #6c757d; font-style: italic;">No hay movimientos registrados para los filtros seleccionados.</p>
        @endif
    </div>
</div>

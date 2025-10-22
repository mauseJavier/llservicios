<div class="container">
    <h1>Gestión de Caja</h1>

    {{-- Mensajes --}}
    @if (session()->has('message'))
        <div class="alert alert-success" style="background: #28a745; color: white; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger" style="background: #dc3545; color: white; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
            {{ session('error') }}
        </div>
    @endif

    {{-- Estado actual de la caja --}}
    <div class="card" style="padding: 20px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f8f9fa;">
        <h3>Estado Actual de la Caja</h3>
        
        @if($cajaActiva)
            <div style="color: #28a745; font-weight: bold; font-size: 18px; margin-bottom: 10px;">
                <i class="fas fa-unlock"></i> Caja ABIERTA
            </div>
            @if($ultimoInicio)
                <p><strong>Iniciada por:</strong> {{ $ultimoInicio->usuario_nombre }}</p>
                <p><strong>Fecha:</strong> {{ $ultimoInicio->created_at->format('d/m/Y H:i') }}</p>
                <p><strong>Importe inicial:</strong> ${{ number_format($ultimoInicio->importe, 2, ',', '.') }}</p>
                @if($ultimoInicio->comentario)
                    <p><strong>Comentario:</strong> {{ $ultimoInicio->comentario }}</p>
                @endif
            @endif
        @else
            <div style="color: #dc3545; font-weight: bold; font-size: 18px; margin-bottom: 10px;">
                <i class="fas fa-lock"></i> Caja CERRADA
            </div>
            @if($ultimoCierre)
                <p><strong>Cerrada por:</strong> {{ $ultimoCierre->usuario_nombre }}</p>
                <p><strong>Fecha:</strong> {{ $ultimoCierre->created_at->format('d/m/Y H:i') }}</p>
                <p><strong>Importe final:</strong> ${{ number_format($ultimoCierre->importe, 2, ',', '.') }}</p>
                @if($ultimoCierre->comentario)
                    <p><strong>Comentario:</strong> {{ $ultimoCierre->comentario }}</p>
                @endif
            @endif
        @endif
    </div>

    {{-- Resumen de movimientos del día --}}
    @if(!empty($calculoCaja))
        <div class="card" style="padding: 20px; margin-bottom: 20px; border: 1px solid #007BFF; border-radius: 8px; background: #f8f9ff;">
            <h3 style="color: #007BFF; margin-bottom: 20px;">
                <i class="fas fa-calculator"></i> Resumen del Día - {{ $resumenDia['fecha'] ?? '' }}
            </h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                {{-- Columna de Movimientos --}}
                <div style="background: white; padding: 15px; border-radius: 8px; border: 1px solid #e9ecef;">
                    <h4 style="color: #495057; margin-bottom: 15px; border-bottom: 2px solid #e9ecef; padding-bottom: 10px;">
                        📊 Movimientos del Día
                    </h4>
                    
                    <div style="margin-bottom: 12px;">
                        <span style="color: #dc3545;"><strong>Total Inicio de Caja:</strong></span>
                        <span style="float: right; font-weight: bold;">-${{ number_format($calculoCaja['inicio_caja'], 2, ',', '.') }}</span>
                        <div style="clear: both; font-size: 12px; color: #6c757d;">
                            {{ $resumenDia['inicio_registrado'] ? '✅ ' . $resumenDia['cantidad_inicios'] . ' registro(s)' : '❌ No registrado' }}
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 12px;">
                        <span style="color: #dc3545;"><strong>Total Pagos:</strong></span>
                        <span style="float: right; font-weight: bold;">-${{ number_format($calculoCaja['total_pagos'], 2, ',', '.') }}</span>
                        <div style="clear: both; font-size: 12px; color: #6c757d;">
                            {{ $resumenDia['total_movimientos_pagos'] }} movimiento(s)
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 12px;">
                        <span style="color: #28a745;"><strong>Total Cierre de Caja:</strong></span>
                        <span style="float: right; font-weight: bold;">+${{ number_format($calculoCaja['cierre_caja'], 2, ',', '.') }}</span>
                        <div style="clear: both; font-size: 12px; color: #6c757d;">
                            {{ $resumenDia['cierre_registrado'] ? '✅ ' . $resumenDia['cantidad_cierres'] . ' registro(s)' : '❌ No registrado' }}
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <span style="color: #28a745;"><strong>Total Gastos:</strong></span>
                        <span style="float: right; font-weight: bold;">+${{ number_format($calculoCaja['total_gastos'], 2, ',', '.') }}</span>
                        <div style="clear: both; font-size: 12px; color: #6c757d;">
                            {{ $resumenDia['total_movimientos_gastos'] }} movimiento(s)
                        </div>
                    </div>
                </div>
                
                {{-- Columna de Resultados --}}
                <div style="background: white; padding: 15px; border-radius: 8px; border: 1px solid #e9ecef;">
                    <h4 style="color: #495057; margin-bottom: 15px; border-bottom: 2px solid #e9ecef; padding-bottom: 10px;">
                        💰 Resultado Final
                    </h4>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                        <div style="text-align: center; margin-bottom: 10px;">
                            <strong>Fórmula de Cálculo:</strong>
                        </div>
                        <div style="font-family: monospace; text-align: center; color: #495057; font-size: 14px;">
                            (-Suma Inicios) + (-Suma Pagos) + (Suma Cierres) + (Suma Gastos)
                        </div>
                    </div>
                    
                    <div style="background: {{ $calculoCaja['calculo_final'] >= 0 ? '#d4edda' : '#f8d7da' }}; padding: 20px; border-radius: 8px; text-align: center; border: 2px solid {{ $calculoCaja['calculo_final'] >= 0 ? '#c3e6cb' : '#f5c6cb' }};">
                        <div style="font-size: 18px; font-weight: bold; color: {{ $calculoCaja['calculo_final'] >= 0 ? '#155724' : '#721c24' }}; margin-bottom: 10px;">
                            {{ $calculoCaja['calculo_final'] >= 0 ? '✅ RESULTADO POSITIVO' : '❌ RESULTADO NEGATIVO' }}
                        </div>
                        <div style="font-size: 24px; font-weight: bold; color: {{ $calculoCaja['calculo_final'] >= 0 ? '#155724' : '#721c24' }};">
                            ${{ number_format($calculoCaja['calculo_final'], 2, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Botones de acción --}}
    @if(!$mostrarFormulario)
        <div style="display: flex; gap: 10px; margin-bottom: 20px; align-items: center;">
            @if(!$cajaActiva)
                <button wire:click="iniciarCaja" class="btn-success" style="background: #28a745; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer;">
                    <i class="fas fa-play"></i> Iniciar Caja
                </button>
            @else
                <button wire:click="cerrarCaja" class="btn-danger" style="background: #dc3545; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer;">
                    <i class="fas fa-stop"></i> Cerrar Caja
                </button>
            @endif
            
            <button wire:click="calcularMovimientosCaja" class="btn-info" style="background: #17a2b8; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer;">
                <i class="fas fa-sync-alt"></i> Actualizar Cálculos
            </button>
            
            <button wire:click="generarPdfA4" class="btn-secondary" style="background: #6f42c1; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer;">
                <i class="fas fa-file-pdf"></i> PDF A4
            </button>
            
            <button wire:click="generarPdf80mm" class="btn-secondary" style="background: #fd7e14; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer;">
                <i class="fas fa-receipt"></i> Ticket 80mm
            </button>
        </div>
    @endif

    {{-- Formulario --}}
    @if($mostrarFormulario)
        <div class="card" style="padding: 20px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 8px;">
            <h3>
                @if($tipoMovimiento === 'inicio')
                    <i class="fas fa-play text-success"></i> Iniciar Caja
                @else
                    <i class="fas fa-stop text-danger"></i> Cerrar Caja
                @endif
            </h3>

            <form wire:submit.prevent="guardar">
                <div style="margin-bottom: 16px;">
                    <label for="importe">Importe *</label>
                    <input type="number" 
                           id="importe"
                           wire:model="importe" 
                           step="0.01" 
                           min="0"
                           placeholder="Ingrese el importe"
                           style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    @error('importe') 
                        <span style="color: #dc3545; font-size: 14px;">{{ $message }}</span> 
                    @enderror
                </div>

                <div style="margin-bottom: 16px;">
                    <label for="comentario">Comentario</label>
                    <textarea id="comentario"
                              wire:model="comentario" 
                              rows="3"
                              placeholder="Comentario opcional (máximo 500 caracteres)"
                              style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; resize: vertical;"></textarea>
                    @error('comentario') 
                        <span style="color: #dc3545; font-size: 14px;">{{ $message }}</span> 
                    @enderror
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" 
                            style="background: #007BFF; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer;">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <button type="button" 
                            wire:click="cancelar"
                            style="background: #6c757d; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer;">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Historial reciente --}}
    <div class="card" style="padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
        <h3>Historial Reciente</h3>
        
        @if($historialReciente && count($historialReciente) > 0)
            <div class="table-responsive">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Fecha</th>
                            <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Usuario</th>
                            <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Movimiento</th>
                            <th style="padding: 12px; border: 1px solid #ddd; text-align: right;">Importe</th>
                            <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Comentario</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($historialReciente as $registro)
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
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p style="color: #6c757d; font-style: italic;">No hay movimientos registrados.</p>
        @endif
    </div>
</div>

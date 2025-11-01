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
    <div class="card" style="padding: 20px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 8px; background: #32408dff;">
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
        <div class="card" style="padding: 20px; margin-bottom: 20px; border: 1px solid #007BFF; border-radius: 8px; background: #32408dff;">
            <h3 style="color: #007BFF; margin-bottom: 20px;">
                <i class="fas fa-calculator"></i> Resumen del Día - {{ $resumenDia['fecha'] ?? '' }}
            </h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                {{-- Columna de Movimientos --}}
                <div style="background: #000000ff; padding: 15px; border-radius: 8px; border: 1px solid #e9ecef;">
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
                <div style="background: black; padding: 15px; border-radius: 8px; border: 1px solid #345c83ff;">
                    <h4 style="color: #495057; margin-bottom: 15px; border-bottom: 2px solid #345c83ff; padding-bottom: 10px;">
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
        <div style="display: flex; gap: 10px; margin-bottom: 20px; align-items: center; flex-wrap: wrap;">
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
            
            <button wire:click="toggleResumenEmpresa" class="btn-info" style="background: #007bff; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer;">
                <i class="fas {{ $mostrarResumenEmpresa ? 'fa-eye-slash' : 'fa-building' }}"></i> 
                {{ $mostrarResumenEmpresa ? 'Ocultar' : 'Ver' }} Resumen Empresa
            </button>
            
            <button wire:click="generarPdfA4" class="btn-secondary" style="background: #6f42c1; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer;">
                <i class="fas fa-file-pdf"></i> PDF A4
            </button>
            
            <button wire:click="generarPdf80mm" class="btn-secondary" style="background: #fd7e14; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer;">
                <i class="fas fa-receipt"></i> Ticket 80mm
            </button>
        </div>
    @endif

    {{-- Resumen de todos los usuarios de la empresa --}}
    @if($mostrarResumenEmpresa && !empty($resumenEmpresa))
        <div class="card" style="padding: 20px; margin-bottom: 20px; border: 2px solid #007bff; border-radius: 8px; background: #1a2332;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="color: #007bff; margin: 0;">
                    <i class="fas fa-building"></i> Resumen Empresa - {{ $resumenEmpresa['fecha'] ?? '' }}
                </h3>
                <button wire:click="cargarResumenEmpresa" style="background: #17a2b8; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px;">
                    <i class="fas fa-sync-alt"></i> Actualizar
                </button>
            </div>

            {{-- Totales generales de la empresa --}}
            <div style="background: #0d1520; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 2px solid #28a745;">
                <h4 style="color: #28a745; margin-bottom: 15px; text-align: center;">
                    <i class="fas fa-chart-line"></i> TOTALES GENERALES DE LA EMPRESA
                </h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div style="background: #1a2332; padding: 15px; border-radius: 6px; text-align: center; border: 1px solid #dc3545;">
                        <div style="color: #dc3545; font-size: 14px; margin-bottom: 5px;">Total Inicios</div>
                        <div style="color: white; font-size: 20px; font-weight: bold;">-${{ number_format($resumenEmpresa['totales']['inicio_caja'], 2, ',', '.') }}</div>
                    </div>
                    <div style="background: #1a2332; padding: 15px; border-radius: 6px; text-align: center; border: 1px solid #dc3545;">
                        <div style="color: #dc3545; font-size: 14px; margin-bottom: 5px;">Total Pagos</div>
                        <div style="color: white; font-size: 20px; font-weight: bold;">-${{ number_format($resumenEmpresa['totales']['total_pagos'], 2, ',', '.') }}</div>
                    </div>
                    <div style="background: #1a2332; padding: 15px; border-radius: 6px; text-align: center; border: 1px solid #28a745;">
                        <div style="color: #28a745; font-size: 14px; margin-bottom: 5px;">Total Cierres</div>
                        <div style="color: white; font-size: 20px; font-weight: bold;">+${{ number_format($resumenEmpresa['totales']['cierre_caja'], 2, ',', '.') }}</div>
                    </div>
                    <div style="background: #1a2332; padding: 15px; border-radius: 6px; text-align: center; border: 1px solid #28a745;">
                        <div style="color: #28a745; font-size: 14px; margin-bottom: 5px;">Total Gastos</div>
                        <div style="color: white; font-size: 20px; font-weight: bold;">+${{ number_format($resumenEmpresa['totales']['total_gastos'], 2, ',', '.') }}</div>
                    </div>
                    <div style="background: {{ $resumenEmpresa['totales']['calculo_final'] >= 0 ? '#155724' : '#721c24' }}; padding: 15px; border-radius: 6px; text-align: center; border: 2px solid {{ $resumenEmpresa['totales']['calculo_final'] >= 0 ? '#28a745' : '#dc3545' }}; grid-column: span 4;">
                        <div style="color: white; font-size: 16px; margin-bottom: 5px; font-weight: bold;">
                            {{ $resumenEmpresa['totales']['calculo_final'] >= 0 ? '✅ RESULTADO FINAL EMPRESA' : '❌ RESULTADO FINAL EMPRESA' }}
                        </div>
                        <div style="color: white; font-size: 28px; font-weight: bold;">${{ number_format($resumenEmpresa['totales']['calculo_final'], 2, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            {{-- Tabla de usuarios --}}
            @if(count($resumenEmpresa['usuarios']) > 0)
                <div style="margin-top: 20px;">
                    <h4 style="color: #6c757d; margin-bottom: 15px;">
                        <i class="fas fa-users"></i> Detalle por Usuario ({{ $resumenEmpresa['cantidad_usuarios'] }} usuario(s) con movimientos)
                    </h4>
                    <div class="table-responsive" style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                            <thead>
                                <tr style="background: #0d1520;">
                                    <th style="padding: 12px; border: 1px solid #345c83; text-align: left; color: #17a2b8;">Usuario</th>
                                    <th style="padding: 12px; border: 1px solid #345c83; text-align: center; color: #dc3545;">Inicios</th>
                                    <th style="padding: 12px; border: 1px solid #345c83; text-align: center; color: #dc3545;">Pagos</th>
                                    <th style="padding: 12px; border: 1px solid #345c83; text-align: center; color: #28a745;">Cierres</th>
                                    <th style="padding: 12px; border: 1px solid #345c83; text-align: center; color: #28a745;">Gastos</th>
                                    <th style="padding: 12px; border: 1px solid #345c83; text-align: center; color: #ffc107;">Resultado</th>
                                    <th style="padding: 12px; border: 1px solid #345c83; text-align: center; color: #6c757d;">Movimientos</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($resumenEmpresa['usuarios'] as $usuarioData)
                                    <tr style="background: #1a2332;">
                                        <td style="padding: 10px; border: 1px solid #345c83; font-weight: bold;">
                                            <i class="fas fa-user"></i> {{ $usuarioData['usuario_nombre'] }}
                                        </td>
                                        <td style="padding: 10px; border: 1px solid #345c83; text-align: center;">
                                            <div style="color: #dc3545; font-weight: bold;">-${{ number_format($usuarioData['inicio_caja'], 2, ',', '.') }}</div>
                                            <small style="color: #6c757d;">({{ $usuarioData['cantidad_inicios'] }})</small>
                                        </td>
                                        <td style="padding: 10px; border: 1px solid #345c83; text-align: center;">
                                            <div style="color: #dc3545; font-weight: bold;">-${{ number_format($usuarioData['total_pagos'], 2, ',', '.') }}</div>
                                            <small style="color: #6c757d;">({{ $usuarioData['cantidad_pagos'] }})</small>
                                        </td>
                                        <td style="padding: 10px; border: 1px solid #345c83; text-align: center;">
                                            <div style="color: #28a745; font-weight: bold;">+${{ number_format($usuarioData['cierre_caja'], 2, ',', '.') }}</div>
                                            <small style="color: #6c757d;">({{ $usuarioData['cantidad_cierres'] }})</small>
                                        </td>
                                        <td style="padding: 10px; border: 1px solid #345c83; text-align: center;">
                                            <div style="color: #28a745; font-weight: bold;">+${{ number_format($usuarioData['total_gastos'], 2, ',', '.') }}</div>
                                            <small style="color: #6c757d;">({{ $usuarioData['cantidad_gastos'] }})</small>
                                        </td>
                                        <td style="padding: 10px; border: 1px solid #345c83; text-align: center; background: {{ $usuarioData['calculo_final'] >= 0 ? '#155724' : '#721c24' }};">
                                            <div style="color: white; font-weight: bold; font-size: 15px;">
                                                ${{ number_format($usuarioData['calculo_final'], 2, ',', '.') }}
                                            </div>
                                        </td>
                                        <td style="padding: 10px; border: 1px solid #345c83; text-align: center;">
                                            <small style="color: #6c757d;">
                                                {{ $usuarioData['cantidad_inicios'] + $usuarioData['cantidad_cierres'] + $usuarioData['cantidad_pagos'] + $usuarioData['cantidad_gastos'] }} total
                                            </small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot style="background: #0d1520; font-weight: bold;">
                                <tr>
                                    <td style="padding: 12px; border: 1px solid #345c83; text-align: right;" colspan="1">
                                        <strong style="color: #ffc107;">TOTALES:</strong>
                                    </td>
                                    <td style="padding: 12px; border: 1px solid #345c83; text-align: center; color: #dc3545;">
                                        -${{ number_format($resumenEmpresa['totales']['inicio_caja'], 2, ',', '.') }}
                                    </td>
                                    <td style="padding: 12px; border: 1px solid #345c83; text-align: center; color: #dc3545;">
                                        -${{ number_format($resumenEmpresa['totales']['total_pagos'], 2, ',', '.') }}
                                    </td>
                                    <td style="padding: 12px; border: 1px solid #345c83; text-align: center; color: #28a745;">
                                        +${{ number_format($resumenEmpresa['totales']['cierre_caja'], 2, ',', '.') }}
                                    </td>
                                    <td style="padding: 12px; border: 1px solid #345c83; text-align: center; color: #28a745;">
                                        +${{ number_format($resumenEmpresa['totales']['total_gastos'], 2, ',', '.') }}
                                    </td>
                                    <td style="padding: 12px; border: 1px solid #345c83; text-align: center; background: {{ $resumenEmpresa['totales']['calculo_final'] >= 0 ? '#28a745' : '#dc3545' }}; color: white; font-size: 16px;">
                                        ${{ number_format($resumenEmpresa['totales']['calculo_final'], 2, ',', '.') }}
                                    </td>
                                    <td style="padding: 12px; border: 1px solid #345c83;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @else
                <p style="color: #6c757d; font-style: italic; text-align: center; padding: 20px;">
                    No hay movimientos registrados para ningún usuario de la empresa en la fecha actual.
                </p>
            @endif

            {{-- Leyenda --}}
            <div style="margin-top: 20px; padding: 15px; background: #0d1520; border-radius: 8px; border: 1px solid #345c83;">
                <h5 style="color: #6c757d; margin-bottom: 10px;"><i class="fas fa-info-circle"></i> Leyenda:</h5>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; font-size: 13px; color: #adb5bd;">
                    <div><span style="color: #dc3545;">●</span> Valores en rojo: Salidas de efectivo</div>
                    <div><span style="color: #28a745;">●</span> Valores en verde: Entradas de efectivo</div>
                    <div><span style="color: #6c757d;">●</span> Números entre paréntesis: Cantidad de movimientos</div>
                    <div><span style="color: #ffc107;">●</span> Resultado: (-Inicios) + (-Pagos) + (Cierres) + (Gastos)</div>
                </div>
            </div>
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

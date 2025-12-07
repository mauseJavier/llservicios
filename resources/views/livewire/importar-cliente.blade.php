<div>
    <div class="container">
        <!-- Header -->
        <div class="grid">
            <div>
                <hgroup>
                    <h1><i class="fas fa-file-import"></i> Importar Clientes</h1>
                    <p>Importa múltiples clientes desde un archivo CSV</p>
                </hgroup>
            </div>
            <div style="text-align: right;">
                <a href="{{ route('Cliente.index') }}" role="button" class="secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <!-- Instrucciones -->
        <article>
            <header>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <strong><i class="fas fa-info-circle"></i> Instrucciones de Uso</strong>
                    <button wire:click="toggleInstrucciones" class="outline secondary" style="padding: 0.25rem 0.75rem; margin: 0;">
                        <i class="fas fa-{{ $mostrarInstrucciones ? 'chevron-up' : 'chevron-down' }}"></i>
                    </button>
                </div>
            </header>
            
            @if($mostrarInstrucciones)
                <div>
                    <h4>Formato del archivo CSV</h4>
                    <p>El archivo CSV debe contener los siguientes campos en el orden especificado:</p>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Campo</th>
                                <th>Requerido</th>
                                <th>Descripción</th>
                                <th>Ejemplo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>nombre</code></td>
                                <td><mark>Sí</mark></td>
                                <td>Nombre completo del cliente</td>
                                <td>Juan Pérez</td>
                            </tr>
                            <tr>
                                <td><code>correo</code></td>
                                <td><mark>Sí</mark></td>
                                <td>Correo electrónico válido</td>
                                <td>juan.perez@email.com</td>
                            </tr>
                            <tr>
                                <td><code>telefono</code></td>
                                <td>No</td>
                                <td>Número de teléfono (sin código de país)</td>
                                <td>3516123456</td>
                            </tr>
                            <tr>
                                <td><code>dni</code></td>
                                <td>No</td>
                                <td>DNI del cliente (7-8 dígitos)</td>
                                <td>12345678</td>
                            </tr>
                            <tr>
                                <td><code>domicilio</code></td>
                                <td>No</td>
                                <td>Dirección del cliente</td>
                                <td>Av. Siempre Viva 123</td>
                            </tr>
                        </tbody>
                    </table>

                    <h4>Comportamiento de la importación</h4>
                    <ul>
                        <li><strong>Clientes duplicados:</strong> Si un cliente ya existe (por DNI o nombre), se actualizarán sus datos.</li>
                        <li><strong>Vinculación automática:</strong> Los clientes se vincularán automáticamente a tu empresa.</li>
                        <li><strong>Validaciones:</strong> Los datos incorrectos serán reportados en el resumen de errores.</li>
                        <li><strong>Formato:</strong> El archivo debe ser CSV (valores separados por comas).</li>
                        <li><strong>Límite:</strong> Tamaño máximo del archivo: 2MB.</li>
                    </ul>

                    <h4>Ejemplo de archivo CSV</h4>
                    <pre><code>nombre,correo,telefono,dni,domicilio
Juan Pérez,juan.perez@email.com,3516123456,12345678,Av. Siempre Viva 123
María González,maria.gonzalez@email.com,3517654321,87654321,Calle Falsa 456</code></pre>

                    <div style="margin-top: 1rem;">
                        <button wire:click="descargarPlantilla" type="button" class="outline">
                            <i class="fas fa-download"></i> Descargar Plantilla CSV
                        </button>
                    </div>
                </div>
            @endif
        </article>

        <!-- Formulario de importación -->
        <article>
            <header>
                <strong><i class="fas fa-upload"></i> Seleccionar Archivo</strong>
            </header>

            @if (session()->has('success'))
                <div style="padding: 1rem; background-color: var(--pico-color-green-100); color: var(--pico-color-green-900); border-radius: 0.5rem; margin-bottom: 1rem;">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                </div>
            @endif

            @if (session()->has('warning'))
                <div style="padding: 1rem; background-color: var(--pico-color-yellow-100); color: var(--pico-color-yellow-900); border-radius: 0.5rem; margin-bottom: 1rem;">
                    <i class="fas fa-exclamation-triangle"></i> {{ session('warning') }}
                </div>
            @endif

            @if (session()->has('error'))
                <div style="padding: 1rem; background-color: var(--pico-color-red-100); color: var(--pico-color-red-900); border-radius: 0.5rem; margin-bottom: 1rem;">
                    <i class="fas fa-times-circle"></i> {{ session('error') }}
                </div>
            @endif

            <form wire:submit.prevent="importarClientes">
                <div>
                    <label for="archivoCSV">
                        <strong>Archivo CSV</strong>
                        <small>Seleccione un archivo .csv o .txt (máximo 2MB)</small>
                    </label>
                    <input type="file" 
                           id="archivoCSV" 
                           wire:model="archivoCSV" 
                           accept=".csv,.txt"
                           @if($procesando) disabled @endif>
                    @error('archivoCSV')
                        <small style="color: var(--pico-color-red-500);">{{ $message }}</small>
                    @enderror
                </div>

                @if($archivoCSV && !$procesando)
                    <div style="padding: 0.75rem; background-color: var(--pico-card-background-color); border-radius: 0.5rem; margin-bottom: 1rem;">
                        <i class="fas fa-file-csv"></i> <strong>{{ $archivoCSV->getClientOriginalName() }}</strong>
                        <small>({{ number_format($archivoCSV->getSize() / 1024, 2) }} KB)</small>
                    </div>
                @endif

                <div class="grid">
                    <button type="submit" @if($procesando || !$archivoCSV) disabled @endif>
                        @if($procesando)
                            <i class="fas fa-spinner fa-spin"></i> Procesando...
                        @else
                            <i class="fas fa-file-import"></i> Importar Clientes
                        @endif
                    </button>

                    @if($archivoCSV && !$procesando)
                        <button type="button" wire:click="limpiar" class="secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    @endif
                </div>
            </form>
        </article>

        <!-- Resumen de importación -->
        @if($resumen)
            <article>
                <header>
                    <strong><i class="fas fa-chart-bar"></i> Resumen de Importación</strong>
                </header>

                <div class="grid">
                    <div style="text-align: center; padding: 1rem; background-color: var(--pico-card-background-color); border-radius: 0.5rem;">
                        <h2 style="margin: 0; color: var(--pico-primary);">{{ $resumen['total_filas'] }}</h2>
                        <small>Total filas procesadas</small>
                    </div>

                    <div style="text-align: center; padding: 1rem; background-color: var(--pico-card-background-color); border-radius: 0.5rem;">
                        <h2 style="margin: 0; color: var(--pico-color-green-500);">{{ $resumen['creados'] }}</h2>
                        <small>Clientes creados</small>
                    </div>

                    <div style="text-align: center; padding: 1rem; background-color: var(--pico-card-background-color); border-radius: 0.5rem;">
                        <h2 style="margin: 0; color: var(--pico-color-blue-500);">{{ $resumen['actualizados'] }}</h2>
                        <small>Clientes actualizados</small>
                    </div>

                    <div style="text-align: center; padding: 1rem; background-color: var(--pico-card-background-color); border-radius: 0.5rem;">
                        <h2 style="margin: 0; color: var(--pico-color-red-500);">{{ $resumen['errores'] }}</h2>
                        <small>Errores</small>
                    </div>

                    <div style="text-align: center; padding: 1rem; background-color: var(--pico-card-background-color); border-radius: 0.5rem;">
                        <h2 style="margin: 0; color: var(--pico-color-yellow-500);">{{ $resumen['omitidos'] }}</h2>
                        <small>Filas omitidas</small>
                    </div>
                </div>

                @if($resumen['creados'] > 0 || $resumen['actualizados'] > 0)
                    <div style="padding: 1rem; background-color: var(--pico-color-green-100); color: var(--pico-color-green-900); border-radius: 0.5rem; margin-top: 1rem;">
                        <i class="fas fa-check-circle"></i> 
                        <strong>¡Importación exitosa!</strong>
                        <p style="margin: 0.5rem 0 0 0;">
                            Se {{ $resumen['creados'] > 0 ? 'crearon ' . $resumen['creados'] . ' cliente(s)' : '' }}
                            {{ $resumen['creados'] > 0 && $resumen['actualizados'] > 0 ? ' y ' : '' }}
                            {{ $resumen['actualizados'] > 0 ? 'actualizaron ' . $resumen['actualizados'] . ' cliente(s)' : '' }}
                            exitosamente.
                        </p>
                    </div>
                @endif

                @if(count($errores) > 0)
                    <details style="margin-top: 1rem;">
                        <summary style="cursor: pointer; color: var(--pico-color-red-500);">
                            <i class="fas fa-exclamation-circle"></i> Ver errores ({{ count($errores) }})
                        </summary>
                        <div style="margin-top: 1rem; max-height: 400px; overflow-y: auto;">
                            @foreach($errores as $error)
                                <div style="padding: 1rem; background-color: var(--pico-color-red-100); border-left: 4px solid var(--pico-color-red-500); margin-bottom: 1rem;">
                                    <strong>Línea {{ $error['linea'] }}:</strong>
                                    <p style="margin: 0.5rem 0;">
                                        <strong>Datos:</strong> {{ implode(', ', array_filter($error['datos'])) }}
                                    </p>
                                    <ul style="margin: 0.5rem 0 0 0;">
                                        @foreach($error['mensajes'] as $mensaje)
                                            <li>{{ $mensaje }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endif

                <div style="margin-top: 1rem;">
                    <button wire:click="limpiar" type="button" class="outline">
                        <i class="fas fa-redo"></i> Importar Otro Archivo
                    </button>
                    <a href="{{ route('Cliente.index') }}" role="button">
                        <i class="fas fa-users"></i> Ver Clientes
                    </a>
                </div>
            </article>
        @endif
    </div>
</div>

<div class="container">
    {{-- Mensajes de éxito y error --}}
    @if($successMessage)
        <article aria-live="polite">
            <header><strong>Éxito</strong></header>
            <p>{{ $successMessage }}</p>
        </article>
    @endif

    @if($errorMessage)
        <article aria-live="polite">
            <header><strong>Error</strong></header>
            <p>{{ $errorMessage }}</p>
        </article>
    @endif

    {{-- Estado de certificados AFIP --}}
    <article>
        <header>
            <strong>Facturación Electrónica AFIP</strong>
        </header>
        
        <div class="grid">
            <div>
                @if($certificadosValidos)
                    <small><strong>Certificados AFIP válidos</strong></small>
                @else
                    <small><strong>Certificados AFIP no válidos</strong></small>
                    @if($certificadosError)
                        <small>{{ $certificadosError }}</small>
                    @endif
                @endif
            </div>

            <div style="text-align: right;">
                @if($pago && $pago->tieneFacturaAfip())
                    {{-- Ya tiene factura --}}
                    <div style="display: inline-flex; gap: .5rem; flex-wrap: wrap; justify-content: flex-end;">
                        <small><strong>Facturado</strong></small>
                        <button wire:click="descargarFacturaPDF">Ver Factura PDF</button>
                        <button wire:click="descargarFacturaPDF80" class="secondary">Factura PDF 80mm</button>
                    </div>
                @else
                    {{-- Botón para facturar --}}
                    <button 
                        wire:click="openFacturarModal" 
                        @if(!$certificadosValidos) disabled @endif
                    >
                        Generar Factura AFIP
                    </button>
                @endif
            </div>
        </div>

            {{-- Mostrar información de factura si existe --}}
        @if($pago && $pago->tieneFacturaAfip())
            <hr>
            <div class="grid">
                <div>
                    <small>CAE</small>
                    <div><strong>{{ $pago->afip_cae }}</strong></div>
                </div>
                <div>
                    <small>Vencimiento CAE</small>
                    <div><strong>{{ \Carbon\Carbon::parse($pago->afip_cae_vencimiento)->format('d/m/Y') }}</strong></div>
                </div>
                <div>
                    <small>Tipo Comprobante</small>
                    <div><strong>{{ $pago->tipo_comprobante_nombre }}</strong></div>
                </div>
                <div>
                    <small>Número Comprobante</small>
                    <div><strong>{{ str_pad($pago->afip_punto_venta, 5, '0', STR_PAD_LEFT) }}-{{ str_pad($pago->afip_numero_comprobante, 8, '0', STR_PAD_LEFT) }}</strong></div>
                </div>
            </div>
        @endif
    </article>

    {{-- Modal de Facturación --}}
    @if($showFacturarModal)
        <dialog open>
            <article>
                <header>
                    <strong>Generar Factura AFIP</strong>
                    <button type="button" class="secondary" wire:click="cerrarModal">Cerrar</button>
                </header>
                
                @if($pago)
                    {{-- Resumen del pago --}}
                    <article>
                        <header><strong>Datos del Pago</strong></header>
                        <div class="grid">
                            <div>
                                <small>Cliente</small>
                                <div><strong>{{ $pago->servicioPagar->cliente->nombre ?? 'N/A' }}</strong></div>
                            </div>
                            <div>
                                <small>DNI Cliente</small>
                                <div><strong>{{ $pago->servicioPagar->cliente->dni ?? 'N/A' }}</strong></div>
                            </div>
                        </div>
                        
                    </article>
                    
                    <article>
                        <div class="grid">
    
                            <div>
                                <small>Servicio</small>
                                <div><strong>{{ $pago->servicioPagar->servicio->nombre ?? 'N/A' }}</strong></div>
                            </div>
                            <div>
                                <small>Importe</small>
                                <div><strong>${{ number_format($pago->total, 2) }}</strong></div>
                            </div>
                            <div>
                                <small>Fecha</small>
                                <div><strong>{{ \Carbon\Carbon::parse($pago->created_at)->format('d/m/Y H:i') }}</strong></div>
                            </div>
    
                        </div>

                    </article>

                    {{-- Formulario de facturación --}}
                    <form wire:submit.prevent="generarFactura">
                        <label for="tipoComprobante">Tipo de Comprobante</label>
                        <select 
                            wire:model="tipoComprobante" 
                            id="tipoComprobante" 
                            @error('tipoComprobante') aria-invalid="true" @enderror
                            @if($loading) disabled @endif
                        >
                            <option value="">Seleccionar...</option>
                            @foreach($tiposComprobantes as $codigo => $nombre)
                                <option value="{{ $codigo }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                        @error('tipoComprobante') 
                            <small>{{ $message }}</small>
                        @enderror

                        <label for="tipoDocumentoReceptor">Tipo de Documento (Receptor)</label>
                        <select 
                            wire:model="tipoDocumentoReceptor" 
                            id="tipoDocumentoReceptor" 
                            @error('tipoDocumentoReceptor') aria-invalid="true" @enderror
                            @if($loading) disabled @endif
                        >
                            <option value="">Seleccionar...</option>
                            @foreach($tiposDocumentosComunes as $codigo => $nombre)
                                <option value="{{ $codigo }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                        @error('tipoDocumentoReceptor') 
                            <small>{{ $message }}</small>
                        @enderror

                        <label for="condicionIvaReceptorId">Condición frente al IVA (Receptor)</label>
                        <select 
                            wire:model="condicionIvaReceptorId" 
                            id="condicionIvaReceptorId" 
                            @error('condicionIvaReceptorId') aria-invalid="true" @enderror
                            @if($loading) disabled @endif
                        >
                            @foreach($tiposContribuyentes as $codigo => $info)
                                <option value="{{ $codigo }}">{{ $info['Desc'] }} ({{ $info['Cmp_Clase'] }})</option>
                            @endforeach
                        </select>
                        @error('condicionIvaReceptorId') 
                            <small>{{ $message }}</small>
                        @enderror

                        <label for="puntoVenta">Punto de Venta</label>
                        <input 
                            wire:model="puntoVenta" 
                            type="number" 
                            id="puntoVenta" 
                            min="1"
                            @error('puntoVenta') aria-invalid="true" @enderror
                            @if($loading) disabled @endif
                        >
                        @error('puntoVenta') 
                            <small>{{ $message }}</small>
                        @enderror
                        <small>Generalmente el punto de venta es 1</small>

                        @if($errorMessage)
                            <article>
                                <header><strong>Error</strong></header>
                                <p>{{ $errorMessage }}</p>
                            </article>
                        @endif
                    </form>
                @endif
                
                <footer>
                    <button 
                        type="button" 
                        class="secondary" 
                        wire:click="cerrarModal"
                        @if($loading) disabled @endif
                    >
                        Cancelar
                    </button>
                    <button 
                        type="button" 
                        wire:click="generarFactura"
                        @if($loading) disabled @endif
                    >
                        @if($loading)
                            Generando...
                        @else
                            Generar Factura
                        @endif
                    </button>
                </footer>
            </article>
        </dialog>
    @endif

    {{-- Script para abrir PDF automáticamente --}}
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('factura-generada', (event) => {
                // Abrir PDF en nueva pestaña
                window.open(event.url, '_blank');
            });
        });
    </script>
</div>

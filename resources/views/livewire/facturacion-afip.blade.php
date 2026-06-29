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
                        @if($pago->notaCredito)
                            <small style="color:#d32f2f;"><strong>NC emitida</strong></small>
                        @elseif($certificadosValidos)
                            <button wire:click="openNcModal" style="background:#d32f2f; border-color:#d32f2f;">
                                Generar Nota de Crédito
                            </button>
                        @endif
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

            {{-- Información de Nota de Crédito si fue emitida --}}
            @if($pago->notaCredito)
                <div style="background:#464343; padding:10px; border-radius:5px; margin-top:10px;">
                    <strong style="color:#d32f2f;">Nota de Crédito emitida</strong>
                    <div class="grid" style="margin-top:8px;">
                        <div>
                            <small>Tipo NC</small>
                            <div><strong>{{ $pago->notaCredito->tipo_comprobante_nombre }}</strong></div>
                        </div>
                        <div>
                            <small>CAE NC</small>
                            <div><strong>{{ $pago->notaCredito->afip_cae }}</strong></div>
                        </div>
                        <div>
                            <small>Número NC</small>
                            <div><strong>{{ str_pad($pago->notaCredito->afip_punto_venta, 5, '0', STR_PAD_LEFT) }}-{{ str_pad($pago->notaCredito->afip_numero_comprobante, 8, '0', STR_PAD_LEFT) }}</strong></div>
                        </div>
                        <div>
                            <small>Ver NC PDF</small>
                            <div>
                                <a role="button" href="{{ route('FacturaAfipPDF', ['pagoId' => $pago->notaCredito->id]) }}" target="_blank" class="secondary" style="padding:4px 10px; font-size:0.85em;">
                                    PDF NC
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
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
                            readonly
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
    {{-- Modal de Nota de Crédito --}}
    @if($showNcModal)
        <dialog open>
            <article>
                <header>
                    <strong>Generar Nota de Crédito AFIP</strong>
                    <button type="button" class="secondary" wire:click="cerrarNcModal">Cerrar</button>
                </header>

                @if($pago)
                    <article>
                        <p>Se generará una <strong>Nota de Crédito</strong> que anula la siguiente factura:</p>
                        <div class="grid">
                            <div>
                                <small>Comprobante Original</small>
                                <div><strong>{{ $pago->tipo_comprobante_nombre }}</strong></div>
                            </div>
                            <div>
                                <small>CAE Original</small>
                                <div><strong>{{ $pago->afip_cae }}</strong></div>
                            </div>
                            <div>
                                <small>Número</small>
                                <div><strong>{{ str_pad($pago->afip_punto_venta, 5, '0', STR_PAD_LEFT) }}-{{ str_pad($pago->afip_numero_comprobante, 8, '0', STR_PAD_LEFT) }}</strong></div>
                            </div>
                            <div>
                                <small>Importe a acreditar</small>
                                <div><strong>${{ number_format($pago->total, 2) }}</strong></div>
                            </div>
                        </div>
                        <p style="background:#fff3f3; color:#8a1f1f; padding:10px; border-radius:5px; margin-top:10px;">
                            <strong>⚠ Atención:</strong> Esta acción genera una Nota de Crédito en AFIP
                            por el total facturado, crea un movimiento negativo en la tabla de pagos
                            y revierte el servicio a estado <strong>IMPAGO</strong>.
                        </p>

                        @if($errorMessage)
                            <article>
                                <header><strong>Error</strong></header>
                                <p>{{ $errorMessage }}</p>
                            </article>
                        @endif
                    </article>
                @endif

                <footer>
                    <button type="button" class="secondary" wire:click="cerrarNcModal" @if($loading) disabled @endif>
                        Cancelar
                    </button>
                    <button type="button" wire:click="generarNotaCredito"
                            style="background:#d32f2f; border-color:#d32f2f;"
                            @if($loading) disabled @endif>
                        @if($loading) Generando... @else Confirmar y Generar NC @endif
                    </button>
                </footer>
            </article>
        </dialog>
    @endif

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('factura-generada', (event) => {
                // Abrir PDF en nueva pestaña
                window.open(event.url, '_blank');
            });
        });
    </script>
</div>

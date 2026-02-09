<div class="container">
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

    @if ($loading)
    
            <progress />
        
    @else
        <article >
            <header>
                <strong>Enviar recibo/factura por WhatsApp</strong>
            </header>

            <div class="grid">
                <div>
                    <label for="telefono_whatsapp">Número de WhatsApp</label>
                    <input
                        id="telefono_whatsapp"
                        type="text"
                        wire:model="telefono"
                        placeholder="Ej: 5492942506803"
                        @error('telefono') aria-invalid="true" @enderror
                    >
                    @error('telefono')
                        <small>{{ $message }}</small>
                    @enderror
                    <small>Se usa el teléfono del cliente como valor por defecto.</small>
                </div>

                <div style="text-align: right;">
                    <button
                        wire:click="enviarComprobantes"
                        @if($loading || empty($telefono)) disabled @endif
                    >
                        @if($loading)
                            Enviando...
                        @else
                            Enviar PDFs A4
                        @endif
                    </button>
                </div>
            </div>

            <hr>

            <div class="grid">
                <div>
                    <small><strong>Recibo</strong></small>
                    <div>Se enviará el recibo en PDF A4.</div>
                </div>
                <div>
                    <small><strong>Factura AFIP</strong></small>
                    <div>
                        @if($pago && $pago->tieneFacturaAfip())
                            Se enviará la factura en PDF A4.
                        @else
                            No hay factura generada para este pago.
                        @endif
                    </div>
                </div>
            </div>
        </article>
        
    @endif
    


</div>

<div class="container">
    <article>
        <header>
            <hgroup>
                <h2><i class="fab fa-whatsapp"></i> Administración de WhatsApp</h2>
                @if($empresa)
                    <p>Empresa: {{ $empresa->name ?? 'Sin nombre' }}</p>
                @endif
            </hgroup>
            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                @if($estado)
                    <span class="whatsapp-state" style="display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fab fa-whatsapp" style="font-size: 1.4rem; color: {{ $estado === 'open' ? '#22c55e' : ($estado === 'close' ? '#ef4444' : ($estado === 'connecting' ? '#eab308' : '#6b7280')) }};"></i>
                        <strong>{{ strtoupper($estado) }}</strong>
                    </span>
                @else
                    <mark>No configurado</mark>
                @endif
                <button wire:click="refreshState" class="outline" style="width: auto; margin: 0;">
                    <i class="fas fa-sync"></i> Refrescar estado
                </button>
                @if($whatsappConfigurado && $estado === 'open')
                    <button wire:click="logout" class="outline" style="width: auto; margin: 0;" onclick="return confirm('¿Desconectar la instancia de WhatsApp?')">
                        <i class="fas fa-sign-out-alt"></i> Desconectar
                    </button>
                @endif
            </div>
        </header>

        <section>
            @if($successMessage)
                <ins>
                    <i class="fas fa-check-circle"></i> {{ $successMessage }}
                    <button wire:click="$set('successMessage', '')" class="close" aria-label="Cerrar">&times;</button>
                </ins>
            @endif

            @if($errorMessage)
                <mark>
                    <i class="fas fa-exclamation-triangle"></i> {{ $errorMessage }}
                    <button wire:click="$set('errorMessage', '')" class="close" aria-label="Cerrar">&times;</button>
                </mark>
            @endif
        </section>

        <section>
            <hgroup>
                <h3>Configuración de la instancia</h3>
                <p>Instancia y token de la API de Evolution asociados a la empresa.</p>
            </hgroup>

            <form wire:submit="saveConfig">
                <label for="instanciaWS">Instancia WhatsApp</label>
                <input type="text" id="instanciaWS" wire:model="instanciaWS" placeholder="Nombre de la instancia" autocomplete="off">
                @error('instanciaWS') <small style="color: #ef4444;">{{ $message }}</small> @enderror

                <label for="tokenWS">Token WhatsApp</label>
                <input type="text" id="tokenWS" wire:model="tokenWS" placeholder="Token de la instancia" autocomplete="off">
                @error('tokenWS') <small style="color: #ef4444;">{{ $message }}</small> @enderror

                <button type="submit">
                    <i class="fas fa-save"></i> Guardar configuración
                </button>
            </form>
        </section>

        <section @if($conectando || $estado === 'connecting') wire:poll.5s="refreshState" @endif>
            <hgroup>
                <h3>Conectar instancia</h3>
                <p>Vinculá la instancia escaneando el código QR con tu WhatsApp.</p>
            </hgroup>

            @if(!$whatsappConfigurado)
                <mark>
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>Atención:</strong> Configure la instancia y el token antes de conectar.
                </mark>
            @elseif($estado === 'open')
                <ins>
                    <i class="fas fa-check-circle"></i> La instancia está conectada y operativa.
                </ins>
            @else
                @if($qrCode)
                    <div style="text-align: center; margin: 1rem 0;">
                        <img src="{{ $qrCode }}" alt="Código QR de WhatsApp" style="max-width: 260px; border-radius: 8px; background: #fff; padding: 8px;">
                        <p><small>
                            Escaneá el QR desde <strong>WhatsApp → Dispositivos vinculados → Vincular un dispositivo</strong>.
                        </small></p>
                        @if($pairingCode)
                            <p>
                                <strong>Código de vinculación:</strong> <code>{{ $pairingCode }}</code>
                            </p>
                            <p><small>
                                Alternativa: <strong>WhatsApp → Dispositivos vinculados → Vincular con número de teléfono</strong>.
                            </small></p>
                        @endif
                        @if($conectando || $estado === 'connecting')
                            <small aria-busy="true">Esperando confirmación de vinculación...</small>
                        @endif
                    </div>
                @else
                    <button wire:click="connect" @if($conectando || $estado === 'connecting') aria-busy="true" disabled @endif>
                        <i class="fab fa-whatsapp"></i> Conectar instancia
                    </button>
                @endif
            @endif
        </section>

        <section>
            <hgroup>
                <h3>Enviar mensaje de prueba</h3>
                <p>Verifica que la instancia funciona enviando un mensaje de texto.</p>
            </hgroup>

            @if(!$whatsappConfigurado)
                <mark>
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>Atención:</strong> Configure la instancia y el token antes de enviar mensajes de prueba.
                </mark>
            @else
                <form wire:submit="enviarPrueba">
                    <label for="telefonoPrueba">Número de teléfono</label>
                    <input type="text" id="telefonoPrueba" wire:model="telefonoPrueba" placeholder="Ej: 5492942506803" autocomplete="off">
                    @error('telefonoPrueba') <small style="color: #ef4444;">{{ $message }}</small> @enderror

                    <label for="mensajePrueba">Mensaje</label>
                    <textarea id="mensajePrueba" wire:model="mensajePrueba" rows="3" placeholder="Escribe el mensaje a enviar"></textarea>
                    @error('mensajePrueba') <small style="color: #ef4444;">{{ $message }}</small> @enderror

                    <button type="submit" @if($enviandoPrueba) aria-busy="true" @endif>
                        <i class="fab fa-whatsapp"></i> Enviar mensaje de prueba
                    </button>
                </form>
            @endif
        </section>
    </article>
</div>
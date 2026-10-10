<div>
    <style>[x-cloak]{display:none !important;}</style>

    @if (! $confirmandoRegeneracion && ! $mostrarModal)
        <strong>
            <button
                type="button"
                wire:click="generarToken"
                data-tooltip="Generar/Regenerar token de API"
                style="background-color: transparent; border: none; cursor: pointer; padding: 0; color: #FFD43B;">
                <i class="fas fa-key"></i> Token API
            </button>
        </strong>
        @if ($tieneToken)
            <small style="color: #28a745;" data-tooltip="La empresa ya tiene un token generado">✓ Configurado</small>
        @endif
    @endif

    {{-- Confirmación al regenerar un token existente --}}
    @if ($confirmandoRegeneracion)
        <dialog open>
            <article style="max-width: 520px;">
                <header>
                    <h3>⚠️ Regenerar token</h3>
                </header>
                <p>
                    La empresa <strong>{{ $nombreEmpresa }}</strong> ya tiene un token de API.
                    Si lo regenerás, <strong>el token anterior dejará de funcionar</strong>.
                </p>
                <footer style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="secondary" wire:click="cancelarRegeneracion">Cancelar</button>
                    <button type="button" wire:click="generarToken">Sí, regenerar</button>
                </footer>
            </article>
        </dialog>
    @endif

    {{-- Token generado (se muestra una sola vez) --}}
    @if ($mostrarModal && $tokenGenerado)
        <dialog open x-data="{ copiado: false }">
            <article style="max-width: 620px; width: 95%;">
                <header>
                    <h3>🔑 Token de API</h3>
                </header>
                <p>
                    Token de <strong>{{ $nombreEmpresa }}</strong>.
                    <strong>Guardalo ahora, no se volverá a mostrar.</strong>
                </p>
                <div role="group">
                    <input type="text" readonly value="{{ $tokenGenerado }}" x-ref="token" style="font-family: monospace;">
                    <button
                        type="button"
                        @click="navigator.clipboard.writeText($refs.token.value).then(() => { copiado = true; setTimeout(() => copiado = false, 2000); })">
                        <span x-show="! copiado">Copiar</span>
                        <span x-show="copiado" x-cloak>¡Copiado!</span>
                    </button>
                </div>
                <small>Usalo en el header: <code>Authorization: Bearer &lt;token&gt;</code></small>
                <footer style="display: flex; justify-content: flex-end; margin-top: 1rem;">
                    <button type="button" class="secondary" wire:click="cerrarModal">Cerrar</button>
                </footer>
            </article>
        </dialog>
    @endif
</div>

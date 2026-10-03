<div>
    <div class="container">
        @if($successMessage)
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ $successMessage }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errorMessage)
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ $errorMessage }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card mb-3" style="position: relative;">
            <div class="card-body">
                <h6 class="card-title mb-3">
                    <i class="fas fa-magnifying-glass-dollar me-2"></i>Auditoría de pagos MercadoPago
                </h6>

                <div class="mb-3">
                    <label for="modo" class="form-label">Modo</label>
                    <select id="modo" wire:model="modo" class="form-select" wire:loading.attr="disabled">
                        <option value="huerfanos">Pagos huérfanos en servicios impago</option>
                        <option value="cruzados">Pagos cruzados (pago anterior al período)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="empresaId" class="form-label">Empresa</label>
                    <select id="empresaId" wire:model.live="empresaId" class="form-select" wire:loading.attr="disabled">
                        <option value="all">Todas las empresas</option>
                        @foreach($empresas as $e)
                            <option value="{{ $e->id }}">{{ $e->nombre }} — CUIT {{ $e->cuit ?? 's/d' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-primary" wire:click="auditar" wire:loading.attr="disabled" wire:target="auditar,aplicar">
                        <span wire:loading wire:target="auditar" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        <i wire:loading.remove wire:target="auditar" class="fas fa-magnifying-glass me-1"></i>
                        <span wire:loading.remove wire:target="auditar">Auditar (sin cambios)</span>
                        <span wire:loading wire:target="auditar">Procesando...</span>
                    </button>

                    <button
                        type="button"
                        class="btn btn-danger"
                        wire:click="aplicar"
                        wire:confirm="¿Confirmás aplicar los cambios? Esta acción puede revertir deudas a impago y eliminar pagos. No se puede deshacer."
                        wire:loading.attr="disabled"
                        wire:target="auditar,aplicar"
                    >
                        <span wire:loading wire:target="aplicar" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        <i wire:loading.remove wire:target="aplicar" class="fas fa-triangle-exclamation me-1"></i>
                        <span wire:loading.remove wire:target="aplicar">Aplicar cambios</span>
                        <span wire:loading wire:target="aplicar">Procesando...</span>
                    </button>
                </div>

                @if($modo === 'cruzados')
                    <small class="text-muted d-block mt-2">
                        El modo pagos cruzados consulta la API de MercadoPago pago por pago; puede demorar.
                    </small>
                @endif

                <div wire:loading wire:target="auditar,aplicar" class="alert alert-info mt-3 mb-0 d-flex align-items-center">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    <span>Procesando... esto puede demorar. No cierres esta ventana.</span>
                </div>
            </div>
        </div>

        @if($output !== '')
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Resultado</h6>
                    <pre style="white-space: pre-wrap; word-break: break-word; max-height: 60vh; overflow: auto;">{{ $output }}</pre>
                </div>
            </div>
        @endif
    </div>
</div>

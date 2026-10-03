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

    <div class="card mb-3">
        <div class="card-body">
            <h6 class="card-title mb-3">
                <i class="fas fa-magnifying-glass-dollar me-2"></i>Auditoría de pagos MercadoPago
            </h6>

            <div class="mb-3">
                <label for="modo" class="form-label">Modo</label>
                <select id="modo" wire:model="modo" class="form-select" @if($loading) disabled @endif>
                    <option value="huerfanos">Pagos huérfanos en servicios impago</option>
                    <option value="cruzados">Pagos cruzados (pago anterior al período)</option>
                </select>
            </div>

            @if(!$empresa && !$todas)
                <div class="alert alert-warning">
                    <i class="fas fa-building me-1"></i>No hay empresa seleccionada. Buscá y seleccioná una empresa o auditá todas.
                </div>

                <div class="mb-3">
                    <label for="empresaSearch" class="form-label">Buscar empresa (nombre o CUIT)</label>
                    <input
                        type="text"
                        id="empresaSearch"
                        wire:model.live="empresaSearch"
                        class="form-control"
                        placeholder="Ej: Mi Empresa o 20123456789"
                        @if($loading) disabled @endif
                    >
                </div>

                @if(!empty($empresaResults))
                    <div class="list-group mb-3">
                        @foreach($empresaResults as $item)
                            <button
                                type="button"
                                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                wire:click="selectEmpresa({{ $item->id }})"
                                @if($loading) disabled @endif
                            >
                                <div>
                                    <div class="fw-bold">{{ $item->nombre ?? 'Sin nombre' }}</div>
                                    <small class="text-muted">CUIT: {{ $item->cuit ?? 'No definido' }}</small>
                                </div>
                                <span class="badge bg-primary">Seleccionar</span>
                            </button>
                        @endforeach
                    </div>
                @endif

                <button type="button" class="btn btn-outline-secondary" wire:click="usarTodas" @if($loading) disabled @endif>
                    <i class="fas fa-globe me-1"></i>Auditar todas las empresas
                </button>
            @else
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        @if($todas)
                            <div class="fw-bold">Todas las empresas</div>
                            <small class="text-muted">Se auditará el sistema completo.</small>
                        @else
                            <div class="fw-bold">Empresa: {{ $empresa->nombre ?? 'Sin nombre' }}</div>
                            <small class="text-muted">CUIT: {{ $empresa->cuit ?? 'No definido' }}</small>
                        @endif
                    </div>
                    <button type="button" class="btn btn-outline-secondary" wire:click="resetEmpresa" @if($loading) disabled @endif>
                        <i class="fas fa-exchange-alt me-1"></i>Cambiar
                    </button>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-primary" wire:click="auditar" @if($loading) disabled @endif>
                        @if($loading)
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Procesando...
                        @else
                            <i class="fas fa-magnifying-glass me-1"></i>Auditar (sin cambios)
                        @endif
                    </button>

                    <button
                        type="button"
                        class="btn btn-danger"
                        wire:click="aplicar"
                        wire:confirm="¿Confirmás aplicar los cambios? Esta acción puede revertir deudas a impago y eliminar pagos. No se puede deshacer."
                        @if($loading) disabled @endif
                    >
                        @if($loading)
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Procesando...
                        @else
                            <i class="fas fa-triangle-exclamation me-1"></i>Aplicar cambios
                        @endif
                    </button>
                </div>

                @if($modo === 'cruzados')
                    <small class="text-muted d-block mt-2">
                        El modo pagos cruzados consulta la API de MercadoPago pago por pago; puede demorar.
                    </small>
                @endif
            @endif
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

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
                <i class="fas fa-certificate me-2"></i>Generar Certificados AFIP
            </h6>

            @if(!$empresa)
                <div class="alert alert-warning">
                    <i class="fas fa-building me-1"></i>No hay empresa seleccionada. Buscá y seleccioná una empresa.
                </div>

                <div class="mb-3">
                    <label for="empresaSearch" class="form-label">Buscar empresa (nombre o CUIT)</label>
                    <input
                        type="text"
                        id="empresaSearch"
                        wire:model.live="empresaSearch"
                        class="form-control"
                        placeholder="Ej: Mi Empresa o 20123456789"
                    >
                </div>

                @if(!empty($empresaResults))
                    <div class="list-group">
                        @foreach($empresaResults as $item)
                            <button
                                type="button"
                                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                wire:click="selectEmpresa({{ $item->id }})"
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
            @else
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <div class="fw-bold">Empresa: {{ $empresa->nombre ?? 'Sin nombre' }}</div>
                        <small class="text-muted">CUIT: {{ $empresa->cuit ?? 'No definido' }}</small>
                    </div>
                    <button type="button" class="btn btn-outline-secondary" wire:click="resetEmpresa">
                        <i class="fas fa-exchange-alt me-1"></i>Cambiar
                    </button>
                </div>

                <form wire:submit.prevent="generarCertificados">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="entorno" class="form-label">Entorno</label>
                            <select
                                id="entorno"
                                wire:model="entorno"
                                class="form-select @error('entorno') is-invalid @enderror"
                                @if($loading) disabled @endif
                            >
                                <option value="dev">Desarrollo (Homologación)</option>
                                <option value="prod">Producción</option>
                            </select>
                            @error('entorno')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="username" class="form-label">Usuario ARCA</label>
                            <input
                                type="text"
                                id="username"
                                wire:model.defer="username"
                                class="form-control @error('username') is-invalid @enderror"
                                placeholder="CUIT para loguearse en la página de ARCA. Normalmente es el mismo CUIT que el parámetro 'cuit', pero si administrás una sociedad, el CUIT que usás para loguearte es tu propio CUIT."
                                @if($loading) disabled @endif
                            >
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="alias" class="form-label">Alias del certificado</label>
                            <input
                                type="text"
                                id="alias"
                                wire:model.defer="alias"
                                class="form-control @error('alias') is-invalid @enderror"
                                placeholder="Ej: afipsdk"
                                @if($loading) disabled @endif
                            >
                            @error('alias')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Clave Fiscal ARCA</label>
                        <fieldset role="group">
                            <input
                                type="password"
                                id="password"
                                wire:model.live="password"
                                class="form-control @error('password') is-invalid @enderror"
                                placeholder="Clave Fiscal de ARCA"
                                @if($loading) disabled @endif
                            >
                            <button id="verContraseña" type="button" class="btn btn-secondary" @if($loading) disabled @endif>Ver</button>

                        </fieldset>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success" @if($loading) disabled @endif>
                            @if($loading)
                                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                Generando...
                            @else
                                <i class="fas fa-certificate me-1"></i>Generar certificados
                            @endif
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const passwordInput = document.getElementById('password');
        const toggleButton = document.getElementById('verContraseña');

        if (!passwordInput || !toggleButton) return;

        toggleButton.addEventListener('click', () => {
            const showing = passwordInput.getAttribute('type') === 'text';
            passwordInput.setAttribute('type', showing ? 'password' : 'text');
            toggleButton.textContent = showing ? 'Ver' : 'Ocultar';
        });
    });
</script>

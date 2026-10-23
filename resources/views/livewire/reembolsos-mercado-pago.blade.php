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

        <div class="card mb-3">
            <div class="card-body">
                <h6 class="card-title mb-3">
                    <i class="fas fa-rotate-left me-2"></i>Reembolsos de pagos duplicados MercadoPago
                </h6>

                @if(auth()->user()?->role?->nombre === 'Super')
                    <div class="mb-3">
                        <label for="empresaId" class="form-label">Empresa</label>
                        <select id="empresaId" wire:model.live="empresaId" class="form-select" wire:loading.attr="disabled">
                            @foreach($empresas as $e)
                                <option value="{{ $e['id'] }}">{{ $e['nombre'] }} — CUIT {{ $e['cuit'] ?? 's/d' }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="desde" class="form-label">Desde</label>
                        <input id="desde" type="date" class="form-control" wire:model="desde" wire:loading.attr="disabled">
                        @error('desde') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="hasta" class="form-label">Hasta</label>
                        <input id="hasta" type="date" class="form-control" wire:model="hasta" wire:loading.attr="disabled">
                        @error('hasta') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="button" class="btn btn-primary w-100" wire:click="buscarDuplicados" wire:loading.attr="disabled" wire:target="buscarDuplicados">
                            <span wire:loading wire:target="buscarDuplicados" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            <i wire:loading.remove wire:target="buscarDuplicados" class="fas fa-magnifying-glass me-1"></i>
                            <span wire:loading.remove wire:target="buscarDuplicados">Buscar duplicados</span>
                            <span wire:loading wire:target="buscarDuplicados">Buscando...</span>
                        </button>
                    </div>
                </div>

                <p class="text-muted small mb-0">
                    Se buscan pagos aprobados de la empresa en el período indicado. Los pagos históricos anteriores a la creación de la deuda se ignoran.
                    Solo se puede reembolsar un pago que no esté aplicado a un servicio y que no tenga factura AFIP.
                </p>
            </div>
        </div>

        @if(! $empresaTieneToken)
            <div class="alert alert-warning">
                La empresa seleccionada no tiene <strong>MP_ACCESS_TOKEN</strong> configurado. No se pueden consultar pagos ni reembolsar.
            </div>
        @endif

        @if($showConfirm && $selected)
            <div class="card border-danger mb-3">
                <div class="card-body">
                    <h6 class="card-title text-danger">
                        <i class="fas fa-triangle-exclamation me-2"></i>Confirmar reembolso
                    </h6>

                    <p class="mb-2">
                        Se va a reembolsar el pago <strong>{{ $selected['duplicate']['id'] }}</strong>
                        por <strong>${{ number_format($selected['duplicate']['monto'], 2, ',', '.') }}</strong>
                        de la referencia <strong>{{ $selected['referencia'] }}</strong>.
                    </p>

                    <p class="mb-2">
                        Cliente: <strong>{{ $selected['cliente'] ?? 's/d' }}</strong> —
                        Servicio: <strong>{{ $selected['servicio'] ?? 's/d' }}</strong>
                    </p>

                    <p class="mb-3">
                        Se conserva el pago <strong>{{ $selected['primary']['id'] }}</strong>
                        por <strong>${{ number_format($selected['primary']['monto'], 2, ',', '.') }}</strong>.
                    </p>

                    <div class="mb-3">
                        <label for="motivo" class="form-label">Motivo</label>
                        <input id="motivo" type="text" class="form-control" wire:model="motivo" maxlength="255">
                        @error('motivo') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-danger" wire:click="reembolsar" wire:loading.attr="disabled" wire:target="reembolsar">
                            <span wire:loading wire:target="reembolsar" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            <span wire:loading.remove wire:target="reembolsar">Confirmar reembolso</span>
                            <span wire:loading wire:target="reembolsar">Procesando...</span>
                        </button>
                        <button type="button" class="btn btn-secondary" wire:click="cancelarConfirmacion" wire:loading.attr="disabled">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        @endif

        @if(!empty($candidatos))
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title">Pagos duplicados detectados</h6>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Referencia / Cliente</th>
                                    <th>Servicio</th>
                                    <th>Pago que se conserva</th>
                                    <th>Pago(s) duplicado(s)</th>
                                    <th>Monto duplicado</th>
                                    <th>Estado</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($candidatos as $candidato)
                                    <tr wire:key="candidato-{{ $candidato['referencia'] }}">
                                        <td>
                                            <code>{{ $candidato['referencia'] }}</code><br>
                                            <small>{{ $candidato['cliente'] ?? 's/d' }}</small>
                                        </td>
                                        <td>{{ $candidato['servicio'] ?? 's/d' }}</td>
                                        <td>
                                            <span class="badge bg-success">{{ $candidato['primary']['id'] }}</span><br>
                                            <small>
                                                ${{ number_format($candidato['primary']['monto'], 2, ',', '.') }}
                                                — {{ $candidato['primary']['fecha'] }}
                                                @if($candidato['primary']['aplicado'])
                                                    — <span class="text-success">aplicado</span>
                                                @endif
                                            </small>
                                        </td>
                                        <td>
                                            @foreach($candidato['duplicates'] as $duplicado)
                                                <div class="mb-1">
                                                    <span class="badge bg-warning text-dark">{{ $duplicado['id'] }}</span>
                                                    <small>
                                                        ${{ number_format($duplicado['monto'], 2, ',', '.') }}
                                                        — {{ $duplicado['fecha'] }}
                                                        @if($duplicado['aplicado'])
                                                            — <span class="text-danger">aplicado</span>
                                                        @endif
                                                    </small>
                                                </div>
                                            @endforeach
                                        </td>
                                        <td>${{ number_format($candidato['monto_duplicado'], 2, ',', '.') }}</td>
                                        <td>
                                            @if($candidato['elegible'])
                                                <span class="badge bg-success">Reembolsable</span>
                                            @else
                                                <span class="badge bg-secondary">Revisión manual</span>
                                                <ul class="mb-0 ps-3">
                                                    @foreach($candidato['motivos'] as $motivoNoElegible)
                                                        <li><small>{{ $motivoNoElegible }}</small></li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </td>
                                        <td>
                                            @if($candidato['elegible'])
                                                @foreach($candidato['duplicates'] as $duplicado)
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-danger mb-1"
                                                        wire:click="confirmarReembolso('{{ $duplicado['id'] }}')"
                                                        wire:loading.attr="disabled"
                                                    >
                                                        Reembolsar {{ $duplicado['id'] }}
                                                    </button>
                                                @endforeach
                                            @else
                                                <small class="text-muted">Requiere revisión</small>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <div class="card mb-3">
            <div class="card-body">
                <h6 class="card-title">Reembolso manual por Payment ID</h6>
                <p class="text-muted small">
                    Solo se permite reembolsar pagos que pertenezcan a la empresa seleccionada, que estén aprobados y que no estén aplicados a un servicio.
                </p>

                <div class="input-group">
                    <input
                        type="text"
                        class="form-control"
                        wire:model="manualPaymentId"
                        placeholder="Ej.: 185650229710"
                        wire:loading.attr="disabled"
                    >
                    <button
                        type="button"
                        class="btn btn-outline-danger"
                        wire:click="reembolsarManual"
                        wire:loading.attr="disabled"
                        wire:target="reembolsarManual"
                    >
                        <span wire:loading wire:target="reembolsarManual" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        <span wire:loading.remove wire:target="reembolsarManual">Reembolsar</span>
                        <span wire:loading wire:target="reembolsarManual">Procesando...</span>
                    </button>
                </div>
                @error('manualPaymentId') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>
        </div>

        @if(!empty($resultados))
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Resultados</h6>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Resultado</th>
                                    <th>Refund ID</th>
                                    <th>Mensaje</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($resultados as $resultado)
                                    <tr wire:key="resultado-{{ $loop->index }}">
                                        <td><code>{{ $resultado['payment_id'] ?? '-' }}</code></td>
                                        <td>
                                            @if($resultado['success'] ?? false)
                                                <span class="badge bg-success">OK</span>
                                            @else
                                                <span class="badge bg-danger">Error</span>
                                            @endif
                                        </td>
                                        <td>{{ $resultado['refund_id'] ?? '-' }}</td>
                                        <td>{{ $resultado['mensaje'] ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

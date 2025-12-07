
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-2">
                        <i class="fas fa-qrcode me-2"></i>
                        Sistema de Cobro con QR
                    </h1>
                    <p class="text-muted mb-0">
                        Genera un código QR para que tus clientes paguen con Mercado Pago
                    </p>
                </div>
                <a href="{{ route('mercadopago.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Volver
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="alert alert-info">
                <h5 class="alert-heading">
                    <i class="fas fa-info-circle me-2"></i>
                    Instrucciones de uso:
                </h5>
                <ol class="mb-0">
                    <li>Selecciona la caja/POS donde se realizará el cobro</li>
                    <li>Ingresa el monto a cobrar</li>
                    <li>Genera el código QR</li>
                    <li>El cliente escanea el código QR físico de la caja con su app de Mercado Pago</li>
                    <li>El sistema detectará automáticamente cuando el pago se complete</li>
                </ol>
            </div>
        </div>
    </div>

    {{-- Selección de POS/Caja --}}
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-cash-register me-2"></i>
                        Seleccionar Caja
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        @forelse($poses as $pos)
                            <a href="{{ route('mercadopago.qr.cobro', ['posId' => $pos->id]) }}" 
                               class="list-group-item list-group-item-action {{ request('posId') == $pos->id ? 'active' : '' }}">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">
                                            <i class="fas fa-store me-2"></i>
                                            {{ $pos->name }}
                                        </h6>
                                        <small class="text-muted">
                                            Sucursal: {{ $pos->store->name }}
                                        </small>
                                    </div>
                                    @if(request('posId') == $pos->id)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check"></i> Seleccionada
                                        </span>
                                    @endif
                                </div>
                            </a>
                        @empty
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No hay cajas configuradas. 
                                <a href="{{ route('mercadopago.index') }}">Crear una caja primero</a>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        @if(request()->has('posId'))
            <div class="col-md-6">
                {{-- Componente Livewire de cobro --}}
                @livewire('qr-payment', ['posId' => request('posId')])
            </div>
        @else
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-hand-pointer fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Selecciona una caja para comenzar</h5>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // Solicitar permiso para notificaciones al cargar la página
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
</script>
@endpush

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-qrcode me-2"></i>
                        Cobro con Código QR
                    </h4>
                </div>
                
                <div class="card-body">
                    @if (session()->has('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session()->has('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (!$showQR)
                        {{-- Formulario para crear orden --}}
                        <form wire:submit.prevent="createOrder">
                            <div class="mb-4">
                                <label for="amount" class="form-label fw-bold">Monto a cobrar *</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text">$</span>
                                    <input 
                                        type="number" 
                                        class="form-control @error('amount') is-invalid @enderror" 
                                        id="amount"
                                        wire:model="amount"
                                        step="0.01"
                                        min="0.01"
                                        placeholder="0.00"
                                        autofocus>
                                </div>
                                @error('amount')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="description" class="form-label fw-bold">Descripción (opcional)</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="description"
                                    wire:model="description"
                                    placeholder="Ej: Venta de productos">
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-qrcode me-2"></i>
                                    Generar Código QR
                                </button>
                            </div>
                        </form>
                    @else
                        {{-- Pantalla de espera de pago --}}
                        <div class="text-center py-4">
                            @if ($orderStatus === 'pending')
                                <div class="mb-4">
                                    <h3 class="text-primary mb-3">Esperando pago...</h3>
                                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                </div>

                                <div class="alert alert-info mb-4">
                                    <h4 class="alert-heading">
                                        <i class="fas fa-mobile-alt me-2"></i>
                                        Monto a cobrar: ${{ number_format($amount, 2) }}
                                    </h4>
                                    <hr>
                                    <p class="mb-0">
                                        El cliente debe escanear el código QR físico de la caja con su aplicación de Mercado Pago
                                        <img src="https://qrcode.tec-it.com/API/QRCode?data={{ urlencode($qrData) }}&backcolor=%23ffffff" />
                                    </p>
                                </div>

                                <div class="alert alert-warning">
                                    <i class="fas fa-clock me-2"></i>
                                    La orden expira en 10 minutos
                                </div>

                                <div class="progress mb-4" style="height: 25px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                                         role="progressbar" 
                                         style="width: 100%">
                                        Esperando confirmación de pago...
                                    </div>
                                </div>

                                <button wire:click="cancelOrder" class="btn btn-outline-danger">
                                    <i class="fas fa-times me-2"></i>
                                    Cancelar Orden
                                </button>

                            @elseif ($orderStatus === 'paid')
                                <div class="alert alert-success py-5">
                                    <i class="fas fa-check-circle fa-5x text-success mb-3"></i>
                                    <h2 class="text-success mb-3">¡Pago Recibido!</h2>
                                    <p class="fs-4 mb-3">Monto: ${{ number_format($amount, 2) }}</p>
                                    <p class="text-muted">ID de pago: {{ $paymentId }}</p>
                                    <hr>
                                    <button wire:click="cancelOrder" class="btn btn-primary btn-lg mt-3">
                                        <i class="fas fa-plus me-2"></i>
                                        Nueva Venta
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Polling para verificar el estado del pago cada 3 segundos (solo cuando está pendiente) --}}
    @if ($orderStatus === 'pending' && $polling)
        <div wire:poll.3s="checkPaymentStatus" style="display: none;"></div>
    @endif

    {{-- Sonido de éxito cuando se complete el pago --}}
    @script
        <script>
            $wire.on('payment-successful', (event) => {
                // Reproducir sonido de éxito
                const audio = new Audio('/sounds/success.mp3');
                audio.play().catch(e => console.log('Error playing sound:', e));
                
                // Mostrar notificación del navegador si está permitido
                if ('Notification' in window && Notification.permission === 'granted') {
                    new Notification('¡Pago Recibido!', {
                        body: `Monto: $${event.amount}`,
                        icon: '/images/success-icon.png'
                    });
                }
            });

            $wire.on('qr-created', (event) => {
                console.log('Orden QR creada:', event);
            });
        </script>
    @endscript
</div>

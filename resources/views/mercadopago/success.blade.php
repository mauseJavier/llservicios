@extends("layouts.app")

@section("title", "Pago Exitoso")

@section("content")
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-check-circle"></i> Pago Procesado Exitosamente</h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    </div>
                    
                    <h5 class="mb-3">¡Su pago fue procesado correctamente!</h5>
                    <p class="text-muted">Gracias por su compra. En breve recibirá un email de confirmación.</p>
                    
                    @if(isset($paymentId))
                        <p><strong>ID del Pago:</strong> {{ $paymentId }}</p>
                    @endif
                    
                    @if(isset($status))
                        <p><strong>Estado:</strong> {{ $status }}</p>
                    @endif
                    
                    @if(isset($externalReference))
                        <p><strong>Referencia:</strong> {{ $externalReference }}</p>
                    @endif
                    
                    <div class="mt-4">
                        <a href="{{ route("servicios") }}" class="btn btn-primary">
                            <i class="fas fa-home"></i> Volver al Inicio
                        </a>
                        
                        <a href="{{ route("mercadopago.payment-form") }}" class="btn btn-success ml-2">
                            <i class="fas fa-plus"></i> Realizar Otro Pago
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

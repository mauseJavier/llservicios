@extends("layouts.app")

@section("title", "Pago Pendiente")

@section("content")
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0"><i class="fas fa-clock"></i> Pago Pendiente de Confirmación</h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-clock text-warning" style="font-size: 4rem;"></i>
                    </div>
                    
                    <h5 class="mb-3">Su pago está siendo procesado</h5>
                    <p class="text-muted">El pago está pendiente de confirmación. Le notificaremos una vez que sea aprobado.</p>
                    
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
                        
                        <a href="{{ route("mercadopago.payment-form") }}" class="btn btn-warning ml-2">
                            <i class="fas fa-plus"></i> Realizar Otro Pago
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

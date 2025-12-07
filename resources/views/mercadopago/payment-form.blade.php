@extends('principal.principal')

@section("title", "Formulario de Pago")

@section("body")
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-credit-card"></i> Procesar Pago con MercadoPago</h4>
                </div>
                <div class="card-body">
                    @if(session("error"))
                        <div class="alert alert-danger">
                            {{ session("error") }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route("mercadopago.process-payment") }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="title">Título del Producto/Servicio</label>
                                    <input type="text" class="form-control" id="title" name="title" value="{{ old("title", "Servicio de Prueba") }}" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="quantity">Cantidad</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" value="{{ old("quantity", 1) }}" min="1" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="unit_price">Precio Unitario</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number" class="form-control" id="unit_price" name="unit_price" step="0.01" value="{{ old("unit_price", "100.00") }}" min="0.01" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="currency_id">Moneda</label>
                                    <select class="form-control" id="currency_id" name="currency_id" required>
                                        <option value="ARS" {{ old("currency_id") == "ARS" ? "selected" : "" }}>ARS - Peso Argentino</option>
                                        <option value="BRL" {{ old("currency_id") == "BRL" ? "selected" : "" }}>BRL - Real Brasileño</option>
                                        <option value="CLP" {{ old("currency_id") == "CLP" ? "selected" : "" }}>CLP - Peso Chileno</option>
                                        <option value="MXN" {{ old("currency_id") == "MXN" ? "selected" : "" }}>MXN - Peso Mexicano</option>
                                        <option value="USD" {{ old("currency_id") == "USD" ? "selected" : "" }}>USD - Dólar Americano</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h5>Información del Comprador</h5>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="payer_name">Nombre</label>
                                    <input type="text" class="form-control" id="payer_name" name="payer_name" value="{{ old("payer_name") }}" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="payer_surname">Apellido</label>
                                    <input type="text" class="form-control" id="payer_surname" name="payer_surname" value="{{ old("payer_surname") }}" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="payer_email">Email</label>
                                    <input type="email" class="form-control" id="payer_email" name="payer_email" value="{{ old("payer_email") }}" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="payer_phone">Teléfono</label>
                                    <input type="text" class="form-control" id="payer_phone" name="payer_phone" value="{{ old("payer_phone") }}">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="description">Descripción</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Descripción detallada del producto o servicio">{{ old("description") }}</textarea>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-credit-card"></i> Pagar con MercadoPago
                            </button>
                        </div>
                        
                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt"></i> 
                                Pago seguro procesado por MercadoPago
                            </small>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

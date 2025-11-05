<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Gestión de QR MercadoPago</h5>
                            @if($empresa)
                                <p class="text-sm mb-0 text-muted">Empresa: {{ $empresa->name ?? 'Sin nombre' }}</p>
                            @endif
                        </div>
                        @if($mpConfigured)
                            <button wire:click="openStoreModal" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Nueva Tienda
                            </button>
                        @endif
                    </div>
                </div>

                <div class="card-body px-0 pt-0 pb-2">
                    {{-- Mensajes de alerta --}}
                    @if($successMessage)
                        <div class="alert alert-success alert-dismissible fade show mx-4 mt-3" role="alert">
                            <span class="alert-icon"><i class="fas fa-check-circle"></i></span>
                            <span class="alert-text">{{ $successMessage }}</span>
                            <button type="button" class="btn-close" wire:click="$set('successMessage', '')"></button>
                        </div>
                    @endif

                    @if($errorMessage)
                        <div class="alert alert-danger alert-dismissible fade show mx-4 mt-3" role="alert">
                            <span class="alert-icon"><i class="fas fa-exclamation-triangle"></i></span>
                            <span class="alert-text">{{ $errorMessage }}</span>
                            <button type="button" class="btn-close" wire:click="$set('errorMessage', '')"></button>
                        </div>
                    @endif

                    {{-- Mensaje si no está configurado MP --}}
                    @if(!$mpConfigured)
                        <div class="alert alert-warning mx-4 mt-3" role="alert">
                            <span class="alert-icon"><i class="fas fa-exclamation-circle"></i></span>
                            <span class="alert-text">
                                <strong>Atención:</strong> Configure las credenciales de MercadoPago en su empresa antes de crear tiendas y cajas.
                            </span>
                        </div>
                    @endif

                    {{-- Loading overlay --}}
                    @if($loading)
                        <div class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" 
                             style="background: rgba(0,0,0,0.5); z-index: 9999;">
                            <div class="spinner-border text-light" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                    @endif

                    {{-- Lista de tiendas --}}
                    <div class="table-responsive p-0">
                        @if($stores->count() > 0)
                            @foreach($stores as $store)
                                <div class="mx-4 mb-4 border rounded">
                                    {{-- Header de tienda --}}
                                    <div class="bg-light p-3 border-bottom">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">
                                                    <i class="fas fa-store text-primary"></i>
                                                    {{ $store->name }}
                                                </h6>
                                                <p class="text-sm mb-1 text-muted">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    {{ $store->full_address }}
                                                </p>
                                                @if($store->mp_store_id)
                                                    <small class="badge badge-sm bg-gradient-success">
                                                        ID MP: {{ $store->mp_store_id }}
                                                    </small>
                                                @endif
                                            </div>
                                            <div class="btn-group btn-group-sm">
                                                <button wire:click="openStoreModal({{ $store->id }})" 
                                                        class="btn btn-outline-primary btn-sm" 
                                                        title="Editar tienda">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button wire:click="openPosModal({{ $store->id }})" 
                                                        class="btn btn-outline-success btn-sm" 
                                                        title="Agregar caja">
                                                    <i class="fas fa-plus"></i> Caja
                                                </button>
                                                <button wire:click="deleteStore({{ $store->id }})" 
                                                        onclick="return confirm('¿Está seguro de eliminar esta tienda y todas sus cajas?')"
                                                        class="btn btn-outline-danger btn-sm" 
                                                        title="Eliminar tienda">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Cajas de la tienda --}}
                                    <div class="p-3">
                                        @if($store->pos->count() > 0)
                                            <div class="row">
                                                @foreach($store->pos as $pos)
                                                    <div class="col-md-6 col-lg-4 mb-3">
                                                        <div class="card h-100 {{ $pos->active ? '' : 'bg-light' }}">
                                                            <div class="card-body">
                                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                                    <h6 class="mb-0">
                                                                        <i class="fas fa-cash-register text-info"></i>
                                                                        {{ $pos->name }}
                                                                    </h6>
                                                                    <div class="btn-group btn-group-sm">
                                                                        @if($pos->qr_url)
                                                                            <a href="{{ $pos->qr_url }}" 
                                                                               target="_blank"
                                                                               class="btn btn-outline-primary btn-sm" 
                                                                               title="Ver QR">
                                                                                <i class="fas fa-qrcode"></i>
                                                                            </a>
                                                                        @endif
                                                                        <button wire:click="deletePos({{ $pos->id }})" 
                                                                                onclick="return confirm('¿Está seguro de eliminar esta caja?')"
                                                                                class="btn btn-outline-danger btn-sm" 
                                                                                title="Eliminar">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="text-sm text-muted">
                                                                    <div class="mb-1">
                                                                        <span class="badge badge-sm {{ $pos->fixed_amount === 'true' ? 'bg-gradient-success' : 'bg-gradient-warning' }}">
                                                                            {{ $pos->fixed_amount === 'true' ? 'Monto Fijo' : 'Monto Variable' }}
                                                                        </span>
                                                                    </div>
                                                                    
                                                                    @if($pos->category)
                                                                        <div class="mb-1">
                                                                            <i class="fas fa-tag"></i> 
                                                                            {{ $categories[$pos->category] ?? $pos->category }}
                                                                        </div>
                                                                    @endif
                                                                    
                                                                    @if($pos->mp_pos_id)
                                                                        <div class="mb-1">
                                                                            <i class="fas fa-id-card"></i> 
                                                                            <small>{{ $pos->mp_pos_id }}</small>
                                                                        </div>
                                                                    @endif
                                                                    
                                                                    <div class="mt-2">
                                                                        <span class="badge badge-sm {{ $pos->active ? 'bg-gradient-success' : 'bg-gradient-secondary' }}">
                                                                            {{ $pos->active ? 'Activa' : 'Inactiva' }}
                                                                        </span>
                                                                    </div>
                                                                </div>

                                                                {{-- Mostrar QR si está disponible --}}
                                                                @if($pos->qr_code)
                                                                    <div class="mt-3 text-center">
                                                                        <img src="{{ $pos->qr_code }}" 
                                                                             alt="QR Code" 
                                                                             class="img-fluid rounded"
                                                                             style="max-width: 150px;">
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="alert alert-info mb-0">
                                                <i class="fas fa-info-circle"></i>
                                                No hay cajas configuradas para esta tienda.
                                                <button wire:click="openPosModal({{ $store->id }})" 
                                                        class="btn btn-sm btn-info ms-2">
                                                    Crear primera caja
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                            {{-- Paginación --}}
                            <div class="mx-4">
                                {{ $stores->links() }}
                            </div>
                        @else
                            <div class="mx-4 mt-4">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    No hay tiendas configuradas. 
                                    @if($mpConfigured)
                                        <button wire:click="openStoreModal" class="btn btn-sm btn-info ms-2">
                                            Crear primera tienda
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal para crear/editar tienda --}}
    @if($showStoreModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ $storeId ? 'Editar Tienda' : 'Nueva Tienda' }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeModal">Cerrar</button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="saveStore">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="storeName" class="form-label">Nombre de la Tienda *</label>
                                    <input type="text" 
                                           class="form-control @error('storeName') is-invalid @enderror" 
                                           id="storeName" 
                                           wire:model="storeName"
                                           placeholder="Ej: Sucursal Centro">
                                    @error('storeName') 
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-8 mb-3">
                                    <label for="storeStreet" class="form-label">Calle *</label>
                                    <input type="text" 
                                           class="form-control @error('storeStreet') is-invalid @enderror" 
                                           id="storeStreet" 
                                           wire:model="storeStreet"
                                           placeholder="Av. Principal">
                                    @error('storeStreet') 
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="storeNumber" class="form-label">Número</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="storeNumber" 
                                           wire:model="storeNumber"
                                           placeholder="1234">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="storeCity" class="form-label">Ciudad *</label>
                                    <input type="text" 
                                           class="form-control @error('storeCity') is-invalid @enderror" 
                                           id="storeCity" 
                                           wire:model="storeCity"
                                           placeholder="Buenos Aires">
                                    @error('storeCity') 
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="storeState" class="form-label">Provincia *</label>
                                    <input type="text" 
                                           class="form-control @error('storeState') is-invalid @enderror" 
                                           id="storeState" 
                                           wire:model="storeState"
                                           placeholder="CABA">
                                    @error('storeState') 
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="storeZipCode" class="form-label">Código Postal</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="storeZipCode" 
                                           wire:model="storeZipCode"
                                           placeholder="1000">
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="storeLatitude" class="form-label">Latitud</label>
                                    <input type="text" 
                                           class="form-control @error('storeLatitude') is-invalid @enderror" 
                                           id="storeLatitude" 
                                           wire:model="storeLatitude"
                                           placeholder="-34.603722">
                                    @error('storeLatitude') 
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="storeLongitude" class="form-label">Longitud</label>
                                    <input type="text" 
                                           class="form-control @error('storeLongitude') is-invalid @enderror" 
                                           id="storeLongitude" 
                                           wire:model="storeLongitude"
                                           placeholder="-58.381592">
                                    @error('storeLongitude') 
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <small>
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Nota:</strong> La dirección y coordenadas son importantes para que los clientes puedan ubicar su tienda en la app de MercadoPago.
                                </small>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancelar</button>
                        <button type="button" class="btn btn-primary" wire:click="saveStore" :disabled="loading">
                            <span wire:loading.remove wire:target="saveStore">
                                <i class="fas fa-save"></i> {{ $storeId ? 'Actualizar' : 'Crear' }}
                            </span>
                            <span wire:loading wire:target="saveStore">
                                <i class="fas fa-spinner fa-spin"></i> Guardando...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal para crear/editar caja --}}
    @if($showPosModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ $posId ? 'Editar Caja' : 'Nueva Caja' }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeModal">Cerrar</button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="savePos">
                            <div class="mb-3">
                                <label for="posName" class="form-label">Nombre de la Caja *</label>
                                <input type="text" 
                                       class="form-control @error('posName') is-invalid @enderror" 
                                       id="posName" 
                                       wire:model="posName"
                                       placeholder="Ej: Caja Principal">
                                @error('posName') 
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>



                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="posFixedAmount" 
                                           wire:model="posFixedAmount">
                                    <label class="form-check-label" for="posFixedAmount">
                                        Monto Fijo
                                    </label>
                                </div>
                                <small class="text-muted">
                                    Si está activado, el cliente no podrá modificar el monto del pago.
                                </small>
                            </div>

                            <div class="alert alert-success">
                                <small>
                                    <i class="fas fa-qrcode"></i>
                                    <strong>Al crear la caja se generará automáticamente un código QR estático.</strong>
                                </small>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancelar</button>
                        <button type="button" class="btn btn-primary" wire:click="savePos" :disabled="loading">
                            <span wire:loading.remove wire:target="savePos">
                                <i class="fas fa-save"></i> {{ $posId ? 'Actualizar' : 'Crear' }}
                            </span>
                            <span wire:loading wire:target="savePos">
                                <i class="fas fa-spinner fa-spin"></i> Guardando...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif




</div>

@push('styles')
<style>
    .modal.show {
        display: block;
    }
    
    .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
    }
</style>
@endpush


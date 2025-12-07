<div class="container">
    <article>
        <header>
            <hgroup>
                <h2>Gestión de QR MercadoPago</h2>
                @if($empresa)
                    <p>Empresa: {{ $empresa->name ?? 'Sin nombre' }}</p>
                @endif
            </hgroup>
            @if($mpConfigured)
                <button wire:click="openStoreModal" class="outline">
                    <i class="fas fa-plus"></i> Nueva Tienda
                </button>
            @endif
        </header>

        <section>
            {{-- Mensajes de alerta --}}
            @if($successMessage)
                <ins>
                    <i class="fas fa-check-circle"></i> {{ $successMessage }}
                    <button wire:click="$set('successMessage', '')" class="close" aria-label="Cerrar">&times;</button>
                </ins>
            @endif

            @if($errorMessage)
                <mark>
                    <i class="fas fa-exclamation-triangle"></i> {{ $errorMessage }}
                    <button wire:click="$set('errorMessage', '')" class="close" aria-label="Cerrar">&times;</button>
                </mark>
            @endif

            {{-- Mensaje si no está configurado MP --}}
            @if(!$mpConfigured)
                <mark>
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>Atención:</strong> Configure las credenciales de MercadoPago en su empresa antes de crear tiendas y cajas.
                </mark>
            @endif

            {{-- Loading overlay --}}
            @if($loading)
                <div class="loading-overlay" aria-busy="true">
                    <div class="loading-spinner">
                        <span aria-label="Cargando..."></span>
                    </div>
                </div>
            @endif

            {{-- Lista de tiendas --}}
            @if($stores->count() > 0)
                @foreach($stores as $store)
                    <article class="store-card">
                        {{-- Header de tienda --}}
                        <header>
                            <div class="store-header">
                                <div class="store-info">
                                    <h3>
                                        <i class="fas fa-store"></i>
                                        {{ $store->name }}
                                    </h3>
                                    <p>
                                        <i class="fas fa-map-marker-alt"></i>
                                        {{ $store->full_address }}
                                    </p>
                                    @if($store->mp_store_id)
                                        <small><kbd>ID MP: {{ $store->mp_store_id }}</kbd></small>
                                    @endif
                                </div>
                                <div class="store-actions">
                                    <button wire:click="openStoreModal({{ $store->id }})" 
                                            class="outline secondary" 
                                            title="Editar tienda">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button wire:click="openPosModal({{ $store->id }})" 
                                            class="outline" 
                                            title="Agregar caja">
                                        <i class="fas fa-plus"></i> Caja
                                    </button>
                                    <button wire:click="deleteStore({{ $store->id }})" 
                                            onclick="return confirm('¿Está seguro de eliminar esta tienda y todas sus cajas?')"
                                            class="outline contrast" 
                                            title="Eliminar tienda">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </header>

                        {{-- Cajas de la tienda --}}
                        <div class="pos-container">
                            @if($store->pos->count() > 0)
                                <div class="grid">
                                    @foreach($store->pos as $pos)
                                        <article class="pos-card {{ !$pos->active ? 'inactive' : '' }}">
                                            <header>
                                                <div class="pos-header">
                                                    <h5>
                                                        <i class="fas fa-cash-register"></i>
                                                            <h3>{{ $pos->name }}</h3>
                                                        <small>
                                                            <h6>
                                                                Usuario: {{ $pos->usuario ? $pos->usuario->name : 'No asignado' }}
                                                                
                                                            </h6>
                                                        </small>
                                                    </h5>
                                                    <div class="pos-actions">
                                                        @if($pos->qr_url)
                                                            <a href="{{ $pos->qr_url }}" 
                                                               target="_blank"
                                                               role="button"
                                                               class="outline secondary" 
                                                               title="Ver QR">
                                                                <i class="fas fa-qrcode"></i>
                                                            </a>
                                                        @endif
                                                        <button wire:click="deletePos({{ $pos->id }})" 
                                                                onclick="return confirm('¿Está seguro de eliminar esta caja?')"
                                                                class="outline contrast" 
                                                                title="Eliminar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </header>
                                            
                                            <div class="pos-details">
                                                <p>
                                                    <small>
                                                        <kbd class="{{ $pos->fixed_amount === 'true' ? 'success' : 'warning' }}">
                                                            {{ $pos->fixed_amount === 'true' ? 'Monto Fijo' : 'Monto Variable' }}
                                                        </kbd>
                                                    </small>
                                                </p>
                                                
                                                @if($pos->category)
                                                    <p>
                                                        <small>
                                                            <i class="fas fa-tag"></i> 
                                                            {{ $categories[$pos->category] ?? $pos->category }}
                                                        </small>
                                                    </p>
                                                @endif
                                                
                                                @if($pos->mp_pos_id)
                                                    <p>
                                                        <small>
                                                            <i class="fas fa-id-card"></i> 
                                                            {{ $pos->mp_pos_id }}
                                                        </small>
                                                    </p>
                                                @endif
                                            </div>

                                            <footer>
                                                <small>
                                                    <kbd class="{{ $pos->active ? 'success' : '' }}">
                                                        {{ $pos->active ? '● Activa' : '○ Inactiva' }}
                                                    </kbd>
                                                </small>
                                            </footer>

                                            {{-- Mostrar QR si está disponible --}}
                                            @if($pos->qr_code)
                                                <div class="qr-code-container">
                                                    <img src="{{ $pos->qr_code }}" 
                                                         alt="QR Code" 
                                                         class="qr-code">
                                                </div>
                                            @endif
                                        </article>
                                    @endforeach
                                </div>
                            @else
                                <p>
                                    <i class="fas fa-info-circle"></i>
                                    No hay cajas configuradas para esta tienda.
                                    <button wire:click="openPosModal({{ $store->id }})" 
                                            class="secondary">
                                        Crear primera caja
                                    </button>
                                </p>
                            @endif
                        </div>
                    </article>
                @endforeach

                {{-- Paginación --}}
                <div>
                    {{ $stores->links() }}
                </div>
            @else
                <p>
                    <i class="fas fa-info-circle"></i>
                    No hay tiendas configuradas. 
                    @if($mpConfigured)
                        <button wire:click="openStoreModal" class="secondary">
                            Crear primera tienda
                        </button>
                    @endif
                </p>
            @endif
        </section>
    </article>

    {{-- Modal para crear/editar tienda --}}
    @if($showStoreModal)
        <dialog open>
            <article>
                <header>
                    <h3>{{ $storeId ? 'Editar Tienda' : 'Nueva Tienda' }}</h3>
                    <button wire:click="closeModal" aria-label="Cerrar" class="close"></button>
                </header>
                
                <form wire:submit.prevent="saveStore">
                    <div class="grid">
                        <label>
                            Nombre de la Tienda *
                            <input type="text" 
                                   name="storeName"
                                   wire:model="storeName"
                                   placeholder="Ej: Sucursal Centro"
                                   aria-invalid="@error('storeName') true @enderror"
                                   required>
                            @error('storeName') 
                                <small class="error">{{ $message }}</small>
                            @enderror
                        </label>
                    </div>

                    <div class="grid">
                        <label>
                            Calle *
                            <input type="text" 
                                   name="storeStreet"
                                   wire:model="storeStreet"
                                   placeholder="Av. Principal"
                                   aria-invalid="@error('storeStreet') true @enderror"
                                   required>
                            @error('storeStreet') 
                                <small class="error">{{ $message }}</small>
                            @enderror
                        </label>

                        <label>
                            Número
                            <input type="text" 
                                   name="storeNumber"
                                   wire:model="storeNumber"
                                   placeholder="1234">
                        </label>
                    </div>

                    <div class="grid">
                        <label>
                            Ciudad *
                            <input type="text" 
                                   name="storeCity"
                                   wire:model="storeCity"
                                   placeholder="Buenos Aires"
                                   aria-invalid="@error('storeCity') true @enderror"
                                   required>
                            @error('storeCity') 
                                <small class="error">{{ $message }}</small>
                            @enderror
                        </label>

                        <label>
                            Provincia *
                            <input type="text" 
                                   name="storeState"
                                   wire:model="storeState"
                                   placeholder="CABA"
                                   aria-invalid="@error('storeState') true @enderror"
                                   required>
                            @error('storeState') 
                                <small class="error">{{ $message }}</small>
                            @enderror
                        </label>
                    </div>

                    <div class="grid">
                        <label>
                            Código Postal
                            <input type="text" 
                                   name="storeZipCode"
                                   wire:model="storeZipCode"
                                   placeholder="1000">
                        </label>

                        <label>
                            Latitud
                            <input type="text" 
                                   name="storeLatitude"
                                   wire:model="storeLatitude"
                                   placeholder="-34.603722"
                                   aria-invalid="@error('storeLatitude') true @enderror">
                            @error('storeLatitude') 
                                <small class="error">{{ $message }}</small>
                            @enderror
                        </label>

                        <label>
                            Longitud
                            <input type="text" 
                                   name="storeLongitude"
                                   wire:model="storeLongitude"
                                   placeholder="-58.381592"
                                   aria-invalid="@error('storeLongitude') true @enderror">
                            @error('storeLongitude') 
                                <small class="error">{{ $message }}</small>
                            @enderror
                        </label>
                    </div>

                    <ins>
                        <small>
                            <i class="fas fa-info-circle"></i>
                            <strong>Nota:</strong> La dirección y coordenadas son importantes para que los clientes puedan ubicar su tienda en la app de MercadoPago.
                        </small>
                    </ins>
                </form>

                <footer>
                    <button type="button" class="secondary" wire:click="closeModal">Cancelar</button>
                    <button type="button" wire:click="saveStore" aria-busy="{{ $loading ? 'true' : 'false' }}">
                        @if($loading)
                            Guardando...
                        @else
                            <i class="fas fa-save"></i> {{ $storeId ? 'Actualizar' : 'Crear' }}
                        @endif
                    </button>
                </footer>
            </article>
        </dialog>
    @endif


    {{-- Modal para crear/editar caja --}}
    @if($showPosModal)
        <dialog open>
            <article>
                <header>
                    <h3>{{ $posId ? 'Editar Caja' : 'Nueva Caja' }}</h3>
                    <button wire:click="closeModal" aria-label="Cerrar" class="close"></button>
                </header>

                <form wire:submit.prevent="savePos">
                    <label>
                        Nombre de la Caja *
                        <input type="text" 
                               name="posName"
                               wire:model="posName"
                               placeholder="Ej: Caja Principal"
                               aria-invalid="@error('posName') true @enderror"
                               required>
                        @error('posName') 
                            <small class="error">{{ $message }}</small>
                        @enderror
                    </label>

                    <select name="usuarioCaja" id="usuarioCaja" wire:model="usuarioIDCaja" required>
                        <option value="">Asignar Usuario a la Caja</option>
                        @foreach($usuarios as $usuario)
                            <option value="{{ $usuario->id }}">{{ $usuario->name }} ({{ $usuario->email }})</option>
                        @endforeach
                    </select>

                    <label>
                        <input type="checkbox" 
                               name="posFixedAmount"
                               wire:model="posFixedAmount"
                               role="switch">
                        Monto Fijo
                        <br>
                        <small>Si está activado, el cliente no podrá modificar el monto del pago.</small>
                    </label>

                    <ins>
                        <small>
                            <i class="fas fa-qrcode"></i>
                            <strong>Al crear la caja se generará automáticamente un código QR estático.</strong>
                        </small>
                    </ins>
                </form>

                <footer>
                    <button type="button" class="secondary" wire:click="closeModal">Cancelar</button>
                    <button type="button" wire:click="savePos" aria-busy="{{ $loading ? 'true' : 'false' }}">
                        @if($loading)
                            Guardando...
                        @else
                            <i class="fas fa-save"></i> {{ $posId ? 'Actualizar' : 'Crear' }}
                        @endif
                    </button>
                </footer>
            </article>
        </dialog>
    @endif
</div>

@push('styles')
<style>
    /* Pico CSS Custom Styles */
    
    /* Header con botón alineado */
    article > header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    article > header hgroup {
        flex: 1;
        margin: 0;
    }
    
    article > header button {
        flex-shrink: 0;
    }

    /* Store card styling */
    .store-card {
        margin-bottom: 2rem;
        border: 1px solid var(--pico-muted-border-color);
    }

    .store-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .store-info {
        flex: 1;
    }

    .store-info h3 {
        margin-bottom: 0.5rem;
        color: var(--pico-primary);
    }

    .store-info p {
        margin-bottom: 0.5rem;
        color: var(--pico-muted-color);
    }

    .store-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .store-actions button {
        margin: 0;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }

    /* POS container */
    .pos-container {
        padding: 1.5rem;
    }

    /* Grid for POS cards */
    .grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }

    /* POS card styling */
    .pos-card {
        margin: 0;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .pos-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--pico-card-box-shadow);
    }

    .pos-card.inactive {
        opacity: 0.7;
        background: var(--pico-card-background-color);
    }

    .pos-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .pos-header h5 {
        margin: 0;
        font-size: 1.1rem;
    }

    .pos-actions {
        display: flex;
        gap: 0.25rem;
    }

    .pos-actions button,
    .pos-actions a {
        margin: 0;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }

    .pos-details p {
        margin-bottom: 0.5rem;
    }

    .pos-details small {
        color: var(--pico-muted-color);
    }

    /* QR code styling */
    .qr-code-container {
        text-align: center;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid var(--pico-muted-border-color);
    }

    .qr-code {
        max-width: 150px;
        height: auto;
        border-radius: var(--pico-border-radius);
    }

    /* Badge variants */
    kbd.success {
        background: var(--pico-ins-color);
        color: var(--pico-background-color);
    }

    kbd.warning {
        background: #ff9800;
        color: var(--pico-background-color);
    }

    /* Alert close button */
    ins, mark {
        position: relative;
        padding-right: 2.5rem;
    }

    ins .close, mark .close {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        background: transparent;
        border: none;
        font-size: 1.5rem;
        line-height: 1;
        cursor: pointer;
        padding: 0;
        margin: 0;
        color: inherit;
    }

    /* Loading overlay */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .loading-spinner {
        width: 3rem;
        height: 3rem;
        border: 0.25rem solid var(--pico-primary-background);
        border-top-color: transparent;
        border-radius: 50%;
        animation: spinner 0.6s linear infinite;
    }

    @keyframes spinner {
        to {
            transform: rotate(360deg);
        }
    }

    /* Dialog styling */
    dialog {
        max-width: 600px;
    }

    dialog[open] {
        animation: slide-up 0.3s ease-out;
    }

    @keyframes slide-up {
        from {
            transform: translateY(20px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    dialog header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    dialog header .close {
        background: transparent;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0;
        margin: 0;
        line-height: 1;
    }

    dialog footer {
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
    }

    /* Error messages */
    small.error {
        color: var(--pico-del-color);
        display: block;
        margin-top: 0.25rem;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .store-header,
        .pos-header {
            flex-direction: column;
        }

        .store-actions,
        .pos-actions {
            width: 100%;
            justify-content: flex-start;
        }
    }
</style>
@endpush

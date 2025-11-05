<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\MercadoPagoStore;
use App\Models\MercadoPagoPOS;
use App\Models\Empresa;
use App\Services\MercadoPago\MercadoPagoQRService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MercadoPagoQrManager extends Component
{
    use WithPagination;

    // Propiedades para el modal de tienda
    public $showStoreModal = false;
    public $storeId = null;
    public $storeName = '';
    public $storeStreet = '';
    public $storeNumber = '';
    public $storeCity = '';
    public $storeState = '';
    public $storeZipCode = '';
    public $storeLatitude = '';
    public $storeLongitude = '';

    // Propiedades para el modal de caja
    public $showPosModal = false;
    public $selectedStoreId = null;
    public $posId = null;
    public $posName = '';
    public $posFixedAmount = true;
    public $posCategory = '';

    // Propiedades de estado
    public $loading = false;
    public $successMessage = '';
    public $errorMessage = '';
    public $empresa = null;
    public $mpConfigured = false;




    protected $rules = [
        'storeName' => 'required|string|min:3|max:100',
        'storeStreet' => 'required|string|max:100',
        'storeNumber' => 'nullable|string|max:20',
        'storeCity' => 'required|string|max:50',
        'storeState' => 'required|string|max:50',
        'storeZipCode' => 'nullable|string|max:20',
        'storeLatitude' => 'nullable|numeric|between:-90,90',
        'storeLongitude' => 'nullable|numeric|between:-180,180',
    ];

    protected $messages = [
        'storeName.required' => 'El nombre de la tienda es obligatorio',
        'storeName.min' => 'El nombre debe tener al menos 3 caracteres',
        'storeStreet.required' => 'La calle es obligatoria',
        'storeCity.required' => 'La ciudad es obligatoria',
        'storeState.required' => 'La provincia es obligatoria',
    ];

    public function mount()
    {
        $this->loadEmpresaConfig();

    }

    /**
     * Cargar configuración de la empresa
     */
    public function loadEmpresaConfig()
    {
        $user = Auth::user();
        
        if (!$user || !$user->empresa_id) {
            $this->errorMessage = 'Usuario sin empresa asignada';
            return;
        }

        $this->empresa = Empresa::find($user->empresa_id);
        
        if (!$this->empresa) {
            $this->errorMessage = 'Empresa no encontrada';
            return;
        }

        // Verificar si tiene credenciales de MercadoPago configuradas
        $this->mpConfigured = !empty($this->empresa->MP_ACCESS_TOKEN) && 
                             !empty($this->empresa->MP_PUBLIC_KEY) &&
                             !empty($this->empresa->MP_USER_ID);

        if (!$this->mpConfigured) {
            if (empty($this->empresa->MP_USER_ID)) {
                $this->errorMessage = 'Falta configurar el USER_ID de MercadoPago. Por favor, configure el campo MP_USER_ID en la empresa.';
            } else {
                $this->errorMessage = 'Las credenciales de MercadoPago no están configuradas para esta empresa';
            }
        }
    }

    /**
     * Abrir modal para crear/editar tienda
     */
    public function openStoreModal($storeId = null)
    {
        if (!$this->mpConfigured) {
            $this->errorMessage = 'Configure las credenciales de MercadoPago primero';
            return;
        }

        $this->resetStoreForm();
        
        if ($storeId) {
            $store = MercadoPagoStore::where('id', $storeId)
                ->where('empresa_id', $this->empresa->id)
                ->first();
                
            if ($store) {
                $this->storeId = $store->id;
                $this->storeName = $store->name;
                $this->storeStreet = $store->address_street_name;
                $this->storeNumber = $store->address_street_number;
                $this->storeCity = $store->address_city;
                $this->storeState = $store->address_state;
                $this->storeZipCode = $store->address_zip_code;
                $this->storeLatitude = $store->address_latitude;
                $this->storeLongitude = $store->address_longitude;
            }
        }
        
        $this->showStoreModal = true;
    }

    /**
     * Guardar tienda
     */
    public function saveStore()
    {
        $this->validate();
        
        $this->loading = true;
        $this->clearMessages();

        try {
            // Validar el formato del Access Token
            $accessToken = $this->empresa->MP_ACCESS_TOKEN;
            
            // El Access Token correcto debe tener este formato:
            // APP_USR-XXXXXXXX-XXXXXX-XXXXXXXXXXXXXXXXXXXXXXXX-XXXXXXXXX
            // Y debe tener al menos 60 caracteres
            if (strlen($accessToken) < 60) {
                throw new \Exception(
                    'El Access Token configurado parece ser incorrecto. ' .
                    'Debe ser el token completo de producción/prueba, no solo el identificador. ' .
                    'Token actual: ' . substr($accessToken, 0, 20) . '... (longitud: ' . strlen($accessToken) . ' caracteres). ' .
                    'Se esperan al menos 60 caracteres.'
                );
            }

            // Configurar credenciales de MercadoPago dinámicamente
            config([
                'services.mercadopago.access_token' => $accessToken,
                'services.mercadopago.user_id' => $this->empresa->MP_USER_ID,
                'services.mercadopago.sandbox' => false
            ]);

            // Verificar que se configuró correctamente
            \Log::info('MercadoPago Config', [
                'access_token_length' => strlen($accessToken),
                'access_token_prefix' => substr($accessToken, 0, 20) . '...',
                'user_id' => config('services.mercadopago.user_id'),
                'sandbox' => config('services.mercadopago.sandbox')
            ]);
            
            $qrService = new MercadoPagoQRService();
            
            // Preparar los datos de la ubicación
            $locationData = [
                'street_number' => $this->storeNumber ?: '',
                'street_name' => $this->storeStreet,
                'city_name' => $this->storeCity,
                'state_name' => $this->storeState,
                'reference' => ''
            ];

            // Solo agregar latitud/longitud si ambos están presentes
            if (!empty($this->storeLatitude) && !empty($this->storeLongitude)) {
                $locationData['latitude'] = floatval($this->storeLatitude);
                $locationData['longitude'] = floatval($this->storeLongitude);
            } else {
                // Valores por defecto si no se proporcionan (requeridos por la API)
                $locationData['latitude'] = -34.603722;
                $locationData['longitude'] = -58.381592;
            }

            $storeData = [
                'name' => $this->storeName,
                'location' => $locationData
            ];

            if ($this->storeId) {
                // Actualizar tienda existente
                $store = MercadoPagoStore::findOrFail($this->storeId);
                
                if ($store->mp_store_id) {
                    $response = $qrService->updateStore($store->mp_store_id, $storeData);
                }
                
                $store->update([
                    'name' => $this->storeName,
                    'address_street_name' => $this->storeStreet,
                    'address_street_number' => $this->storeNumber,
                    'address_city' => $this->storeCity,
                    'address_state' => $this->storeState,
                    'address_zip_code' => $this->storeZipCode,
                    'address_latitude' => $this->storeLatitude,
                    'address_longitude' => $this->storeLongitude,
                ]);
                
                $this->successMessage = 'Tienda actualizada exitosamente';
            } else {
                // Crear nueva tienda
                // El external_id debe ser alfanumérico (sin guiones bajos ni caracteres especiales)
                $externalId = 'store' . $this->empresa->id . Str::random(8);
                $storeData['external_id'] = $externalId;
                
                \Log::info('MercadoPagoQR - Intentando crear tienda', [
                    'store_data' => $storeData
                ]);
                
                $response = $qrService->createStore($storeData);
                
                \Log::info('MercadoPagoQR - Respuesta del servicio', [
                    'response' => $response
                ]);
                
                if (isset($response['id'])) {
                    MercadoPagoStore::create([
                        'empresa_id' => $this->empresa->id,
                        'external_id' => $externalId,
                        'mp_store_id' => $response['id'],
                        'name' => $this->storeName,
                        'location' => $storeData['location'],
                        'address_street_name' => $this->storeStreet,
                        'address_street_number' => $this->storeNumber,
                        'address_city' => $this->storeCity,
                        'address_state' => $this->storeState,
                        'address_zip_code' => $this->storeZipCode,
                        'address_latitude' => $this->storeLatitude ?: $locationData['latitude'],
                        'address_longitude' => $this->storeLongitude ?: $locationData['longitude'],
                    ]);
                    
                    $this->successMessage = 'Tienda creada exitosamente';
                } else {
                    throw new \Exception('Error al crear la tienda en MercadoPago: ' . json_encode($response));
                }
            }
            
            $this->showStoreModal = false;
            $this->resetStoreForm();
            
        } catch (\Exception $e) {
            $this->errorMessage = 'Error: ' . $e->getMessage();
            \Log::error('Error al guardar tienda', [
                'error' => $e->getMessage(),
                'empresa_id' => $this->empresa->id
            ]);
        } finally {
            $this->loading = false;
        }
    }

    /**
     * Eliminar tienda
     */
    public function deleteStore($storeId)
    {
        $this->loading = true;
        $this->clearMessages();

        try {
            $store = MercadoPagoStore::where('id', $storeId)
                ->where('empresa_id', $this->empresa->id)
                ->firstOrFail();

            // Eliminar en MercadoPago si existe
            if ($store->mp_store_id) {
                config([
                    'services.mercadopago.access_token' => $this->empresa->MP_ACCESS_TOKEN,
                    'services.mercadopago.user_id' => $this->empresa->MP_USER_ID,
                    'services.mercadopago.sandbox' => false
                ]);
                
                $qrService = new MercadoPagoQRService();
                $qrService->deleteStore($store->mp_store_id);
            }

            // Eliminar de la base de datos (las cajas se eliminan en cascada)
            $store->delete();

            $this->successMessage = 'Tienda eliminada exitosamente';
            
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al eliminar tienda: ' . $e->getMessage();
        } finally {
            $this->loading = false;
        }
    }

    /**
     * Abrir modal para crear/editar caja
     */
    public function openPosModal($storeId, $posId = null)
    {
        $this->resetPosForm();
        $this->selectedStoreId = $storeId;
        
        if ($posId) {
            $pos = MercadoPagoPOS::where('id', $posId)
                ->where('mercadopago_store_id', $storeId)
                ->first();
                
            if ($pos) {
                $this->posId = $pos->id;
                $this->posName = $pos->name;
                $this->posFixedAmount = $pos->fixed_amount === 'true';
                $this->posCategory = $pos->category;
            }
        }
        
        $this->showPosModal = true;
    }

    /**
     * Guardar caja (POS)
     */
    public function savePos()
    {
        $this->validate([
            'posName' => 'required|string|min:3|max:100',
            'posCategory' => 'nullable|string',
        ], [
            'posName.required' => 'El nombre de la caja es obligatorio',
            'posName.min' => 'El nombre debe tener al menos 3 caracteres',
        ]);

        $this->loading = true;
        $this->clearMessages();

        try {
            $store = MercadoPagoStore::where('id', $this->selectedStoreId)
                ->where('empresa_id', $this->empresa->id)
                ->firstOrFail();

            config([
                'services.mercadopago.access_token' => $this->empresa->MP_ACCESS_TOKEN,
                'services.mercadopago.user_id' => $this->empresa->MP_USER_ID,
                'services.mercadopago.sandbox' => false
            ]);
            
            $qrService = new MercadoPagoQRService();
            
            $posData = [
                'name' => $this->posName,
                'fixed_amount' => $this->posFixedAmount,
            ];

            if ($this->posCategory) {
                $posData['category'] = $this->posCategory;
            }

            if ($this->posId) {
                // Actualizar caja existente
                $pos = MercadoPagoPOS::findOrFail($this->posId);
                
                $pos->update([
                    'name' => $this->posName,
                    'fixed_amount' => $this->posFixedAmount ? 'true' : 'false',
                    'category' => $this->posCategory,
                ]);
                
                $this->successMessage = 'Caja actualizada exitosamente';
            } else {
                // Crear nueva caja
                // El external_id debe ser alfanumérico (sin guiones bajos ni caracteres especiales)
                $externalId = 'pos' . $store->id . Str::random(8);
                $posData['external_id'] = $externalId;
                $posData['store_id'] = $store->mp_store_id;
                $posData['external_store_id'] = $store->external_id;
                
                // Nota: El parámetro 'category' es opcional y debe ser un código numérico MCC
                // Si no se envía, se usa una categoría genérica
                // Solo se soportan categorías de Gastronomía y Estación de servicio
                // Por ahora lo omitimos para evitar errores
                if (isset($posData['category'])) {
                    unset($posData['category']);
                }
                
                $response = $qrService->createPOS($posData);
                
                \Log::info('MercadoPagoQR - Respuesta al crear caja', [
                    'response' => $response
                ]);
                
                // La respuesta viene en formato: ['success' => true, 'pos_id' => ..., 'data' => [...]]
                if (isset($response['success']) && $response['success'] && isset($response['data']['id'])) {
                    $responseData = $response['data'];
                    
                    MercadoPagoPOS::create([
                        'mercadopago_store_id' => $store->id,
                        'external_id' => $externalId,
                        'mp_pos_id' => $responseData['id'],
                        'name' => $this->posName,
                        'fixed_amount' => $this->posFixedAmount ? 'true' : 'false',
                        'category' => $this->posCategory,
                        'qr_code' => $response['qr_code_image'] ?? null,
                        'qr_url' => $response['qr_code_template'] ?? null,
                        'uuid' => $response['uuid'] ?? null,
                        'status' => $responseData['status'] ?? 'active',
                        'qr_data' => $responseData['qr_code'] ?? null,
                        'active' => true,
                    ]);
                    
                    $this->successMessage = 'Caja creada exitosamente con código QR';
                } else {
                    throw new \Exception('Error al crear la caja en MercadoPago: ' . json_encode($response));
                }
            }
            
            $this->showPosModal = false;
            $this->resetPosForm();
            
        } catch (\Exception $e) {
            $this->errorMessage = 'Error: ' . $e->getMessage();
            \Log::error('Error al guardar caja', [
                'error' => $e->getMessage(),
                'store_id' => $this->selectedStoreId
            ]);
        } finally {
            $this->loading = false;
        }
    }

    /**
     * Eliminar caja
     */
    public function deletePos($posId)
    {
        $this->loading = true;
        $this->clearMessages();

        try {
            $pos = MercadoPagoPOS::findOrFail($posId);
            $store = $pos->store;

            if ($store->empresa_id !== $this->empresa->id) {
                throw new \Exception('No autorizado');
            }

            // Eliminar en MercadoPago si existe
            if ($pos->mp_pos_id) {
                config([
                    'services.mercadopago.access_token' => $this->empresa->MP_ACCESS_TOKEN,
                    'services.mercadopago.user_id' => $this->empresa->MP_USER_ID,
                    'services.mercadopago.sandbox' => false
                ]);
                
                $qrService = new MercadoPagoQRService();
                $qrService->deletePOS($pos->mp_pos_id);
            }

            $pos->delete();

            $this->successMessage = 'Caja eliminada exitosamente';
            
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al eliminar caja: ' . $e->getMessage();
        } finally {
            $this->loading = false;
        }
    }

    /**
     * Descargar QR de una caja
     */
    public function downloadQR($posId)
    {
        try {
            $pos = MercadoPagoPOS::findOrFail($posId);
            
            if (!$pos->qr_url) {
                $this->errorMessage = 'Esta caja no tiene un código QR disponible';
                return;
            }

            return redirect($pos->qr_url);
            
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al descargar QR: ' . $e->getMessage();
        }
    }

    /**
     * Reset formulario de tienda
     */
    private function resetStoreForm()
    {
        $this->storeId = null;
        $this->storeName = '';
        $this->storeStreet = '';
        $this->storeNumber = '';
        $this->storeCity = '';
        $this->storeState = '';
        $this->storeZipCode = '';
        $this->storeLatitude = '';
        $this->storeLongitude = '';
        $this->resetErrorBag();
    }

    /**
     * Reset formulario de caja
     */
    private function resetPosForm()
    {
        $this->posId = null;
        $this->posName = '';
        $this->posFixedAmount = true;
        $this->posCategory = '';
        $this->resetErrorBag();
    }

    /**
     * Limpiar mensajes
     */
    private function clearMessages()
    {
        $this->successMessage = '';
        $this->errorMessage = '';
    }

    /**
     * Cerrar modales
     */
    public function closeModal()
    {
        $this->showStoreModal = false;
        $this->showPosModal = false;
        $this->resetStoreForm();
        $this->resetPosForm();
    }

    /**
     * Render del componente
     */
    public function render()
    {
        $stores = [];
        
        if ($this->empresa) {
            $stores = MercadoPagoStore::where('empresa_id', $this->empresa->id)
                ->with(['pos' => function($query) {
                    $query->orderBy('created_at', 'desc');
                }])
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        }

        return view('livewire.mercado-pago-qr-manager', [
            'stores' => $stores,
        ])
        ->extends('principal.principal')
            ->section('body');

    }
}

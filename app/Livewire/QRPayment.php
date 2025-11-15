<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\MercadoPagoPOS;
use App\Models\MercadoPagoQROrder;
use App\Services\MercadoPago\MercadoPagoQRService;
use Illuminate\Support\Str;

class QRPayment extends Component
{
    public $posId;
    public $amount = '';
    public $description = '';
    public $orderId = null;
    public $orderReference = null;
    public $orderStatus = null;
    public $paymentId = null;
    public $showQR = false;
    public $qrData = null;


    
    // Para polling
    public $polling = false;
    
    protected $rules = [
        'amount' => 'required|numeric|min:0.01',
        'description' => 'nullable|string|max:255',
    ];

    public function mount($posId = null)
    {
        // dd(MercadoPagoPOS::first()->mp_pos_id);

        $this->posId = $posId ?? MercadoPagoPOS::first()->id;

    }

    /**
     * Crear orden de pago QR
     */
    public function createOrder()
    {
        $this->validate();

        try {
            $pos = MercadoPagoPOS::findOrFail($this->posId);
            
            // Configurar credenciales
            config([
                'services.mercadopago.access_token' => $pos->store->empresa->MP_ACCESS_TOKEN,
                'services.mercadopago.user_id' => $pos->store->empresa->MP_USER_ID,
                'services.mercadopago.sandbox' => false
            ]);
            
            $qrService = new MercadoPagoQRService();
            
            // Generar referencia única
            $this->orderReference = 'QR-' . time() . '-' . Str::random(6);
            

            $baseUrl = config('app.env') === 'local' ? 'https://prepositionally-vacciniaceous-irving.ngrok-free.dev' : config('app.url');
            // Datos de la orden
            $orderData = [
                'external_reference' => $this->orderReference,
                'title' => $this->description ?: 'Pago en caja',
                'description' => $this->description ?: 'Cobro mediante código QR',
                'total_amount' => floatval($this->amount),
                'notification_url' => env('APP_ENV') == 'local' ? $baseUrl .'/api/mercadopago/webhook/qr' : route('api.mercadopago.webhook.qr'),
                'items' => [
                    [
                        'sku_number' => 'ITEM-001',
                        'category' => 'marketplace',
                        'title' => $this->description ?: 'Producto/Servicio',
                        'description' => $this->description ?: 'Cobro mediante código QR',
                        'unit_price' => floatval($this->amount),
                        'quantity' => 1,
                        'unit_measure' => 'unit',
                        'total_amount' => floatval($this->amount)
                    ]
                ],
                // ...existing code...
                'expiration_date' => now()->addMinutes(10)->format('Y-m-d\TH:i:s.vP')
                // Genera: 2025-11-08T21:42:17.000-03:00

            ];
            
            // Crear orden en MercadoPago
            $response = $qrService->createQROrder($pos->external_id, $orderData);
            
            if (!$response['success']) {
                session()->flash('error', 'Error al crear orden: ' . $response['error']);
                return;
            }
            
            // Guardar orden en BD
            $order = MercadoPagoQROrder::create([
                'mercadopago_pos_id' => $pos->id,
                'external_reference' => $this->orderReference,
                'in_store_order_id' => $response['in_store_order_id'],
                'total_amount' => $this->amount,
                'status' => 'pending',
                'items' => $orderData['items'],
                'expires_at' => now()->addMinutes(10)
            ]);
            
            $this->orderId = $order->id;
            $this->orderStatus = 'pending';
            $this->showQR = true;
            $this->polling = true;
            $this->qrData = $response['qr_data'];
            
            // Dispatch evento para mostrar QR en el frontend
            $this->dispatch('qr-created', [
                'orderId' => $this->orderId,
                'amount' => $this->amount,
                'qrData' => $this->qrData
            ]);
            
            session()->flash('success', 'Orden creada. Escanea el código QR para pagar.');
            
        } catch (\Exception $e) {
            \Log::error('Error creando orden QR', [
                'error' => $e->getMessage()
            ]);
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Verificar estado del pago (polling)
     */
    public function checkPaymentStatus()
    {
        if (!$this->orderId) {
            return;
        }

        $order = MercadoPagoQROrder::find($this->orderId);
        
        if (!$order) {
            $this->polling = false;
            return;
        }

        $this->orderStatus = $order->status;
        
        if ($order->isPaid()) {
            $this->polling = false;
            $this->paymentId = $order->payment_id;
            
            // Dispatch evento de pago exitoso
            $this->dispatch('payment-successful', [
                'orderId' => $this->orderId,
                'paymentId' => $this->paymentId,
                'amount' => $order->total_amount
            ]);
            
            session()->flash('success', '¡Pago recibido exitosamente!');
        } elseif ($order->status === 'expired' || $order->status === 'cancelled') {
            $this->polling = false;
            session()->flash('error', 'La orden ha expirado o fue cancelada.');
        }
    }

    /**
     * Cancelar orden
     */
    public function cancelOrder()
    {
        try {
            if ($this->orderId) {
                $order = MercadoPagoQROrder::find($this->orderId);
                
                if ($order && $order->isPending()) {
                    $pos = $order->pos;
                    
                    // Configurar credenciales
                    config([
                        'services.mercadopago.access_token' => $pos->store->empresa->MP_ACCESS_TOKEN,
                        'services.mercadopago.user_id' => $pos->store->empresa->MP_USER_ID,
                    ]);
                    
                    $qrService = new MercadoPagoQRService();
                    $qrService->deleteQROrder($pos->mp_pos_id);
                    
                    $order->update(['status' => 'cancelled']);
                }
            }
            
            $this->reset(['orderId', 'orderReference', 'orderStatus', 'paymentId', 'showQR', 'polling', 'amount', 'description']);
            session()->flash('info', 'Orden cancelada');
            
        } catch (\Exception $e) {
            \Log::error('Error cancelando orden', ['error' => $e->getMessage()]);
            session()->flash('error', 'Error al cancelar la orden');
        }
    }

    public function render()
    {
        return view('livewire.qr-payment')
        ->extends('principal.principal')
            ->section('body');
    }
}

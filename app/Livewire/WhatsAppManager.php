<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Empresa;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class WhatsAppManager extends Component
{
    public $instanciaWS = '';
    public $tokenWS = '';
    public $estado = null;
    public $empresa = null;
    public $whatsappConfigurado = false;

    public $successMessage = '';
    public $errorMessage = '';

    public $telefonoPrueba = '';
    public $mensajePrueba = '';
    public $enviandoPrueba = false;

    public $qrCode = null;
    public $pairingCode = null;
    public $conectando = false;

    protected $rules = [
        'instanciaWS' => 'nullable|string|max:255',
        'tokenWS' => 'nullable|string|max:255',
    ];

    protected $messages = [
        'instanciaWS.max' => 'La instancia no puede superar los 255 caracteres',
        'tokenWS.max' => 'El token no puede superar los 255 caracteres',
    ];

    public function mount()
    {
        $this->loadEmpresaConfig();
    }

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

        $this->instanciaWS = $this->empresa->instanciaWS;
        $this->tokenWS = $this->empresa->tokenWS;
        $this->whatsappConfigurado = !empty($this->empresa->instanciaWS) && !empty($this->empresa->tokenWS);

        $this->estado = $this->obtenerEstado();
    }

    public function refreshState()
    {
        $this->clearMessages();

        if ($this->empresa) {
            Cache::forget("whatsapp_state_{$this->empresa->id}");
        }

        $this->estado = $this->obtenerEstado();

        if ($this->estado === 'open') {
            $this->qrCode = null;
            $this->pairingCode = null;
            $this->conectando = false;
        }

        $this->successMessage = 'Estado actualizado';
    }

    public function connect()
    {
        $this->clearMessages();

        if (!$this->empresa || !$this->whatsappConfigurado) {
            $this->errorMessage = 'Configure la instancia y el token de WhatsApp primero';
            return;
        }

        $this->conectando = true;
        $this->qrCode = null;
        $this->pairingCode = null;

        try {
            $service = new WhatsAppService($this->empresa->instanciaWS, $this->empresa->tokenWS);
            $result = $service->connect($this->empresa->instanciaWS);

            if ($result['success']) {
                $this->qrCode = $result['base64'];
                $this->pairingCode = $result['pairingCode'];
                $this->successMessage = $result['message'] ?? 'Escaneá el QR para vincular el dispositivo.';

                if ($this->empresa) {
                    Cache::forget("whatsapp_state_{$this->empresa->id}");
                }
                $this->estado = $this->obtenerEstado();
            } else {
                $this->errorMessage = $result['message'] ?? 'Error al generar el código QR';
                $this->conectando = false;
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Error: ' . $e->getMessage();
            $this->conectando = false;
        }
    }

    public function logout()
    {
        $this->clearMessages();

        if (!$this->empresa || !$this->whatsappConfigurado) {
            $this->errorMessage = 'Configure la instancia y el token de WhatsApp primero';
            return;
        }

        try {
            $service = new WhatsAppService($this->empresa->instanciaWS, $this->empresa->tokenWS);
            $result = $service->logout($this->empresa->instanciaWS);

            if ($result['success']) {
                $this->successMessage = $result['message'] ?? 'Instancia desconectada correctamente';
            } else {
                $this->errorMessage = $result['message'] ?? 'Error al desconectar la instancia';
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Error: ' . $e->getMessage();
        }

        $this->qrCode = null;
        $this->pairingCode = null;
        $this->conectando = false;

        if ($this->empresa) {
            Cache::forget("whatsapp_state_{$this->empresa->id}");
        }
        $this->estado = $this->obtenerEstado();
    }

    public function saveConfig()
    {
        $this->validate();
        $this->clearMessages();

        if (!$this->empresa) {
            $this->errorMessage = 'Empresa no encontrada';
            return;
        }

        $this->empresa->update([
            'instanciaWS' => $this->instanciaWS,
            'tokenWS' => $this->tokenWS,
        ]);

        $this->whatsappConfigurado = !empty($this->instanciaWS) && !empty($this->tokenWS);

        if ($this->empresa) {
            Cache::forget("whatsapp_state_{$this->empresa->id}");
        }

        $this->estado = $this->obtenerEstado();
        $this->successMessage = 'Configuración guardada correctamente';
    }

    public function enviarPrueba()
    {
        $this->clearMessages();

        $this->validate([
            'telefonoPrueba' => 'required|string',
            'mensajePrueba' => 'required|string',
        ], [
            'telefonoPrueba.required' => 'El número es obligatorio',
            'mensajePrueba.required' => 'El mensaje es obligatorio',
        ]);

        if (!$this->empresa || !$this->whatsappConfigurado) {
            $this->errorMessage = 'Configure la instancia y el token de WhatsApp primero';
            return;
        }

        $this->enviandoPrueba = true;

        try {
            $service = new WhatsAppService($this->empresa->instanciaWS, $this->empresa->tokenWS);
            $result = $service->sendTextMessage($this->telefonoPrueba, $this->mensajePrueba);

            if ($result['success']) {
                $this->successMessage = $result['message'] ?? 'Mensaje de prueba enviado correctamente';
                $this->telefonoPrueba = '';
                $this->mensajePrueba = '';
            } else {
                $this->errorMessage = $result['message'] ?? 'Error al enviar el mensaje de prueba';
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Error: ' . $e->getMessage();
        } finally {
            $this->enviandoPrueba = false;
        }
    }

    private function obtenerEstado()
    {
        if (!$this->empresa || empty($this->empresa->instanciaWS) || empty($this->empresa->tokenWS)) {
            return null;
        }

        $result = Cache::remember(
            "whatsapp_state_{$this->empresa->id}",
            30,
            function () {
                $service = new WhatsAppService($this->empresa->instanciaWS, $this->empresa->tokenWS);
                $result = $service->getConnectionState($this->empresa->instanciaWS);
                return $result['state'];
            }
        );

        return $result;
    }

    private function clearMessages()
    {
        $this->successMessage = '';
        $this->errorMessage = '';
    }

    public function render()
    {
        return view('livewire.whatsapp-manager')
            ->extends('principal.principal')
            ->section('body');
    }
}
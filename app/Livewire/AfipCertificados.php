<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Empresa;
use App\Services\AfipService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class AfipCertificados extends Component
{
    public $empresaId = null;
    public $empresa = null;

    public $empresaSearch = '';
    public $empresaResults = [];

    public $entorno = 'dev';
    public $username = '';
    public $password = '';
    public $alias = '';

    public $loading = false;
    public $successMessage = '';
    public $errorMessage = '';

    protected $rules = [
        'entorno' => 'required|in:dev,prod',
        'username' => 'required|max:11', // max length 11 for AFIP username
        'password' => 'required|string',
        'alias' => 'required|string',
    ];

    protected $messages = [
        'entorno.required' => 'El entorno es obligatorio',
        'entorno.in' => 'El entorno debe ser dev o prod',
        'username.required' => 'El usuario es obligatorio',
        'password.required' => 'La contraseña es obligatoria',
        'alias.required' => 'El alias es obligatorio',
    ];

    public function mount($empresaId = null)
    {
        if ($empresaId) {
            $this->setEmpresa($empresaId);
            return;
        }

        $user = Auth::user();
        if ($user && $user->empresa_id) {
            $this->setEmpresa($user->empresa_id);
        }
    }

    public function updatedEmpresaSearch()
    {
        $query = trim($this->empresaSearch);

        if (mb_strlen($query) < 2) {
            $this->empresaResults = [];
            return;
        }

        $this->empresaResults = Empresa::query()
            ->where('nombre', 'like', "%{$query}%")
            ->orWhere('cuit', 'like', "%{$query}%")
            ->orderBy('nombre')
            ->limit(10)
            ->get()
            ->all();
    }

    public function selectEmpresa($empresaId)
    {
        $this->setEmpresa($empresaId);
    }

    public function resetEmpresa()
    {
        $this->empresa = null;
        $this->empresaId = null;
        $this->empresaSearch = '';
        $this->empresaResults = [];
        $this->clearMessages();
    }

    protected function setEmpresa($empresaId)
    {
        $this->empresa = Empresa::find($empresaId);
        $this->empresaId = $this->empresa?->id;

        $this->username = $this->empresa?->cuit; 
        $this->password = $this->empresa?->clave_fiscal ?? '';

        $this->empresaSearch = '';
        $this->empresaResults = [];
    }

    public function generarCertificados()
    {
        $this->validate();
        $this->clearMessages();

        if (!$this->empresa) {
            $this->errorMessage = 'Debe seleccionar una empresa';
            return;
        }

        if (empty($this->empresa->cuit)) {
            $this->errorMessage = 'La empresa seleccionada no tiene CUIT configurado';
            return;
        }

        $this->loading = true;

        try {
            $afipService = new AfipService($this->empresa->id, false);

            $data = [
                'cuit' => $this->empresa->cuit,
                'username' => (string) $this->username,
                'password' => $this->password,
                'alias' => $this->alias,
            ];

            $resultado = $this->entorno === 'prod'
                ? $afipService->generarCertificadosProduccion($data)
                : $afipService->generarCertificadosDesarrollo($data);

            if ($resultado['success']) {
                $this->successMessage = 'Certificados generados y guardados correctamente.';
                $this->password = '';
                return;
            }

            $this->errorMessage = 'Error al generar certificados: ' . ($resultado['error'] ?? 'Error desconocido');
        } catch (Exception $e) {
            $this->errorMessage = 'Error al generar certificados: ' . $e->getMessage();
            Log::error('Error generando certificados AFIP (Livewire)', [
                'empresa_id' => $this->empresa->id ?? null,
                'error' => $e->getMessage()
            ]);
        } finally {
            $this->loading = false;
        }
    }

    protected function clearMessages()
    {
        $this->successMessage = '';
        $this->errorMessage = '';
    }

    public function render()
    {
        return view('livewire.afip-certificados')
                ->extends('principal.principal')
        ->section('body'); 
    }
}

<?php

namespace App\Livewire;

use App\Models\Empresa;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EmpresaToken extends Component
{
    public int $empresaId;

    public string $nombreEmpresa = '';

    public ?string $tokenGenerado = null;

    public bool $mostrarModal = false;

    public bool $confirmandoRegeneracion = false;

    public bool $tieneToken = false;

    public function mount(int $empresaId): void
    {
        $this->empresaId = $empresaId;

        $empresa = Empresa::find($empresaId);

        if ($empresa) {
            $this->nombreEmpresa = $empresa->nombre;
            $this->tieneToken = ! empty($empresa->api_token);
        }
    }

    /**
     * Genera (o regenera) el token global de API de la empresa.
     * Si ya existe un token, primero pide confirmación.
     */
    public function generarToken(): void
    {
        $this->verificarSuper();

        $empresa = Empresa::findOrFail($this->empresaId);

        if (! empty($empresa->api_token) && ! $this->confirmandoRegeneracion) {
            $this->confirmandoRegeneracion = true;

            return;
        }

        $this->tokenGenerado = $empresa->generateApiToken();
        $this->tieneToken = true;
        $this->confirmandoRegeneracion = false;
        $this->mostrarModal = true;
    }

    public function cancelarRegeneracion(): void
    {
        $this->confirmandoRegeneracion = false;
    }

    public function cerrarModal(): void
    {
        $this->mostrarModal = false;
        $this->tokenGenerado = null;
    }

    /**
     * Solo un usuario Super puede generar/regenerar tokens de empresa.
     */
    private function verificarSuper(): void
    {
        $usuario = Auth::user();

        abort_unless(
            $usuario && $usuario->role && $usuario->role->nombre === 'Super',
            403
        );
    }

    public function render()
    {
        return view('livewire.empresa-token');
    }
}

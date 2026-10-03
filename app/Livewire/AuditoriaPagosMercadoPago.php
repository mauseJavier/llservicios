<?php

namespace App\Livewire;

use App\Models\Empresa;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Throwable;

class AuditoriaPagosMercadoPago extends Component
{
    public $empresas = [];

    public string $empresaId = 'all';

    public $empresa = null;

    public string $modo = 'huerfanos';

    public bool $loading = false;

    public string $output = '';

    public string $successMessage = '';

    public string $errorMessage = '';

    protected $rules = [
        'modo' => 'required|in:cruzados,huerfanos',
    ];

    public function mount(): void
    {
        abort_unless(Auth::user()?->role?->nombre === 'Super', 403);

        $this->empresas = Empresa::query()
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'cuit']);
    }

    public function updatedEmpresaId(): void
    {
        $this->empresa = ($this->empresaId !== '' && $this->empresaId !== 'all')
            ? Empresa::find($this->empresaId)
            : null;

        $this->clearMessages();
    }

    public function auditar(): void
    {
        $this->ejecutar(false);
    }

    public function aplicar(): void
    {
        $this->ejecutar(true);
    }

    protected function ejecutar(bool $apply): void
    {
        if (Auth::user()?->role?->nombre !== 'Super') {
            abort(403);
        }

        $this->validate();

        $esTodas = $this->empresaId === '' || $this->empresaId === 'all';

        $this->clearMessages();
        $this->loading = true;
        $this->output = '';

        set_time_limit(0);

        try {
            $parametros = [
                '--empresa' => $esTodas ? 'all' : (string) $this->empresaId,
            ];

            if ($this->modo === 'huerfanos') {
                $parametros['--huerfanos'] = true;
            }

            if ($apply) {
                $parametros['--apply'] = true;
                $parametros['--force'] = true;
            }

            $exitCode = Artisan::call('mp:auditar-pagos-cruzados', $parametros);
            $this->output = trim(Artisan::output());

            $accion = $apply ? 'La remediación' : 'La auditoría';

            if ($exitCode === 0) {
                $this->successMessage = "{$accion} finalizó correctamente.";
            } else {
                $this->errorMessage = "{$accion} finalizó con errores (código {$exitCode}).";
            }
        } catch (Throwable $e) {
            $this->errorMessage = 'Error al ejecutar el comando: '.$e->getMessage();

            Log::error('Error en componente de auditoría MercadoPago', [
                'modo' => $this->modo,
                'apply' => $apply,
                'empresa_id' => $this->empresaId,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->loading = false;
        }
    }

    protected function clearMessages(): void
    {
        $this->successMessage = '';
        $this->errorMessage = '';
    }

    public function render()
    {
        return view('livewire.auditoria-pagos-mercado-pago')
            ->extends('principal.principal')
            ->section('body');
    }
}

<?php

namespace App\Livewire;

use App\Models\Empresa;
use App\Services\MercadoPago\MercadoPagoDuplicatePaymentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Throwable;

class ReembolsosMercadoPago extends Component
{
    public array $empresas = [];

    public string $empresaId = '';

    public string $empresaNombre = '';

    public bool $empresaTieneToken = false;

    public string $desde = '';

    public string $hasta = '';

    public array $candidatos = [];

    public bool $loading = false;

    public string $successMessage = '';

    public string $errorMessage = '';

    public ?array $selected = null;

    public bool $showConfirm = false;

    public string $motivo = 'Pago duplicado';

    public string $manualPaymentId = '';

    public array $resultados = [];

    protected $rules = [
        'empresaId' => 'required',
        'desde' => 'required|date',
        'hasta' => 'required|date|after_or_equal:desde',
        'motivo' => 'required|string|max:255',
        'manualPaymentId' => 'required|string|max:100',
    ];

    public function mount(): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $rol = $user->role?->nombre;
        abort_unless(in_array($rol, ['Super', 'Admin'], true), 403);

        if ($rol === 'Super') {
            $this->empresas = Empresa::query()
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'cuit'])
                ->toArray();

            $this->empresaId = (string) ($user->empresa_id ?: ($this->empresas[0]['id'] ?? ''));
        } else {
            $this->empresaId = (string) ($user->empresa_id ?? '');
        }

        abort_if($this->empresaId === '', 403, 'Usuario sin empresa asignada.');

        $empresa = Empresa::find($this->empresaId);
        abort_unless($empresa, 404);

        $this->empresaNombre = (string) $empresa->nombre;
        $this->empresaTieneToken = ! empty($empresa->MP_ACCESS_TOKEN);
        $this->desde = now()->subDays(30)->toDateString();
        $this->hasta = now()->toDateString();
    }

    public function updatedEmpresaId(): void
    {
        $user = Auth::user();

        if ($user?->role?->nombre !== 'Super') {
            $this->empresaId = (string) ($user?->empresa_id ?? '');
        }

        $empresa = Empresa::find($this->empresaId);

        $this->empresaNombre = (string) ($empresa?->nombre ?? '');
        $this->empresaTieneToken = ! empty($empresa?->MP_ACCESS_TOKEN);
        $this->candidatos = [];
        $this->resultados = [];
        $this->selected = null;
        $this->showConfirm = false;
        $this->clearMessages();
    }

    public function buscarDuplicados(): void
    {
        $this->validate([
            'empresaId' => 'required',
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ]);

        $this->clearMessages();
        $this->candidatos = [];
        $this->resultados = [];
        $this->selected = null;
        $this->showConfirm = false;
        $this->loading = true;

        try {
            $empresa = $this->empresaActual();

            $service = app(MercadoPagoDuplicatePaymentService::class);

            $this->candidatos = $service->buscarDuplicados(
                $empresa,
                Carbon::parse($this->desde),
                Carbon::parse($this->hasta)
            );

            $this->successMessage = empty($this->candidatos)
                ? 'No se detectaron pagos duplicados en el período.'
                : 'Se detectaron '.count($this->candidatos).' referencia(s) con pagos duplicados.';
        } catch (Throwable $e) {
            $this->errorMessage = $e->getMessage();

            Log::error('Error buscando pagos duplicados MercadoPago', [
                'empresa_id' => $this->empresaId,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->loading = false;
        }
    }

    public function confirmarReembolso(string $paymentId): void
    {
        $this->clearMessages();
        $this->selected = null;

        foreach ($this->candidatos as $candidato) {
            foreach ($candidato['duplicates'] as $duplicado) {
                if ((string) $duplicado['id'] === $paymentId) {
                    $this->selected = [
                        'referencia' => $candidato['referencia'],
                        'cliente' => $candidato['cliente'],
                        'servicio' => $candidato['servicio'],
                        'primary' => $candidato['primary'],
                        'duplicate' => $duplicado,
                        'elegible' => $candidato['elegible'],
                        'motivos' => $candidato['motivos'],
                    ];
                    $this->showConfirm = true;

                    return;
                }
            }
        }

        $this->errorMessage = 'No se encontró el pago seleccionado.';
    }

    public function cancelarConfirmacion(): void
    {
        $this->showConfirm = false;
        $this->selected = null;
    }

    public function reembolsar(): void
    {
        if (! $this->selected) {
            $this->errorMessage = 'Seleccioná un pago para reembolsar.';

            return;
        }

        $this->validateOnly('motivo');
        $this->clearMessages();
        $this->loading = true;
        $paymentId = (string) $this->selected['duplicate']['id'];

        try {
            $empresa = $this->empresaActual();

            $service = app(MercadoPagoDuplicatePaymentService::class);

            $resultado = $service->reembolsar(
                $empresa,
                $paymentId,
                $this->motivo ?: 'Pago duplicado',
                Auth::user()
            );

            $resultado['payment_id'] = $resultado['payment_id'] ?? $paymentId;

            $this->resultados[] = $resultado;

            if ($resultado['success']) {
                $this->successMessage = 'Reembolso procesado. ID de reembolso: '.($resultado['refund_id'] ?? 's/d');
                $this->showConfirm = false;
                $this->selected = null;
                $this->quitarDuplicadoDeCandidatos($paymentId);
            } else {
                $this->errorMessage = $resultado['mensaje'] ?? 'No se pudo reembolsar.';
            }
        } catch (Throwable $e) {
            $this->errorMessage = $e->getMessage();

            Log::error('Error reembolsando pago MercadoPago', [
                'empresa_id' => $this->empresaId,
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->loading = false;
        }
    }

    public function reembolsarManual(): void
    {
        $this->validateOnly('manualPaymentId');
        $this->clearMessages();
        $this->loading = true;
        $paymentId = trim($this->manualPaymentId);

        try {
            $empresa = $this->empresaActual();

            $service = app(MercadoPagoDuplicatePaymentService::class);

            $resultado = $service->reembolsar(
                $empresa,
                $paymentId,
                'Reembolso manual',
                Auth::user()
            );

            $resultado['payment_id'] = $resultado['payment_id'] ?? $paymentId;

            $this->resultados[] = $resultado;

            if ($resultado['success']) {
                $this->successMessage = 'Reembolso manual procesado. ID de reembolso: '.($resultado['refund_id'] ?? 's/d');
                $this->manualPaymentId = '';
                $this->quitarDuplicadoDeCandidatos($paymentId);
            } else {
                $this->errorMessage = $resultado['mensaje'] ?? 'No se pudo reembolsar.';
            }
        } catch (Throwable $e) {
            $this->errorMessage = $e->getMessage();

            Log::error('Error en reembolso manual MercadoPago', [
                'empresa_id' => $this->empresaId,
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->loading = false;
        }
    }

    /**
     * La empresa se resuelve siempre desde el usuario para los roles de empresa.
     * Super puede elegirla desde el selector.
     */
    private function empresaActual(): Empresa
    {
        $user = Auth::user();
        $rol = $user?->role?->nombre;

        $empresaId = $rol === 'Super'
            ? (int) $this->empresaId
            : (int) ($user?->empresa_id ?? 0);

        $empresa = Empresa::find($empresaId);
        abort_unless($empresa, 404);

        $this->empresaId = (string) $empresa->id;
        $this->empresaNombre = (string) $empresa->nombre;
        $this->empresaTieneToken = ! empty($empresa->MP_ACCESS_TOKEN);

        return $empresa;
    }

    private function quitarDuplicadoDeCandidatos(string $paymentId): void
    {
        $candidatos = [];

        foreach ($this->candidatos as $candidato) {
            $duplicados = array_values(array_filter(
                $candidato['duplicates'],
                fn (array $duplicado) => (string) $duplicado['id'] !== $paymentId
            ));

            if (empty($duplicados)) {
                continue;
            }

            $candidato['duplicates'] = $duplicados;
            $candidato['monto_duplicado'] = array_sum(array_map(
                fn (array $duplicado) => (float) ($duplicado['monto'] ?? 0),
                $duplicados
            ));

            $candidatos[] = $candidato;
        }

        $this->candidatos = $candidatos;
    }

    protected function clearMessages(): void
    {
        $this->successMessage = '';
        $this->errorMessage = '';
    }

    public function render()
    {
        return view('livewire.reembolsos-mercado-pago')
            ->extends('principal.principal')
            ->section('body');
    }
}

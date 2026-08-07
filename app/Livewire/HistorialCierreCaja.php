<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use App\Models\CierreCaja as CierreCajaModel;
use App\Models\User;
use App\Models\Empresa;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class HistorialCierreCaja extends Component
{
    use WithPagination;

    public $fechaDesde = '';
    public $fechaHasta = '';
    public $tipoMovimiento = '';
    public $usuarioId = '';

    public function mount()
    {
        $this->fechaHasta = Carbon::today()->format('Y-m-d');
        $this->fechaDesde = Carbon::today()->subDays(30)->format('Y-m-d');
    }

    public function updatedFechaDesde()
    {
        $this->resetPage();
    }

    public function updatedFechaHasta()
    {
        $this->resetPage();
    }

    public function updatedTipoMovimiento()
    {
        $this->resetPage();
    }

    public function updatedUsuarioId()
    {
        $this->resetPage();
    }

    public function limpiarFiltros()
    {
        $this->reset([
            'fechaDesde',
            'fechaHasta',
            'tipoMovimiento',
            'usuarioId',
        ]);
        $this->resetPage();
    }

    public function render()
    {
        $usuario = Auth::user();

        $registros = CierreCajaModel::where('empresa_id', $usuario->empresa_id)
            ->when($this->fechaDesde, fn ($query) => $query->whereDate('created_at', '>=', $this->fechaDesde))
            ->when($this->fechaHasta, fn ($query) => $query->whereDate('created_at', '<=', $this->fechaHasta))
            ->when($this->tipoMovimiento, fn ($query) => $query->where('movimiento', $this->tipoMovimiento))
            ->when($this->usuarioId, fn ($query) => $query->where('usuario_id', $this->usuarioId))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $usuarios = User::where('empresa_id', $usuario->empresa_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.historial-cierre-caja', [
            'registros' => $registros,
            'usuarios' => $usuarios,
        ])
        ->extends('principal.principal')
        ->section('body');
    }

    public function generarPdf($formato, $fecha, $usuarioId)
    {
        $usuarioLogueado = Auth::user();
        $usuario = User::findOrFail($usuarioId);

        if ($usuario->empresa_id !== $usuarioLogueado->empresa_id) {
            abort(403);
        }

        $empresa = Empresa::find($usuario->empresa_id);
        $fechaCarbon = Carbon::parse($fecha);

        $datosResumen = CierreCajaModel::resumenUsuario($usuario->empresa_id, $usuario->id, $fechaCarbon);

        $datos = [
            'usuario' => $usuario,
            'empresa' => $empresa,
            'fecha' => $fechaCarbon,
            'calculoCaja' => $datosResumen['calculoCaja'],
            'resumenDia' => $datosResumen['resumenDia'],
            'movimientosInicio' => $datosResumen['movimientosInicio'],
            'movimientosCierre' => $datosResumen['movimientosCierre'],
            'pagosDia' => $datosResumen['pagosDia'],
            'gastosDia' => $datosResumen['gastosDia'],
            'formato' => $formato
        ];

        $nombreArchivo = 'cierre-caja-' . $fechaCarbon->format('Y-m-d') . '-' . $usuario->name . '.pdf';

        if ($formato === '80mm') {
            $pdf = Pdf::loadView('pdf.cierre-caja-80mm', $datos)
                     ->setPaper([0, 0, 226, 842], 'portrait');
        } else {
            $pdf = Pdf::loadView('pdf.cierre-caja-a4', $datos)
                     ->setPaper('a4', 'portrait');
        }

        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, $nombreArchivo);
    }
}

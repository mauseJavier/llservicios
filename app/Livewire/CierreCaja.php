<?php

namespace App\Livewire;

use App\Models\CierreCaja as CierreCajaModel;
use App\Models\Empresa;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CierreCaja extends Component
{
    public $importe = '';

    public $comentario = '';

    public $mostrarFormulario = false;

    public $tipoMovimiento = '';

    public $ultimoCierre = null;

    public $ultimoInicio = null;

    public $cajaActiva = false;

    public $calculoCaja = [];

    public $resumenDia = [];

    public $resumenEmpresa = [];

    public $mostrarResumenEmpresa = false;

    public $usuarioId = '';

    protected $rules = [
        'importe' => 'required|numeric|min:0',
        'comentario' => 'nullable|string|max:500',
    ];

    protected $messages = [
        'importe.required' => 'El importe es obligatorio',
        'importe.numeric' => 'El importe debe ser un número válido',
        'importe.min' => 'El importe no puede ser negativo',
        'comentario.max' => 'El comentario no puede exceder los 500 caracteres',
    ];

    public function mount()
    {
        $this->usuarioId = Auth::user()->id;
        $this->cargarEstadoCaja();
        $this->cargarResumenEmpresa();
    }

    public function updatedUsuarioId()
    {
        $this->calcularMovimientosCaja();
    }

    public function cargarEstadoCaja()
    {
        $usuario = Auth::user();
        $empresaId = $usuario->empresa_id;

        // Obtener último inicio y cierre
        $this->ultimoInicio = CierreCajaModel::ultimoInicio($empresaId);
        $this->ultimoCierre = CierreCajaModel::ultimoCierre($empresaId);

        // Determinar si la caja está activa
        if (! $this->ultimoInicio) {
            $this->cajaActiva = false;
        } elseif (! $this->ultimoCierre) {
            $this->cajaActiva = true;
        } else {
            $this->cajaActiva = $this->ultimoInicio->created_at > $this->ultimoCierre->created_at;
        }

        // Recalcular los movimientos después de cargar el estado
        $this->calcularMovimientosCaja();
    }

    public function iniciarCaja()
    {
        $this->tipoMovimiento = 'inicio';
        $this->mostrarFormulario = true;
        $this->importe = '';
        $this->comentario = '';
    }

    public function cerrarCaja()
    {
        $this->tipoMovimiento = 'cierre';
        $this->mostrarFormulario = true;
        $this->importe = '';
        $this->comentario = '';
    }

    public function guardar()
    {
        $this->validate();

        $usuario = Auth::user();

        try {
            CierreCajaModel::create([
                'usuario_id' => $usuario->id,
                'usuario_nombre' => $usuario->name,
                'importe' => $this->importe,
                'empresa_id' => $usuario->empresa_id,
                'movimiento' => $this->tipoMovimiento,
                'comentario' => $this->comentario,
            ]);

            $mensaje = $this->tipoMovimiento === 'inicio' ? 'Caja iniciada correctamente' : 'Caja cerrada correctamente';

            if ($this->tipoMovimiento === 'inicio') {
                $this->dispatch('swal-centro', titulo: $mensaje, texto: 'Movimiento registrado correctamente');
            } else {
                $this->dispatch('swal', tipo: 'success', titulo: $mensaje, texto: 'Movimiento registrado correctamente');
            }

            $this->resetForm();
            $this->cargarEstadoCaja();

        } catch (\Exception $e) {
            Log::error('Error al procesar el movimiento de caja: '.$e->getMessage());
            $this->dispatch('swal', tipo: 'error', titulo: 'Error', texto: 'Error al procesar el movimiento');
        }
    }

    public function cancelar()
    {
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->mostrarFormulario = false;
        $this->importe = '';
        $this->comentario = '';
        $this->tipoMovimiento = '';
        $this->resetValidation();
    }

    public function calcularMovimientosCaja()
    {
        $usuario = $this->usuarioSeleccionado();

        $resumen = CierreCajaModel::resumenUsuario($usuario->empresa_id, $usuario->id, Carbon::today());

        $this->calculoCaja = $resumen['calculoCaja'];
        $this->resumenDia = array_merge($resumen['resumenDia'], [
            'usuario' => $usuario->name,
        ]);
    }

    protected function usuarioSeleccionado(): User
    {
        $usuarioLogueado = Auth::user();

        if (! $this->usuarioId) {
            return $usuarioLogueado;
        }

        $usuario = User::find($this->usuarioId);

        if (! $usuario || $usuario->empresa_id !== $usuarioLogueado->empresa_id) {
            abort(403);
        }

        return $usuario;
    }

    public function generarPdfA4()
    {
        return $this->generarPdf('a4');
    }

    public function generarPdf80mm()
    {
        return $this->generarPdf('80mm');
    }

    private function generarPdf($formato)
    {
        $usuario = $this->usuarioSeleccionado();
        $empresa = Empresa::find($usuario->empresa_id);

        // Asegurar que tenemos los datos actualizados
        $this->calcularMovimientosCaja();

        // Obtener el resumen y los movimientos del día
        $hoy = Carbon::today();
        $datosResumen = CierreCajaModel::resumenUsuario($usuario->empresa_id, $usuario->id, $hoy);

        $datos = [
            'usuario' => $usuario,
            'empresa' => $empresa,
            'fecha' => $hoy,
            'calculoCaja' => $datosResumen['calculoCaja'],
            'resumenDia' => $datosResumen['resumenDia'],
            'movimientosInicio' => $datosResumen['movimientosInicio'],
            'movimientosCierre' => $datosResumen['movimientosCierre'],
            'pagosDia' => $datosResumen['pagosDia'],
            'gastosDia' => $datosResumen['gastosDia'],
            'formato' => $formato,
        ];

        $nombreArchivo = 'cierre-caja-'.$hoy->format('Y-m-d').'-'.$usuario->name.'.pdf';

        if ($formato === '80mm') {
            $pdf = Pdf::loadView('pdf.cierre-caja-80mm', $datos)
                ->setPaper([0, 0, 226, 842], 'portrait'); // 80mm width
        } else {
            $pdf = Pdf::loadView('pdf.cierre-caja-a4', $datos)
                ->setPaper('a4', 'portrait');
        }

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $nombreArchivo);
    }

    public function toggleResumenEmpresa()
    {
        $this->mostrarResumenEmpresa = ! $this->mostrarResumenEmpresa;
        if ($this->mostrarResumenEmpresa) {
            $this->cargarResumenEmpresa();
        }
    }

    public function cargarResumenEmpresa()
    {
        $usuario = Auth::user();

        $this->resumenEmpresa = CierreCajaModel::resumenEmpresa($usuario->empresa_id, Carbon::today());
    }

    public function getHistorialReciente()
    {
        $usuario = $this->usuarioSeleccionado();

        return CierreCajaModel::where('empresa_id', $usuario->empresa_id)
            ->where('usuario_id', $usuario->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
    }

    public function render()
    {
        $usuario = Auth::user();

        $usuarios = User::where('empresa_id', $usuario->empresa_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.cierre-caja', [
            'historialReciente' => $this->getHistorialReciente(),
            'calculoCaja' => $this->calculoCaja,
            'resumenDia' => $this->resumenDia,
            'usuarios' => $usuarios,
        ])
            ->extends('principal.principal')
            ->section('body');
    }
}

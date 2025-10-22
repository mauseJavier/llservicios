<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\CierreCaja as CierreCajaModel;
use App\Models\Empresa;
use App\Models\Pagos;
use App\Models\Expense;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

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

    protected $rules = [
        'importe' => 'required|numeric|min:0',
        'comentario' => 'nullable|string|max:500'
    ];

    protected $messages = [
        'importe.required' => 'El importe es obligatorio',
        'importe.numeric' => 'El importe debe ser un número válido',
        'importe.min' => 'El importe no puede ser negativo',
        'comentario.max' => 'El comentario no puede exceder los 500 caracteres'
    ];

    public function mount()
    {
        $this->cargarEstadoCaja();
    }

    public function cargarEstadoCaja()
    {
        $usuario = Auth::user();
        $empresaId = $usuario->empresa_id;

        // Obtener último inicio y cierre
        $this->ultimoInicio = CierreCajaModel::ultimoInicio($empresaId);
        $this->ultimoCierre = CierreCajaModel::ultimoCierre($empresaId);

        // Determinar si la caja está activa
        if (!$this->ultimoInicio) {
            $this->cajaActiva = false;
        } elseif (!$this->ultimoCierre) {
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
                'comentario' => $this->comentario
            ]);

            $mensaje = $this->tipoMovimiento === 'inicio' ? 'Caja iniciada correctamente' : 'Caja cerrada correctamente';
            session()->flash('message', $mensaje);
            
            $this->resetForm();
            $this->cargarEstadoCaja();

        } catch (\Exception $e) {
            session()->flash('error', 'Error al procesar el movimiento: ' . $e->getMessage());
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
        $usuario = Auth::user();
        $hoy = Carbon::today();
        
        // Calcular suma de todos los inicios de caja del día
        $totalInicioCaja = CierreCajaModel::where('empresa_id', $usuario->empresa_id)
                                         ->where('usuario_id', $usuario->id)
                                         ->where('movimiento', 'inicio')
                                         ->whereDate('created_at', $hoy)
                                         ->sum('importe');

        // Calcular suma de todos los cierres de caja del día
        $totalCierreCaja = CierreCajaModel::where('empresa_id', $usuario->empresa_id)
                                         ->where('usuario_id', $usuario->id)
                                         ->where('movimiento', 'cierre')
                                         ->whereDate('created_at', $hoy)
                                         ->sum('importe');

        // Obtener contadores de registros para mostrar en resumen
        $cantidadInicios = CierreCajaModel::where('empresa_id', $usuario->empresa_id)
                                         ->where('usuario_id', $usuario->id)
                                         ->where('movimiento', 'inicio')
                                         ->whereDate('created_at', $hoy)
                                         ->count();

        $cantidadCierres = CierreCajaModel::where('empresa_id', $usuario->empresa_id)
                                         ->where('usuario_id', $usuario->id)
                                         ->where('movimiento', 'cierre')
                                         ->whereDate('created_at', $hoy)
                                         ->count();

        // Calcular total de pagos del usuario logueado en el día
        $totalPagos = Pagos::where('id_usuario', $usuario->id)
                            ->where('forma_pago', '=', 1)
                           ->whereDate('created_at', $hoy)
                           ->sum('importe');

        // Calcular total de gastos del usuario logueado en el día
        $totalGastos = Expense::where('usuario_id', $usuario->id)
            ->where('estado', 'pago')
            ->where('forma_pago_id', '=', 1)
                              ->whereDate('created_at', $hoy)
                              ->sum('importe');

        // Cálculo según la fórmula: inicio de caja negativo, pagos negativo, cierre de caja y gastos positivo
        $this->calculoCaja = [
            'inicio_caja' => $totalInicioCaja,
            'total_pagos' => $totalPagos,
            'total_gastos' => $totalGastos,
            'cierre_caja' => $totalCierreCaja,
            'calculo_final' => (-$totalInicioCaja) + (-$totalPagos) + $totalCierreCaja + $totalGastos
        ];

        // Resumen detallado del día
        $this->resumenDia = [
            'fecha' => $hoy->format('d/m/Y'),
            'usuario' => $usuario->name,
            'inicio_registrado' => $cantidadInicios > 0,
            'cierre_registrado' => $cantidadCierres > 0,
            'cantidad_inicios' => $cantidadInicios,
            'cantidad_cierres' => $cantidadCierres,
            'total_movimientos_pagos' => Pagos::where('id_usuario', $usuario->id)->whereDate('created_at', $hoy)->count(),
            'total_movimientos_gastos' => Expense::where('usuario_id', $usuario->id)->whereDate('created_at', $hoy)->count()
        ];
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
        $usuario = Auth::user();
        $empresa = Empresa::find($usuario->empresa_id);
        
        // Asegurar que tenemos los datos actualizados
        $this->calcularMovimientosCaja();
        
        // Obtener detalles de movimientos para el reporte
        $hoy = Carbon::today();
        
        $movimientosInicio = CierreCajaModel::where('empresa_id', $usuario->empresa_id)
                                           ->where('usuario_id', $usuario->id)
                                           ->where('movimiento', 'inicio')
                                           ->whereDate('created_at', $hoy)
                                           ->orderBy('created_at')
                                           ->get();
        
        $movimientosCierre = CierreCajaModel::where('empresa_id', $usuario->empresa_id)
                                           ->where('usuario_id', $usuario->id)
                                           ->where('movimiento', 'cierre')
                                           ->whereDate('created_at', $hoy)
                                           ->orderBy('created_at')
                                           ->get();
        
        $pagosDia = Pagos::where('id_usuario', $usuario->id)
                         ->where('forma_pago', '=', 1)
                         ->whereDate('created_at', $hoy)
                         ->orderBy('created_at')
                         ->get();
        
        $gastosDia = Expense::where('usuario_id', $usuario->id)
                           ->whereDate('created_at', $hoy)
                           ->orderBy('created_at')
                           ->get();

        $datos = [
            'usuario' => $usuario,
            'empresa' => $empresa,
            'fecha' => $hoy,
            'calculoCaja' => $this->calculoCaja,
            'resumenDia' => $this->resumenDia,
            'movimientosInicio' => $movimientosInicio,
            'movimientosCierre' => $movimientosCierre,
            'pagosDia' => $pagosDia,
            'gastosDia' => $gastosDia,
            'formato' => $formato
        ];

        $nombreArchivo = 'cierre-caja-' . $hoy->format('Y-m-d') . '-' . $usuario->name . '.pdf';
        
        if ($formato === '80mm') {
            $pdf = Pdf::loadView('pdf.cierre-caja-80mm', $datos)
                     ->setPaper([0, 0, 226, 842], 'portrait'); // 80mm width
        } else {
            $pdf = Pdf::loadView('pdf.cierre-caja-a4', $datos)
                     ->setPaper('a4', 'portrait');
        }

        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, $nombreArchivo);
    }

    public function getHistorialReciente()
    {
        $usuario = Auth::user();
        return CierreCajaModel::where('empresa_id', $usuario->empresa_id)
                            ->orderBy('created_at', 'desc')
                            ->take(10)
                            ->get();
    }

    public function render()
    {
        return view('livewire.cierre-caja', [
            'historialReciente' => $this->getHistorialReciente(),
            'calculoCaja' => $this->calculoCaja,
            'resumenDia' => $this->resumenDia
        ])
        ->extends('principal.principal')
        ->section('body'); 
    }
}

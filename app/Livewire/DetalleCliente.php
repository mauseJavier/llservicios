<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Cliente;
use App\Models\Servicio;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DetalleCliente extends Component
{
    public $clienteId;
    public $cliente;
    public $serviciosVinculados = [];
    public $serviciosImpagos = [];
    public $serviciosPagos = [];
    public $totalImpago = 0;
    public $totalPagado = 0;
    
    // Propiedades para vincular servicios
    public $mostrarModalVincular = false;
    public $serviciosDisponibles = [];
    public $servicioSeleccionado = '';
    public $cantidadVincular = 1;
    public $vencimientoVincular = '';
    public $buscarServicio = '';

    public function mount($clienteId)
    {
        $this->clienteId = $clienteId;
        $this->cargarDatosCliente();
        
        // Establecer fecha de vencimiento por defecto (1 año desde hoy)
        $this->vencimientoVincular = now()->addYear()->format('Y-m-d\TH:i');
    }

    public function cargarDatosCliente()
    {
        $usuario = Auth::user();

        // Verificar que el cliente pertenece a la empresa del usuario
        $this->cliente = DB::selectOne(
            'SELECT b.* FROM cliente_empresa a, clientes b 
             WHERE a.cliente_id = b.id 
             AND a.empresa_id = ? 
             AND b.id = ?',
            [$usuario->empresa_id, $this->clienteId]
        );

        if (!$this->cliente) {
            return redirect()->route('Cliente.index')
                ->with('status', 'Cliente no encontrado o no pertenece a su empresa');
        }

        // Cargar servicios vinculados desde cliente_servicio
        $this->serviciosVinculados = DB::select(
            'SELECT 
                cs.id as vinculo_id,
                cs.cantidad,
                cs.vencimiento,
                cs.created_at as fecha_vinculacion,
                s.id as servicio_id,
                s.nombre as servicio_nombre,
                s.descripcion as servicio_descripcion,
                s.precio as servicio_precio,
                s.tiempo as servicio_tiempo,
                (cs.cantidad * s.precio) as subtotal
             FROM cliente_servicio cs
             INNER JOIN servicios s ON cs.servicio_id = s.id
             WHERE cs.cliente_id = ?
             AND s.empresa_id = ?
             ORDER BY cs.vencimiento DESC',
            [$this->clienteId, $usuario->empresa_id]
        );

        // Cargar servicios impagos
        $this->serviciosImpagos = DB::select(
            'SELECT 
                sp.id,
                sp.cantidad,
                sp.precio,
                sp.created_at as fecha_creacion,
                sp.periodo_servicio,
                s.nombre as servicio_nombre,
                (sp.cantidad * sp.precio) as total
             FROM servicio_pagar sp
             INNER JOIN servicios s ON sp.servicio_id = s.id
             WHERE sp.cliente_id = ?
             AND sp.estado = ?
             AND s.empresa_id = ?
             ORDER BY sp.created_at DESC',
            [$this->clienteId, 'impago', $usuario->empresa_id]
        );

        // Cargar últimos servicios pagos (últimos 10)
        $this->serviciosPagos = DB::select(
            'SELECT 
                sp.id,
                sp.cantidad,
                sp.precio,
                sp.created_at as fecha_creacion,
                sp.updated_at as fecha_pago,
                sp.periodo_servicio,
                s.nombre as servicio_nombre,
                (sp.cantidad * sp.precio) as total
             FROM servicio_pagar sp
             INNER JOIN servicios s ON sp.servicio_id = s.id
             WHERE sp.cliente_id = ?
             AND sp.estado = ?
             AND s.empresa_id = ?
             ORDER BY sp.updated_at DESC
             LIMIT 10',
            [$this->clienteId, 'pago', $usuario->empresa_id]
        );

        // Calcular totales
        $this->totalImpago = array_sum(array_column($this->serviciosImpagos, 'total'));
        $this->totalPagado = array_sum(array_column($this->serviciosPagos, 'total'));
    }

    public function abrirModalVincular()
    {
        $this->mostrarModalVincular = true;
        $this->cargarServiciosDisponibles();
    }

    public function cerrarModalVincular()
    {
        $this->mostrarModalVincular = false;
        $this->servicioSeleccionado = '';
        $this->cantidadVincular = 1;
        $this->buscarServicio = '';
        $this->vencimientoVincular = now()->addYear()->format('Y-m-d\TH:i');
    }

    public function cargarServiciosDisponibles()
    {
        $usuario = Auth::user();
        
        // Obtener IDs de servicios ya vinculados
        $serviciosVinculadosIds = array_column($this->serviciosVinculados, 'servicio_id');
        
        // Construir query base
        $query = 'SELECT s.id, s.nombre, s.descripcion, s.precio, s.tiempo 
                  FROM servicios s 
                  WHERE s.empresa_id = ?';
        
        $params = [$usuario->empresa_id];
        
        // Excluir servicios ya vinculados
        if (!empty($serviciosVinculadosIds)) {
            $placeholders = implode(',', array_fill(0, count($serviciosVinculadosIds), '?'));
            $query .= " AND s.id NOT IN ($placeholders)";
            $params = array_merge($params, $serviciosVinculadosIds);
        }
        
        // Aplicar búsqueda si existe
        if ($this->buscarServicio) {
            $query .= " AND (s.nombre LIKE ? OR s.descripcion LIKE ?)";
            $searchTerm = '%' . $this->buscarServicio . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $query .= ' ORDER BY s.nombre ASC';
        
        $this->serviciosDisponibles = DB::select($query, $params);
    }

    public function updatedBuscarServicio()
    {
        $this->cargarServiciosDisponibles();
    }

    public function vincularServicio()
    {
        $this->validate([
            'servicioSeleccionado' => 'required|numeric',
            'cantidadVincular' => 'required|numeric|min:0.5',
            'vencimientoVincular' => 'required|date',
        ], [
            'servicioSeleccionado.required' => 'Debe seleccionar un servicio',
            'cantidadVincular.required' => 'La cantidad es requerida',
            'cantidadVincular.min' => 'La cantidad mínima es 0.5',
            'vencimientoVincular.required' => 'La fecha de vencimiento es requerida',
        ]);

        $usuario = Auth::user();
        
        // Verificar que el servicio existe y pertenece a la empresa
        $servicio = DB::selectOne(
            'SELECT * FROM servicios WHERE id = ? AND empresa_id = ?',
            [$this->servicioSeleccionado, $usuario->empresa_id]
        );
        
        if (!$servicio) {
            session()->flash('error', 'Servicio no válido');
            return;
        }
        
        // Verificar si ya existe la vinculación
        $existeVinculo = DB::selectOne(
            'SELECT * FROM cliente_servicio WHERE cliente_id = ? AND servicio_id = ?',
            [$this->clienteId, $this->servicioSeleccionado]
        );
        
        if ($existeVinculo) {
            session()->flash('error', 'El cliente ya está vinculado a este servicio');
            return;
        }
        
        // Crear la vinculación
        $fecha = now()->format('Y-m-d H:i:s');
        $vencimiento = \Carbon\Carbon::parse($this->vencimientoVincular)->format('Y-m-d H:i:s');
        
        $id = DB::table('cliente_servicio')->insertGetId([
            'cliente_id' => $this->clienteId,
            'servicio_id' => $this->servicioSeleccionado,
            'cantidad' => $this->cantidadVincular,
            'vencimiento' => $vencimiento,
            'created_at' => $fecha,
            'updated_at' => $fecha,
        ]);
        
        if ($id) {
            session()->flash('success', 'Servicio vinculado exitosamente');
            $this->cerrarModalVincular();
            $this->cargarDatosCliente();
        } else {
            session()->flash('error', 'Error al vincular el servicio');
        }
    }

    public function desvincularServicio($vinculoId)
    {
        $usuario = Auth::user();
        
        // Verificar que el vínculo pertenece al cliente y a la empresa
        $vinculo = DB::selectOne(
            'SELECT cs.* FROM cliente_servicio cs
             INNER JOIN servicios s ON cs.servicio_id = s.id
             WHERE cs.id = ? AND cs.cliente_id = ? AND s.empresa_id = ?',
            [$vinculoId, $this->clienteId, $usuario->empresa_id]
        );
        
        if (!$vinculo) {
            session()->flash('error', 'Vínculo no encontrado');
            return;
        }
        
        $eliminado = DB::delete('DELETE FROM cliente_servicio WHERE id = ?', [$vinculoId]);
        
        if ($eliminado > 0) {
            session()->flash('success', 'Servicio desvinculado exitosamente');
            $this->cargarDatosCliente();
        } else {
            session()->flash('error', 'Error al desvincular el servicio');
        }
    }

    public function render()
    {
        return view('livewire.detalle-cliente')
            ->extends('principal.principal')
            ->section('body');
    }
}

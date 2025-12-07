<?php

namespace App\Livewire\VerCliente;

use Livewire\Component;

use Livewire\WithPagination;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Servicio;

use App\Models\ServicioPagar;
use App\Models\Pagos;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VerCliente extends Component
{

    use WithPagination;

    public $idCliente;
    public $clientes;
    public $buscarCliente;
    public $clienteAEliminar = null;
    public $mostrarModalConfirmacion = false;


    public function mount($idCliente = null)
    {
        if ($idCliente) {
            // Manejar el caso cuando no se proporciona un ID de cliente
            // Por ejemplo, redirigir o mostrar un mensaje de error
        }else {
            // Cargar los datos del cliente según el ID proporcionado
            // Por ejemplo:
            $empresa = Empresa::find(Auth::user()->empresa_id);
            $clientes = $empresa->clientes;
        }

   /*      foreach ($clientes as $cliente) {

            dd($cliente);
        } */

        $this->clientes = $clientes;


    }

    public function updatingBuscarCliente()
    {
        $this->clientes = Empresa::find(Auth::user()->empresa_id)
            ->clientes()
            ->where(function($query) {
                $query->where('nombre', 'like', '%' . $this->buscarCliente . '%')
                    ->orWhere('correo', 'like', '%' . $this->buscarCliente . '%')
                    ->orWhere('telefono', 'like', '%' . $this->buscarCliente . '%')
                    ->orWhere('dni', 'like', '%' . $this->buscarCliente . '%')
                    ->orWhere('domicilio', 'like', '%' . $this->buscarCliente . '%');
            })
            ->get();
    }

    /**
     * Abre el modal de confirmación para eliminar un cliente
     */
    public function confirmarEliminarCliente($clienteId)
    {
        $this->clienteAEliminar = Cliente::find($clienteId);
        $this->mostrarModalConfirmacion = true;
    }

    /**
     * Cancela la eliminación y cierra el modal
     */
    public function cancelarEliminacion()
    {
        $this->clienteAEliminar = null;
        $this->mostrarModalConfirmacion = false;
    }

    /**
     * Elimina el cliente de la empresa actual y todos sus servicios asociados
     * Solo elimina servicios, pagos y relaciones vinculadas a esta empresa
     * El cliente solo se elimina completamente si no pertenece a ninguna otra empresa
     */
    public function eliminarCliente()
    {
        if (!$this->clienteAEliminar) {
            session()->flash('error', 'No se pudo encontrar el cliente a eliminar.');
            return;
        }

        try {
            DB::beginTransaction();

            $clienteId = $this->clienteAEliminar->id;
            $clienteNombre = $this->clienteAEliminar->nombre;
            $empresaId = Auth::user()->empresa_id;
            $empresa = Empresa::find($empresaId);

            // Validar que el cliente pertenece a esta empresa
            $clientePerteneceAEmpresa = DB::table('cliente_empresa')
                ->where('cliente_id', $clienteId)
                ->where('empresa_id', $empresaId)
                ->exists();

            if (!$clientePerteneceAEmpresa) {
                DB::rollBack();
                session()->flash('error', 'El cliente no pertenece a esta empresa.');
                $this->mostrarModalConfirmacion = false;
                $this->clienteAEliminar = null;
                return;
            }

            // Obtener IDs de servicios de esta empresa (solo una vez)
            $serviciosIds = $empresa->servicios()->pluck('id')->toArray();

            // 1. Obtener todos los servicios a pagar del cliente en esta empresa
            $serviciosPagarIds = ServicioPagar::where('cliente_id', $clienteId)
                ->whereIn('servicio_id', $serviciosIds)
                ->pluck('id')
                ->toArray();

            // 2. Eliminar los pagos asociados a estos servicios
            if (!empty($serviciosPagarIds)) {
                Pagos::whereIn('id_servicio_pagar', $serviciosPagarIds)->delete();
            }

            // 3. Eliminar todos los registros de servicio_pagar de esta empresa
            ServicioPagar::where('cliente_id', $clienteId)
                ->whereIn('servicio_id', $serviciosIds)
                ->delete();

            // 4. Eliminar las relaciones cliente-servicio de esta empresa
            DB::table('cliente_servicio')
                ->where('cliente_id', $clienteId)
                ->whereIn('servicio_id', $serviciosIds)
                ->delete();

            // 5. Eliminar la relación cliente-empresa
            DB::table('cliente_empresa')
                ->where('cliente_id', $clienteId)
                ->where('empresa_id', $empresaId)
                ->delete();

            // 6. Verificar si el cliente pertenece a otras empresas
            $tieneOtrasEmpresas = DB::table('cliente_empresa')
                ->where('cliente_id', $clienteId)
                ->exists();

            // 7. Si no pertenece a ninguna otra empresa, eliminar el cliente completamente
            if (!$tieneOtrasEmpresas) {
                Cliente::destroy($clienteId);
            }

            DB::commit();

            // Actualizar la lista de clientes
            $this->clientes = $empresa->clientes;

            // Cerrar el modal
            $this->mostrarModalConfirmacion = false;
            $this->clienteAEliminar = null;

            // Mensaje de éxito
            $mensajeEliminacion = $tieneOtrasEmpresas 
                ? "Cliente '{$clienteNombre}' desvinculado de la empresa exitosamente."
                : "Cliente '{$clienteNombre}' eliminado completamente junto con todos sus servicios y pagos.";
            
            session()->flash('success', $mensajeEliminacion);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Cerrar el modal
            $this->mostrarModalConfirmacion = false;
            $this->clienteAEliminar = null;

            session()->flash('error', 'Error al eliminar el cliente: ' . $e->getMessage());
        }
    }


    public function render()
    {
        return view('livewire.ver-cliente.ver-cliente', [
        ])
        ->extends('principal.principal')
        ->section('body')
        ;
    }
}

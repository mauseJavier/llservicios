<?php

namespace App\Livewire\VerCliente;

use Livewire\Component;

use Livewire\WithPagination;

use App\Models\Cliente;
use App\Models\Empresa;
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
     * Elimina el cliente y todos sus servicios (pagos e impagos)
     * También elimina los pagos asociados y las relaciones en tablas pivot
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

            // 1. Obtener todos los servicios a pagar del cliente
            $serviciosPagar = ServicioPagar::where('cliente_id', $clienteId)->get();

            // 2. Eliminar los pagos asociados a estos servicios
            foreach ($serviciosPagar as $servicioPagar) {
                Pagos::where('id_servicio_pagar', $servicioPagar->id)->delete();
            }

            // 3. Eliminar todos los registros de servicio_pagar (pagos e impagos)
            ServicioPagar::where('cliente_id', $clienteId)->delete();

            // 4. Eliminar las relaciones cliente-servicio en la tabla pivot
            DB::table('cliente_servicio')
                ->where('cliente_id', $clienteId)
                ->delete();

            // 5. Eliminar la relación cliente-empresa en la tabla pivot
            DB::table('cliente_empresa')
                ->where('cliente_id', $clienteId)
                ->delete();

            // 6. Finalmente, eliminar el cliente
            Cliente::destroy($clienteId);

            DB::commit();

            // Actualizar la lista de clientes
            $empresa = Empresa::find(Auth::user()->empresa_id);
            $this->clientes = $empresa->clientes;

            // Cerrar el modal
            $this->mostrarModalConfirmacion = false;
            $this->clienteAEliminar = null;

            // Mensaje de éxito
            session()->flash('success', "Cliente '{$clienteNombre}' eliminado exitosamente junto con todos sus servicios y pagos.");

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

<?php

namespace App\Livewire\VerCliente;

use Livewire\Component;

use Livewire\WithPagination;



use App\Models\Cliente;
use App\Models\Empresa;
use Illuminate\Support\Facades\Auth;

class VerCliente extends Component
{

    use WithPagination;

    public $idCliente;
    public $clientes;
    public $buscarCliente;


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


    public function render()
    {
        return view('livewire.ver-cliente.ver-cliente', [
        ])
        ->extends('principal.principal')
        ->section('body')
        ;
    }
}

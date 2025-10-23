<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Carbon;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Cliente;
use App\Models\ServicioPagar;

class PanelController extends Controller
{
    public function index(){

        $usuario = Auth::user();
        
        // Buscar cliente por DNI usando Eloquent
        $cliente = Cliente::where('dni', $usuario->dni)->first();
        
        if ($cliente) {
            // Obtener servicios impagos usando la relación del cliente con scope
            $serviciosImpagos = $cliente->serviciosPagar()
                ->impagos()
                ->with(['servicio.empresa'])
                ->get()
                ->map(function ($servicioPagar) {
                    return (object) [
                        'servicio_id' => $servicioPagar->id,
                        'fechaCobro' => $servicioPagar->created_at,
                        'nombreServicio' => $servicioPagar->servicio->nombre,
                        'linkPago' => $servicioPagar->servicio->linkPago,
                        'imagenServicio' => $servicioPagar->servicio->imagen,
                        'nombreEmpresa' => $servicioPagar->servicio->empresa->nombre,
                        'cantidadServicio' => $servicioPagar->cantidad,
                        'precioServicio' => $servicioPagar->precio,
                        'total' => $servicioPagar->total, // Usando el accessor
                        'estado' => $servicioPagar->estado
                    ];
                });
        } else {
            $serviciosImpagos = collect();
        }

        return view('panel.panel', compact('serviciosImpagos'))->render();
    }
}

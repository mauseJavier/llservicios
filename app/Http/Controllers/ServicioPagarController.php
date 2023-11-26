<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class ServicioPagarController extends Controller
{
    
    public function index(){

        $usuario = Auth::user();

        $datos = DB::select('SELECT e.nombre AS nombreCliente, b.nombre AS nombreServicio, c.nombre AS nombreEmpresa, a.precio , a.estado, a.created_at as fechaCreacion 
                            FROM servicio_pagar a, servicios b, empresas c, clientes e 
                            WHERE a.servicio_id = b.id AND b.empresa_id = c.id AND a.cliente_id = e.id AND c.id = ?;', [$usuario->empresa_id,]);
        
        return $datos;
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Carbon;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PanelController extends Controller
{
    public function index(){

        $usuario = Auth::user();
        // return $usuario;
        $cliente = DB::select('SELECT a.id AS usuarioId, b.id AS clienteId ,b.* FROM users a, clientes b WHERE a.dni = b.dni AND a.dni = ?', [$usuario->dni]);
        // return $cliente;

        if (count($cliente) == 1){

            $serviciosImpagos = DB::select('SELECT
            a.id AS servicio_id,
            a.created_at AS fechaCobro,
            b.nombre AS nombreServicio,
            b.linkPago as linkPago,
            b.imagen as imagenServicio,
            c.nombre AS nombreEmpresa,
            a.cantidad AS cantidadServicio,
            a.precio AS precioServicio,
            (a.cantidad * a.precio) AS total,
            a.estado AS estado
        FROM
            servicio_pagar a,
            servicios b,
            empresas c
        WHERE
            a.servicio_id = b.id AND b.empresa_id = c.id AND a.cliente_id = ? AND a.estado = ?', [$cliente[0]->clienteId,'impago']);

        }else{

            $serviciosImpagos = array();
        }


        
        // return $serviciosImpagos;

        return view('panel.panel',compact('serviciosImpagos'))->render();


    }
}

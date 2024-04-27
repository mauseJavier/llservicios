<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\FormatoRegistroRecibo;

class FormatoRegistroReciboController extends Controller
{
    public function index (){

        $filas = FormatoRegistroRecibo::all();

        // return $filas;

        return view('reciboSueldo.verFormato', compact('filas'))->render();

    }

    public function store(Request $request){

        // return $request;

        // {
        //     "_token": "OuK9mOo84zLqTk5Jdgd5TTB4IdyjeKGGB8FFwr1f",
        //     "_method": "POST",
        //     "tipo": "deducciones",
        //     "codigo": "234234A",
        //     "descripcion": "GYM BASICO",
        //     "cantidad": "55",
        //     "importe": "2222"
        //   }


        $usuario = Auth::user();

        $id = FormatoRegistroRecibo::create([


            'tipo' => $request->tipo,
            'codigo'=> $request->codigo,
            'descripcion'=> $request->descripcion,
            'cantidad'=>$request->cantidad,
            'importe'=>$request->importe,
            'empresa_id'=>$usuario->empresa_id,

        ]);


       return redirect()->route('formatoRegistro');



    }
}

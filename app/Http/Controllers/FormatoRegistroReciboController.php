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

    public function serch(Request $request){

        $filas = FormatoRegistroRecibo::where('codigo','like','%'.$request->busqueda.'%')
        ->orWhere('descripcion','like','%'.$request->busqueda.'%')
        ->orWhere('cantidad','like','%'.$request->busqueda.'%')
        ->orWhere('importe','like','%'.$request->busqueda.'%')
        ->get();

        return view('reciboSueldo.verFormato', compact('filas'))->render();

    }

    public function update($id){

        $registro = FormatoRegistroRecibo::find($id);

        return view('reciboSueldo.updateRegistro', compact('registro'))->render();

    }

    public function updateId(Request $request, FormatoRegistroRecibo $id){

        // return array('id'=>$id,
        //                 'request'=>$request->tipo);


            $id->tipo = $request->tipo;
            $id->codigo = $request->codigo;
            $id->descripcion = $request->descripcion;
            $id->cantidad = $request->cantidad;
            $id->importe = $request->importe;

            $id->save();

            return redirect()->route('formatoRegistro')->with('mensaje','Modificado Correcto');


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

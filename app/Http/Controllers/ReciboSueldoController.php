<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\ReciboSueldo;

use Barryvdh\DomPDF\Facade\Pdf;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ReciboSueldoImport;


class ReciboSueldoController extends Controller
{
    

    public function todos(){

        // return Auth::user();

        $recibos = ReciboSueldo::where('cuil','like','%'.Auth::user()->dni.'%' )->get();

        // [
        //     {
        //       "id": 1,
        //       "periodo": "0000-00-00",
        //       "empleador": "munisipalidad",
        //       "cliente_id": 1,
        //       "created_at": "2024-04-11T01:48:06.000000Z",
        //       "updated_at": "2024-04-11T01:48:06.000000Z"
        //     }
        //   ]

        // return Auth::user();

        return view('reciboSueldo.reciboSueldo',['recibos'=>$recibos]
        )->render();


    }

    public function subirArchivoRecibos (Request $request){

        // return Auth::user();

        $file = $request->file('archivoRecibos');

        $arrayDetalle = Excel::toArray(new ReciboSueldoImport , $file);
        // return $arrayDetalle[0][0]['apellido_nombre'];

        $indice=0;
        $json=[];

        foreach ($arrayDetalle as $hoja) {
            
            foreach ($hoja as $key => $fila) {

                ReciboSueldo::create([
                    'periodo'=>$fila['periodo'],
                    'empleador'=>$fila['empleador'],
                    'cuil'=>$fila['cuil'],
                    'legajo'=>$fila['legajo'],
                ]);
                
                // echo  $key .': empleado '.$fila['apellido_nombre'] . '<br>';


                foreach ($fila as $key => $value) {
                //    echo $key . ' valor :'. $value .'<br>';

                    
                   if($indice>7){

                    array_push($json,array(
                        $key => $value,
                    ));
                                             
                   }

                   

                   $indice++;
                }
                
            }
        }

        // return $json;

        return redirect()->route('reciboSueldo')->with('mensaje', 'TODO BIEN');

    }


    public function imprimirRecibo(Request $request,$idRecibo){

        $recibo = ReciboSueldo::find($idRecibo);

        // {
        //     "id": 5,
        //     "periodo": "0000-00-00",
        //     "empleador": "1",
        //     "cuil": 20350796631,
        //     "legajo": 30065,
        //     "created_at": "2024-04-13T14:02:25.000000Z",
        //     "updated_at": "2024-04-13T14:02:25.000000Z"
        //   }

        $pdf = Pdf::loadView('pdf.municipalidad.reciboSueldo',compact('recibo'));


        // if($request->tamañoPapel == '80MM'){
        //     //tamaño tiket 
        //     //tamaño A4 en vertical
        //     // $pdf->setPaper('A7', 'portrait');
        //     $pdf->set_paper(array(0, 0, 226.772, 500), 'portrait');
        // }


        $nombreArchivo= 'recibo.pdf';
        return $pdf->stream($nombreArchivo, [ "Attachment" => true]);
        // return $pdf->download($nombreArchivo, [ "Attachment" => true]);


    }
}

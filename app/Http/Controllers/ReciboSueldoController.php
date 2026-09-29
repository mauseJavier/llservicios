<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\ReciboSueldo;
use App\Models\FormatoRegistroRecibo;

use Barryvdh\DomPDF\Facade\Pdf;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Storage;
 



class ReciboSueldoController extends Controller
{
    

    public function todos(Request $request){

        // return Auth::user();
        // return $request;

        if(isset($request->fecha)){
            $fechaFiltro= date('Y-m',strtotime($request->fecha));

            $recibos = ReciboSueldo::where('cuil','like','%'.Auth::user()->dni.'%' )
                                    ->where('periodo',$fechaFiltro)->get();
        }else{
            $fechaFiltro=date('Y-m');
            $recibos = ReciboSueldo::where('cuil','like','%'.Auth::user()->dni.'%' )
            ->get();
        }



        // return $recibos[0]->datos;


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

        return view('reciboSueldo.reciboSueldo',['recibos'=>$recibos,'fechaFiltro'=>$fechaFiltro]
        )->render();


    }

    public function subirArchivoRecibos(Request $request)
    {
        // FUNCIÓN DESCONTINUADA
        // La importación de Recibos de Sueldo se realizaba con maatwebsite/excel:
        //   $arrayDetalle = Excel::toArray(new ReciboSueldoImport, $file);
        // Esa librería fue retirada del proyecto (vulnerabilidades) y el módulo quedó en desuso.
        // El código anterior permanece en el historial de git:
        //   git log -p -- app/Http/Controllers/ReciboSueldoController.php
        return redirect()
            ->route('reciboSueldo')
            ->with('mensaje', 'La importación de recibos de sueldo está descontinuada.');
    }

    public function imprimirRecibo(Request $request,$idRecibo){

        $direccionLogo = Storage::path('public/logos/logoMunicipalidad.jpeg');

        
        $formato = FormatoRegistroRecibo::where('empresa_id',Auth::user()->empresa_id)->get();
        $recibo = ReciboSueldo::where('id',$idRecibo)->get();

        $mapeoIngresos=array();
        $mapeoDeducciones=array();
        $mapeoTotal=array();




        // return array('todo'=> $recibo,
        //             'recibo'=>( $recibo[0]['datos']));

        $datos = json_decode( $recibo[0]['datos'],true);

        foreach ($formato as $value) {

            if($value->tipo == 'ingresos'){
                if($datos[$value->importe] != 0){
                    $mapeoIngresos[]= array(
                        'codigo'=> $datos[$value->codigo],
                        'descripcion'=> $value->descripcion,
                        'cantidad'=>$datos[$value->cantidad],
                        'importe'=>$datos[$value->importe],
                    );
                    
                }

            }elseif($value->tipo == 'deducciones'){

                if($datos[$value->importe] != 0){
                    $mapeoDeducciones[]= array(
                        'codigo'=> $datos[$value->codigo],
                        'descripcion'=> $value->descripcion,
                        'cantidad'=>$datos[$value->cantidad],
                        'importe'=>$datos[$value->importe],
                    );
                  
                }


            }elseif($value->tipo == 'total'){

               
                    $mapeoTotal[]= array(
                        
                        'descripcion'=> $value->descripcion,
                       
                        'importe'=>$datos[$value->importe],
                    );
                  
               


            }



            
        }




        $pdf = Pdf::loadView('pdf.municipalidad.reciboSueldo',[
                            'recibo'=>$recibo[0],
                            'datos'=>json_decode( $recibo[0]['datos']),
                            'mapeoIngresos'=>$mapeoIngresos,
                            'mapeoDeducciones'=>$mapeoDeducciones,
                            'mapeoTotal'=>$mapeoTotal, 
                            'direccionLogo'=>$direccionLogo,                     


                        ]);


        // if($request->tamañoPapel == '80MM'){
        //     //tamaño tiket 
        //     //tamaño A4 en vertical
        //     // $pdf->setPaper('A7', 'portrait');
        //     $pdf->set_paper(array(0, 0, 226.772, 500), 'portrait');
        // }


        $nombreArchivo= 'Recibo '.$recibo[0]->apellidoNombre.' '.$recibo[0]->periodo.'.pdf';
        return $pdf->stream($nombreArchivo, [ "Attachment" => true]);
        // return $pdf->download($nombreArchivo, [ "Attachment" => true]);


    }
}

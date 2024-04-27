<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\ReciboSueldo;
use App\Models\FormatoRegistroRecibo;

use Barryvdh\DomPDF\Facade\Pdf;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ReciboSueldoImport;

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

    public function subirArchivoRecibos (Request $request){

        // return Auth::user();

        $file = $request->file('archivoRecibos');

        $arrayDetalle = Excel::toArray(new ReciboSueldoImport , $file);
        // return $arrayDetalle[0][0]['apellido_nombre'];

        // return $arrayDetalle;




        foreach ($arrayDetalle as $hoja) {
            
            foreach ($hoja as $key => $fila) {


                
                // echo  $key .': empleado '.$fila['apellido_nombre'] . '<br>';


                $indice=0;
                $tres=0;

                $key1 =''; $codigo='';
                $key2 =''; $cantidad='';
                $key3 =''; $importe='';



                // foreach ($fila as $key => $value) {                    


                //    if($indice>7){
                //     // echo 'clave: '.$key . ' valor: '. $value .' valorTres: '.$tres. '<br>';

                //             if($tres<=2){

                //                 switch ($tres) {
                //                     case 0:
                //                         $codigo= $value;
                //                         $key1=$key;
                //                         break;
                //                     case 1:
                //                         $cantidad= $value;
                //                         $key2=$key;
                //                         break;
                //                     case 2:
                //                         $importe= $value;
                //                         $key3=$key;

                //                         break;
                                    
                //                 }
                                
                                
                //                 $tres++;
                //             }else{

                //                 $tres = 0;
                //             }
                                             
                //    }

                //    $json[]= array(
                //         $key1 => $codigo,
                //         $key2 => $cantidad,
                //         $key3 => $importe,
                //         'tres'=>$tres,
                //     );
                   

                //    $indice++;
                // }


                    // dump($fila);

                $nuevoRecibo = ReciboSueldo::create([
                    'periodo'=>date("Y-m", strtotime($fila['periodo'])),
                    'empleador'=>$fila['empleador'],
                    'apellidoNombre'=>$fila['apellido_nombre'],
                    'cuil'=>$fila['cuil'],
                    'legajo'=>$fila['legajo'],
                    'fechaIngreso'=>$fila['fecha_ingreso'],
                    'categoria'=>$fila['categoria'],
                    'datos'=>json_encode($fila),
                ]);
                
            }
        }

        // return $nuevoRecibo;

        return redirect()->route('reciboSueldo')->with('mensaje', 'Archivo procesaso');

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

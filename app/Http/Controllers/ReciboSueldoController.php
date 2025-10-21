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
        $formato = FormatoRegistroRecibo::where('empresa_id',Auth::user()->empresa_id)->get();

        //FORMATO
        // [
            //     {
            //     "id": 1,
            //     "tipo": "ingresos",
            //     "codigo": "codigo_basico",
            //     "descripcion": "Basico",
            //     "cantidad": "cantidad_basico",
            //     "importe": "monto_basico",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 2,
            //     "tipo": "ingresos",
            //     "codigo": "codigo_antiguedad",
            //     "descripcion": "antiguedad",
            //     "cantidad": "cantidad_antiguedad",
            //     "importe": "monto_antiguedad",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 3,
            //     "tipo": "ingresos",
            //     "codigo": "codigo_resolucion",
            //     "descripcion": "resolucion",
            //     "cantidad": "cantidad_resolucion",
            //     "importe": "monto_resolucion",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 4,
            //     "tipo": "ingresos",
            //     "codigo": "codigo_basico",
            //     "descripcion": "Basico",
            //     "cantidad": "cantidad_basico",
            //     "importe": "monto_basico",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 5,
            //     "tipo": "ingresos",
            //     "codigo": "codigo_categoria23",
            //     "descripcion": "categoria23",
            //     "cantidad": "cantidad_categoria23",
            //     "importe": "monto_categoria23",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 6,
            //     "tipo": "ingresos",
            //     "codigo": "codigo_titulo",
            //     "descripcion": "titulo",
            //     "cantidad": "cantidad_titulo",
            //     "importe": "monto_titulo",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 7,
            //     "tipo": "ingresos",
            //     "codigo": "codigo_presentismo",
            //     "descripcion": "presentismo",
            //     "cantidad": "cantidad_presentismo",
            //     "importe": "monto_presentismo",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 8,
            //     "tipo": "ingresos",
            //     "codigo": "codigo_remunerativo",
            //     "descripcion": "remunerativo",
            //     "cantidad": "cantidad_remunerativo",
            //     "importe": "monto_remunerativo",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 9,
            //     "tipo": "ingresos",
            //     "codigo": "codigo_zona",
            //     "descripcion": "zona",
            //     "cantidad": "cantidad_zona",
            //     "importe": "monto_zona",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 10,
            //     "tipo": "ingresos",
            //     "codigo": "codigo_remunerativo_no_bonificable",
            //     "descripcion": "remunerativo_no_bonificable",
            //     "cantidad": "cantidad_remunerativo_no_bonificable",
            //     "importe": "monto_remunerativo_no_bonificable",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 11,
            //     "tipo": "ingresos",
            //     "codigo": "codigo_responsabilidad",
            //     "descripcion": "responsabilidad",
            //     "cantidad": "cantidad_responsabilidad",
            //     "importe": "monto_responsabilidad",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 12,
            //     "tipo": "ingresos",
            //     "codigo": "codigo_sac_proporcional",
            //     "descripcion": "sac_proporcional",
            //     "cantidad": "cantidad_sac_proporcional",
            //     "importe": "monto_sac_proporcional",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 13,
            //     "tipo": "ingresos",
            //     "codigo": "codigo_horas_extras_50",
            //     "descripcion": "horas_extras_50",
            //     "cantidad": "cantidad_horas_extras_50",
            //     "importe": "monto_horas_extras_50",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 14,
            //     "tipo": "ingresos",
            //     "codigo": "codigo_resoluc_8095",
            //     "descripcion": "resoluc_8095",
            //     "cantidad": "cantidad_resoluc_8095",
            //     "importe": "monto_resoluc_8095",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 15,
            //     "tipo": "ingresos",
            //     "codigo": "codigo_gastos_presentacion",
            //     "descripcion": "gastos_presentacion",
            //     "cantidad": "cantidad_gastos_presentacion",
            //     "importe": "monto_gastos_representacion",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 16,
            //     "tipo": "ingresos",
            //     "codigo": "codigo_total_remun",
            //     "descripcion": "total_remun",
            //     "cantidad": "cantidad_total_remun",
            //     "importe": "monto_total_remun",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 17,
            //     "tipo": "ingresos",
            //     "codigo": "codigo_asignacion_familiar",
            //     "descripcion": "asignacion_familiar",
            //     "cantidad": "cantidad_asignacion_familiar",
            //     "importe": "monto_asignacion_familiar",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 18,
            //     "tipo": "ingresos",
            //     "codigo": "codigo_ayuda_escolar",
            //     "descripcion": "ayuda_escolar",
            //     "cantidad": "cantidad_ayuda_escolar",
            //     "importe": "monto_ayuda_escolar",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 19,
            //     "tipo": "ingresos",
            //     "codigo": "codigo_ayuda_escolar_hijodiscapacitado",
            //     "descripcion": "ayuda_escolar_hijodiscapacitado",
            //     "cantidad": "cantidad_ayuda_escolar_hijodiscapacitado",
            //     "importe": "monto_ayuda_escolar_hijodiscapacitado",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 20,
            //     "tipo": "ingresos",
            //     "codigo": "codigo_asignacion_noremunerativo",
            //     "descripcion": "asignacion_noremunerativo",
            //     "cantidad": "cantidad_asignacion_noremunerativo",
            //     "importe": "monto_asignacion_noremunerativo",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 21,
            //     "tipo": "ingresos",
            //     "codigo": "codigo_refrigerio",
            //     "descripcion": "refrigerio",
            //     "cantidad": "cantidad_refrigerio",
            //     "importe": "monto_refrigerio",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 22,
            //     "tipo": "ingresos",
            //     "codigo": "codigo_refrigerio",
            //     "descripcion": "refrigerio",
            //     "cantidad": "cantidad_refrigerio",
            //     "importe": "monto_refrigerio",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 23,
            //     "tipo": "deducciones",
            //     "codigo": "codigo_impuesto_ganancias",
            //     "descripcion": "impuesto_ganancias",
            //     "cantidad": "cantidad_impuesto_ganancias",
            //     "importe": "monto_impuesto_ganancias",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 24,
            //     "tipo": "deducciones",
            //     "codigo": "codigo_aporte_os",
            //     "descripcion": "aporte_os",
            //     "cantidad": "cantidad_aporte_os",
            //     "importe": "monto_aporte_os",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 25,
            //     "tipo": "deducciones",
            //     "codigo": "codigo_aporte_jubilacion",
            //     "descripcion": "aporte_jubilacion",
            //     "cantidad": "cantidad_aporte_jubilacion",
            //     "importe": "monto_aporte_jubilacion",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 26,
            //     "tipo": "deducciones",
            //     "codigo": "codigo_issn",
            //     "descripcion": "issn",
            //     "cantidad": "cantidad_issn",
            //     "importe": "monto_issn",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 27,
            //     "tipo": "deducciones",
            //     "codigo": "codigo_deuda_asistencial_issn",
            //     "descripcion": "issn",
            //     "cantidad": "cantidad_deuda_asistencial_issn",
            //     "importe": "monto_deuda_asistencial_issn",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 28,
            //     "tipo": "deducciones",
            //     "codigo": "codigo_prestamos_turismo_issn",
            //     "descripcion": "prestamos_turismo_issn",
            //     "cantidad": "cantidad_prestamos_turismo_issn",
            //     "importe": "monto_prestamos_turismo_issn",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 29,
            //     "tipo": "deducciones",
            //     "codigo": "codigo_seg_vida",
            //     "descripcion": "seg_vida",
            //     "cantidad": "cantidad_seg_vida",
            //     "importe": "monto_seg_vida",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 30,
            //     "tipo": "deducciones",
            //     "codigo": "codigo_seguro_adicional",
            //     "descripcion": "seguro_adicional",
            //     "cantidad": "cantidad_seguro_adicional",
            //     "importe": "monto_seguro_adicional",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 31,
            //     "tipo": "deducciones",
            //     "codigo": "codigo_seguro_amparo_familiar",
            //     "descripcion": "seguro_amparo_familiar",
            //     "cantidad": "cantidad_seguro_amparo_familiar",
            //     "importe": "monto_seguro_amparo_familiar",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 32,
            //     "tipo": "deducciones",
            //     "codigo": "codigo_anticipo_haberes",
            //     "descripcion": "anticipo_haberes",
            //     "cantidad": "cantidad_anticipo_haberes",
            //     "importe": "monto_anticipo_haberes",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 33,
            //     "tipo": "deducciones",
            //     "codigo": "codigo_tranfer",
            //     "descripcion": "tranfer",
            //     "cantidad": "cantidad_tranfer",
            //     "importe": "monto_tranfer",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 34,
            //     "tipo": "deducciones",
            //     "codigo": "codigo_macro",
            //     "descripcion": "macro",
            //     "cantidad": "cantidad_macro",
            //     "importe": "monto_macro",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 35,
            //     "tipo": "deducciones",
            //     "codigo": "codigo_patagonia",
            //     "descripcion": "patagonia",
            //     "cantidad": "cantidad_patagonia",
            //     "importe": "monto_patagonia",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 36,
            //     "tipo": "deducciones",
            //     "codigo": "codigo_aporte_partidiario",
            //     "descripcion": "aporte_partidiario",
            //     "cantidad": "cantidad_aporte_partidiario",
            //     "importe": "monto_aporte_partidiario",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 37,
            //     "tipo": "deducciones",
            //     "codigo": "codigo_ipvu",
            //     "descripcion": "ipvu",
            //     "cantidad": "cantidad_ipvu",
            //     "importe": "monto_ipvu",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 38,
            //     "tipo": "deducciones",
            //     "codigo": "codigo_descuentos_retributivos",
            //     "descripcion": "descuentos_retributivos",
            //     "cantidad": "cantidad_descuentos_retributivos",
            //     "importe": "monto_descuentos_retributivos",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 39,
            //     "tipo": "deducciones",
            //     "codigo": "codigo_uoem_cuota",
            //     "descripcion": "uoem_cuota",
            //     "cantidad": "cantidad_uoem_cuota",
            //     "importe": "monto_uoem_cuota",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 40,
            //     "tipo": "deducciones",
            //     "codigo": "codigo_upcn_cuota",
            //     "descripcion": "upcn_cuota",
            //     "cantidad": "cantidad_upcn_cuota",
            //     "importe": "monto_upcn_cuota",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 41,
            //     "tipo": "deducciones",
            //     "codigo": "codigo_mudon_cuota",
            //     "descripcion": "mudon_cuota",
            //     "cantidad": "cantidad_mudon_cuota",
            //     "importe": "monto_mudon_cuota",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 42,
            //     "tipo": "deducciones",
            //     "codigo": "codigo_cuota_alimentaria",
            //     "descripcion": "cuota_alimentaria",
            //     "cantidad": "cantidad_cuota_alimentaria",
            //     "importe": "monto_cuota_alimentaria",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 43,
            //     "tipo": "deducciones",
            //     "codigo": "codigo_embargo_judiacial",
            //     "descripcion": "embargo_judiacial",
            //     "cantidad": "cantidad_embargo_judiacial",
            //     "importe": "monto_embargo_judiacial",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 44,
            //     "tipo": "deducciones",
            //     "codigo": "codigo_cuota_diniello",
            //     "descripcion": "cuota_diniello",
            //     "cantidad": "cantidad_cuota_diniello",
            //     "importe": "monto_cuota_diniello",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 45,
            //     "tipo": "deducciones",
            //     "codigo": "codigo_descuento_group",
            //     "descripcion": "descuento_group",
            //     "cantidad": "cantidad_descuento_group",
            //     "importe": "monto_descuento_group",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 46,
            //     "tipo": "deducciones",
            //     "codigo": "codigo_contribuciones_jubilatorias",
            //     "descripcion": "contribuciones_jubilatorias",
            //     "cantidad": "cantidad_contribuciones_jubilatorias",
            //     "importe": "monto_contribuciones_jubilatorias",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 47,
            //     "tipo": "deducciones",
            //     "codigo": "codigo_contribuciones_os",
            //     "descripcion": "contribuciones_os",
            //     "cantidad": "cantidad_contribuciones_os",
            //     "importe": "monto_contribuciones_os",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 48,
            //     "tipo": "deducciones",
            //     "codigo": "codigo_contribuciones_art",
            //     "descripcion": "contribuciones_art",
            //     "cantidad": "cantidad_contribuciones_art",
            //     "importe": "monto_contribuciones_art",
            //     "empresa_id": 1
            //     },
            //     {
            //     "id": 49,
            //     "tipo": "deducciones",
            //     "codigo": "codigo_bono 1/2",
            //     "descripcion": "bono 1/2",
            //     "cantidad": "cantidad_bono 1/2",
            //     "importe": "monto_bono 1/2",
            //     "empresa_id": 1
            //     }
        // ]

        $file = $request->file('archivoRecibos');

        $arrayDetalle = Excel::toArray(new ReciboSueldoImport , $file);
        // return $arrayDetalle[0][0]['apellido_nombre'];

        $control['ok']=array();

            foreach ($formato as $key => $value) {


                    if($value->tipo == 'total'){

                        if (isset($arrayDetalle[0][0][$value->importe])){
                            $control['ok'][] = array(
                                
                                'columna'=> $value->importe,

                            );
                        }else{
                            $control['mal'][] = array(
                                
                                'columna'=> $value->importe,

                            );
                        }

                    }else{

                        if (isset($arrayDetalle[0][0][$value->codigo])){
                            $control['ok'][] = array(
                                
                                'columna'=> $value->codigo,

                            );
                        }else{
                            $control['mal'][] = array(
                                
                                'columna'=> $value->codigo,

                            );
                        }

                        if (isset($arrayDetalle[0][0][$value->cantidad])){
                            $control['ok'][] = array(
                                
                                'columna'=> $value->cantidad,

                            );
                        }else{
                            $control['mal'][] = array(
                                
                                'columna'=> $value->cantidad,

                            );
                        }

                        if (isset($arrayDetalle[0][0][$value->importe])){
                            $control['ok'][] = array(
                                
                                'columna'=> $value->importe,

                            );
                        }else{
                            $control['mal'][] = array(
                                
                                'columna'=> $value->importe,

                            );
                        }


                    }


            }


        if (!isset($control['mal'])){ //no tiene que presentar errores 

            // EN ESTE BUCLE LO QUE HACEMOS ES GUARDAR LA FILA DE CADA RECIBO   
            foreach ($arrayDetalle as $hoja) {
                
                foreach ($hoja as $key => $fila) {

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

        }else{

            // return $control;

            return view('reciboSueldo.errorRegistro', compact('control'))->render();
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

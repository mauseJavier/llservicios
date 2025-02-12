<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Cliente;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;






class GrillaController extends Controller
{

    public function GrillaBuscarCliente(Request $datos){

        $buscar = $datos->buscar;
        $usuario = Auth::user();

        $clientes = DB::select('SELECT b.* FROM cliente_empresa a, clientes b  WHERE a.cliente_id = b.id and a.empresa_id = ? and b.nombre like ?', [$usuario->empresa_id,'%' . $buscar . '%']);
        
        if (count($clientes) > 0){

            // return $clientes;

            //BUSCA LOS SERVICIOS PAGOS E IMPAGOS DE LOS CLIENTES 
            foreach ($clientes as $clave => $valor)
            {
                $servicios = DB::select("SELECT
                                MONTHNAME(a.created_at) AS `mes_creado`,
                                SUM( a.precio * a.cantidad) AS `suma_precios`,
                                CASE
                                    WHEN a.estado = 'pago' THEN 'pago'
                                    ELSE 'impago'
                                END AS estado_pago
                                
                            FROM
                                servicio_pagar a,
                                servicios b,
                                empresas c
                            WHERE
                                a.servicio_id = b.id and
                                b.empresa_id = c.id and 
                                b.empresa_id = ? and
                                a.cliente_id = ?
                            GROUP BY
                                mes_creado
                            ORDER BY
                                a.created_at ASC

                            ", [$usuario->empresa_id,$valor->id,]);

                $clientes[$clave]->datos =json_decode(json_encode($servicios), true); ; //array("enero"=>"pago");
            
            }
                //AGREGA LOS MECES QUE FALTAN PARA LA GRILLA 

                $meses_completos = [
                "January", "February", "March", "April", "May", "June",
                "July", "August", "September", "October", "November", "December"
                ];


                // Combinar los datos existentes con los meses completos
                //estos dos son iguales 
                $total = [];
                foreach ($meses_completos as $mes) {
                //ES IGUAL EN LOS INDICES PARA QU ELOS CORRA LA GRILLA
                $total[] =  [
                    "mes" => $mes,
                    "pago" => 0,
                    "impago" => 0,
                    "total" => 0,

                ];

                }


                foreach ($clientes as $clave => $valor)
                {
                $datos_completos = [];

                foreach ($meses_completos as $mes) {

                $datos_completos[] = [
                "mes_creado" => $mes,
                "suma_precios" => 0,
                "estado_pago" => "pago"
                ];

                }

                foreach($datos_completos as $index => $dc){

                foreach ($valor->datos as $dato) {                   

                if ($dato['mes_creado'] === $dc['mes_creado']) {                        
                    // echo '<br>'. $valor->nombre .' mes '. ($dato['mes_creado']);
                    $datos_completos[$index] = [
                        "mes_creado" => $dato ['mes_creado'],
                        "suma_precios" => $dato ['suma_precios'],
                        "estado_pago" => $dato ['estado_pago']
                    ];

                }


                }


                if($datos_completos[$index]['estado_pago'] == 'pago'){
                    $total[$index]['pago'] += $datos_completos[$index]['suma_precios'];
                    $total[$index]['total'] += $datos_completos[$index]['suma_precios'];
                }else{
                    $total[$index]['impago'] -= $datos_completos[$index]['suma_precios'];
                    $total[$index]['total'] -= $datos_completos[$index]['suma_precios'];
                }


                }

                // Imprimir los datos completos
                // echo json_encode($datos_completos, JSON_PRETTY_PRINT);
                // return $datos_completos;
                $valor->datos = $datos_completos;



                }

                // return response()->json($total, 200);
                // return $clientes;


                // Número de elementos por página
                $perPage = 15;

                // Página actual obtenida de la consulta de la URL (puedes usar Request::input('page') en un controlador real)
                $paginaActual = (isset($request->page)) ? $request->page : 1;

                // Crear una colección para usar el método slice
                $colección = new Collection($clientes);

                // Obtener los elementos para la página actual
                $items = $colección->slice(($paginaActual - 1) * $perPage, $perPage)->all();

                // Crear una instancia de LengthAwarePaginator
                $datosPaginados = new LengthAwarePaginator($items, count($colección), $perPage, $paginaActual, [
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                ]);

                        //ESTO ES PARA EL PAGINADOR
                // $usuarios->withPath('/admin/users');
                // $clientesPaginados->appends(['Buscar' => $datoBuscado]);
            
                // return $datosPaginados;

                return view('grilla.grilla',['clientes'=>$datosPaginados, 'total'=>$total,'buscar'=>$buscar])->render();


        } else{

            return response()->json(['Sin Resultados:'=>count($clientes)], 200);
        }



    }

    public function index(Request $request)
    {

        $usuario = Auth::user();

        $clientes = DB::select('SELECT b.* FROM cliente_empresa a, clientes b  WHERE a.cliente_id = b.id and empresa_id = ?', [$usuario->empresa_id,]);
        

        //BUSCA LOS SERVICIOS PAGOS E IMPAGOS DE LOS CLIENTES 
        foreach ($clientes as $clave => $valor)
        {
            $servicios = DB::select("SELECT
                                        MONTHNAME(a.created_at) AS `mes_creado`,
                                        SUM( a.precio * a.cantidad) AS `suma_precios`,
                                        CASE
                                            WHEN a.estado = 'pago' THEN 'pago'
                                            ELSE 'impago'
                                        END AS estado_pago
                                        
                                    FROM
                                        servicio_pagar a,
                                        servicios b,
                                        empresas c
                                    WHERE
                                        a.servicio_id = b.id and
                                        b.empresa_id = c.id and 
                                        b.empresa_id = ? and
                                        a.cliente_id = ?
                                    GROUP BY
                                        mes_creado
                                    ORDER BY
                                        a.created_at ASC

                                    ", [$usuario->empresa_id,$valor->id,]);

            $clientes[$clave]->datos =json_decode(json_encode($servicios), true); ; //array("enero"=>"pago");
           
        }

        // return $servicios;
        // var_dump ($clientes);
        // die;

        //AGREGA LOS MECES QUE FALTAN PARA LA GRILLA 

        $meses_completos = [
            "January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"
        ];
       
        
        // Combinar los datos existentes con los meses completos
        //estos dos son iguales 
        $total = [];
        foreach ($meses_completos as $mes) {
            //ES IGUAL EN LOS INDICES PARA QU ELOS CORRA LA GRILLA
            $total[] =  [
                "mes" => $mes,
                "pago" => 0,
                "impago" => 0,
                "total" => 0,
                
            ];

        }
      

        foreach ($clientes as $clave => $valor)
        {
            $datos_completos = [];
  
            foreach ($meses_completos as $mes) {

                $datos_completos[] = [
                    "mes_creado" => $mes,
                    "suma_precios" => 0,
                    "estado_pago" => "pago"
                ];
    
            }

            foreach($datos_completos as $index => $dc){

                foreach ($valor->datos as $dato) {                   

                    if ($dato['mes_creado'] === $dc['mes_creado']) {                        
                        // echo '<br>'. $valor->nombre .' mes '. ($dato['mes_creado']);
                        $datos_completos[$index] = [
                            "mes_creado" => $dato ['mes_creado'],
                            "suma_precios" => $dato ['suma_precios'],
                            "estado_pago" => $dato ['estado_pago']
                        ];

                    }

                    
                }

                if($datos_completos[$index]['estado_pago'] == 'pago'){
                    $total[$index]['pago'] += $datos_completos[$index]['suma_precios'];
                    $total[$index]['total'] += $datos_completos[$index]['suma_precios'];
                }else{
                    $total[$index]['impago'] -= $datos_completos[$index]['suma_precios'];
                    $total[$index]['total'] -= $datos_completos[$index]['suma_precios'];
                }


            }
            
            // Imprimir los datos completos
            // echo json_encode($datos_completos, JSON_PRETTY_PRINT);
            // return $datos_completos;
            $valor->datos = $datos_completos;
            
            
           
        }
 
        // return response()->json($total, 200);
        // return $clientes;




        // Número de elementos por página
        $perPage = 15;

        // Página actual obtenida de la consulta de la URL (puedes usar Request::input('page') en un controlador real)
        $paginaActual = (isset($request->page)) ? $request->page : 1;

        // Crear una colección para usar el método slice
        $colección = new Collection($clientes);

        // Obtener los elementos para la página actual
        $items = $colección->slice(($paginaActual - 1) * $perPage, $perPage)->all();

        // Crear una instancia de LengthAwarePaginator
        $datosPaginados = new LengthAwarePaginator($items, count($colección), $perPage, $paginaActual, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);

                //ESTO ES PARA EL PAGINADOR
        // $usuarios->withPath('/admin/users');
        // $clientesPaginados->appends(['Buscar' => $datoBuscado]);
    
        // return $datosPaginados;
        
        

        return view('grilla.grilla',['clientes'=>$datosPaginados, 'total'=>$total])->render();
    }
}

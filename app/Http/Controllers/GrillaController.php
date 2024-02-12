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
    public function index(Request $request)
    {

        $usuario = Auth::user();

        $clientes = DB::select('SELECT b.* FROM cliente_empresa a, clientes b  WHERE a.cliente_id = b.id and empresa_id = ?', [$usuario->empresa_id,]);


        //BUSCA LOS SERVICIOS PAGOS E IMPAGOS DE LOS CLIENTES 
        foreach ($clientes as $clave => $valor)
        {
            $servicios = DB::select("SELECT
                                        MONTHNAME(`created_at`) AS `mes_creado`,
                                        SUM( precio * cantidad) AS `suma_precios`,
                                        CASE
                                            WHEN estado = 'pago' THEN 'pago'
                                            ELSE 'impago'
                                        END AS estado_pago
                                    FROM
                                        `servicio_pagar`
                                    WHERE
                                        cliente_id = ?
                                    GROUP BY
                                        mes_creado
                                    ORDER BY
                                    created_at ASC

                                    ", [$valor->id,]);

            $clientes[$clave]->datos =json_decode(json_encode($servicios), true); ; //array("enero"=>"pago");
           
        }

        // var_dump ($clientes);
        // die;

        //AGREGA LOS MECES QUE FALTAN PARA LA GRILLA 
        foreach ($clientes as $clave => $valor)
        {
            // Datos originales
            // $datos = [
            //     [
            //         "mes_creado" => "January",
            //         "suma_precios" => 9336.7,
            //         "estado_pago" => "impago"
            //     ],
            //     [
            //         "mes_creado" => "February",
            //         "suma_precios" => 3734.68,
            //         "estado_pago" => "pago"
            //     ],
            //     // ... otros datos existentes ...
            // ];
            
            // Lista completa de meses
            $meses_completos = [
                "January", "February", "March", "April", "May", "June",
                "July", "August", "September", "October", "November", "December"
            ];
            
            // Combinar los datos existentes con los meses completos
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

            }
            
            // Imprimir los datos completos
            // echo json_encode($datos_completos, JSON_PRETTY_PRINT);
            // return $datos_completos;
            $valor->datos = $datos_completos;
            
            
           
        }

       
 
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
        
        

        return view('grilla.grilla',['clientes'=>$datosPaginados])->render();
    }
}

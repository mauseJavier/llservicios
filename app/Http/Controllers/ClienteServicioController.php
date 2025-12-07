<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

use Carbon\Carbon;


use App\Models\Cliente;
use App\Models\Servicio;

class ClienteServicioController extends Controller
{
    public function index(){

        $clientes = Cliente::find(1);

        // return $clientes->servicios;

        $Servicios = Servicio::find(1);

        // return $Servicios->clientes;

        
        $usandoDB = DB::select('SELECT a.*,a.precio as Sprecio,b.*,c.* FROM cliente_servicio a,clientes b, servicios c WHERE a.cliente_id = b.id and a.servicio_id = c.id');

        return array('clientes'=> $clientes->servicios,
                    'servicios'=> $Servicios->clientes,
                    'DB'=> $usandoDB,);

    }

    public function agregarCliente(Request $request, $Servicio){


        $usuario = Auth::user();
        // devolver servicios activos 
        // para agregar a un cliente
        // $serviciosActivos = Servicio::where('activo', true)->get();
        

        $servicio = Servicio::where('activo', true)->where('id', $Servicio)->first();

        if (!$servicio) {
            return redirect()->route('Servicios.index')->with('status','Servicio no encontrado o inactivo');
        }

        
        $datoBuscado = $request->Buscar;
        // Obtén la fecha y hora actual
        $fechaActual = Carbon::now();

        // Formatea la fecha y hora en el formato adecuado para el campo datetime-local
        $fechaFormateada = $fechaActual->addYears(1)->format('Y-m-d\TH:i');
     
        // $clientes = Cliente::where('empresa_id','=',$usuario->empresa_id)
        //                     ->where(function($query) use ($datoBuscado){
        //                         $query->where('nombre','like','%' .$datoBuscado.'%')
        //                         ->orWhere('dni','like','%' .$datoBuscado.'%')
        //                         ->orWhere('correo','like','%' .$datoBuscado.'%');
        //                     })
        //                     // ->toSql();
        //                     ->orderBy('id', 'DESC')
        //                     ->paginate(15);

        // $clientesViejo = DB::select('SELECT * FROM clientes WHERE  
        //             (nombre LIKE ? OR dni LIKE ? OR correo LIKE ?) 
        //             ORDER BY id DESC', [
                         
        //                 '%' . $datoBuscado . '%',
        //                 '%' . $datoBuscado . '%',
        //                 '%' . $datoBuscado . '%',
        //             ]);

        $clientes = DB::select('SELECT c.*, b.nombre as nombreEmpresa FROM cliente_empresa a, empresas b, clientes c 
                            WHERE a.cliente_id = c.id and a.empresa_id = b.id AND a.empresa_id = ? and 
                            (c.nombre LIKE ? OR c.dni LIKE ? OR c.correo LIKE ?)' ,
                            [$usuario->empresa_id,
                            '%' . $datoBuscado . '%',
                            '%' . $datoBuscado . '%',
                            '%' . $datoBuscado . '%',]);

        // return array('viejo'=>$clientesViejo,
        //                 'nuevo'=>$clientes);

        $clientesMiembro = DB::select('SELECT a.*,b.* FROM cliente_servicio a, clientes b WHERE a.cliente_id = b.id AND a.servicio_id= ?', [$servicio->id]);

        // return $clientesMiembro;

        // Extraer los IDs de los clientes utilizados
        $clientesUtilizadosIds = array_column($clientesMiembro, 'cliente_id');

        // Filtrar los clientes que no están en el array de clientes utilizados
        $clientesNoUtilizados = array_filter($clientes, function ($cliente) use ($clientesUtilizadosIds) {
            return !in_array($cliente->id, $clientesUtilizadosIds);
        });


        
        // Convertir el array filtrado en un array indexado
        $clientesNoUtilizados = array_values($clientesNoUtilizados);

        // Imprimir o utilizar el nuevo array
        // return($clientesNoUtilizados);



        

        // Imprimir o utilizar el nuevo array
        // return array('clientes'=>$clientes,
        //             'clientesUtilizados'=>$clientesMiembro,
        //             'diferencia'=>$clientesNoUtilizados,);


        // Número de elementos por página
        $perPage = 15;

        // Página actual obtenida de la consulta de la URL (puedes usar Request::input('page') en un controlador real)
        $paginaActual = (isset($request->page)) ? $request->page : 1;

        // Crear una colección para usar el método slice
        $colección = new Collection($clientesNoUtilizados);

        // Obtener los elementos para la página actual
        $items = $colección->slice(($paginaActual - 1) * $perPage, $perPage)->all();

        // Crear una instancia de LengthAwarePaginator
        $clientesPaginados = new LengthAwarePaginator($items, count($colección), $perPage, $paginaActual, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);

        // return $clientesPaginados; 
        // Obtener la consulta SQL ->toSql()
        // dd ($clientes);

        // return response()->json(['clientes'=>$clientes,'empresa'=>$usuario->empresa_id]);



        //ESTO ES PARA EL PAGINADOR
        // $usuarios->withPath('/admin/users');
        $clientesPaginados->appends(['Buscar' => $datoBuscado]);
    
        // return $clientes;
        
     return view('servicios.AgregarCliente',['servicio'=>$servicio,
                'clientes'=>$clientesPaginados,
                'clientesMiembro'=>$clientesMiembro,
                'buscar'=>$datoBuscado,
                'vencimiento'=>$fechaFormateada]
            )->render();
    }

    public function agregarClienteAServicio(Request $request){

        // return $request;
        $servicio = Servicio::find($request->Servicio);
        $fecha = date('y-m-d H:i:s');
        // Convierte la cadena de texto a un objeto Carbon
        $fechaCarbon = Carbon::parse($request->vencimiento);

        // Formatea la fecha en el formato deseado
        $fechaFormateada = $fechaCarbon->format('y-m-d H:i:s');

        // return $servicio;

        //    $fila=  DB::insert('INSERT INTO `cliente_servicio`(`cliente_id`, `servicio_id`, `precio`, `vencimiento`, `created_at`, `updated_at`) 
        //                     VALUES (?,?,?,?,?,?)', 
        //                     [$request->Cliente,$request->Servicio,$servicio->precio,$fecha,$fecha,$fecha])->insertGetId();

        $fila = DB::table('cliente_servicio')->where('cliente_id', $request->Cliente)
                                            ->where('servicio_id',$request->Servicio)->get();
            if( count($fila) >0 ){
                // return 'existen otros elemntos ';
                return redirect()->route('ServiciosAgregarCliente',['Servicio'=>$servicio->id])->with('status','Cliente ya pertenece a este Servicio');
            }else{

                $id = DB::table('cliente_servicio')->insertGetId([
                    'cliente_id' => $request->Cliente,
                    'servicio_id' => $request->Servicio,
                    'cantidad' => $request->cantidad,
                    'vencimiento' => $fechaFormateada,
                    'created_at' => $fecha,
                    'updated_at' => $fecha,
                    
                ]);

                // return 'agregado correcto '. $id;
                return redirect()->route('ServiciosAgregarCliente',['Servicio'=>$servicio->id])->with('status','Cliente agregado id:'.$id);

            }


    }

    public function quitarClienteAServicio(Request $request){

        // return $request;

       $clientesEliminados =  DB::delete('DELETE FROM `cliente_servicio` WHERE cliente_id = ? AND servicio_id = ? ',
                     [$request->Cliente,
                     $request->Servicio,]);

        if($clientesEliminados > 0 ){
            return redirect()->route('ServiciosAgregarCliente',['Servicio'=>$request->Servicio])->with('status','Cliente Quitado');
        }else{
            return redirect()->route('ServiciosAgregarCliente',['Servicio'=>$request->Servicio])->with('status','Error (no se elimino el CLiente)');
            // throw new \Exception('Ocurrió una condición no deseada.');
        }

        // return redirect()->route('ServiciosAgregarCliente',['Servicio'=>$servicio->id])->with('status','Cliente ya pertenece a este Servicio');

    }
}








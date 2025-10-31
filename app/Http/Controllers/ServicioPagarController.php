<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Models\FormaPago;
use App\Models\Pagos;
use App\Models\Servicio;
use App\Models\ServicioPagar;


use App\Events\PagoServicioEvent;
use App\Events\NuevoServicioPagarEvent;


use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

use Barryvdh\DomPDF\Facade\Pdf;

class ServicioPagarController extends Controller
{
    
    public function ServiciosImpagos(Request $request){

        $usuario = Auth::user();

        $datos = DB::select('SELECT
                                a.id AS idServicioPagar,
                                e.id as idCliente,
                                e.nombre AS nombreCliente,
                                e.dni as dniCliente,
                                b.nombre AS nombreServicio,
                                c.nombre AS nombreEmpresa,
                                a.precio AS precioUnitario,
                                ROUND(a.precio * a.cantidad, 2) AS total,
                                a.cantidad as cantidad,
                                a.estado,
                                a.created_at AS fechaCreacion
                            FROM
                                servicio_pagar a,
                                servicios b,
                                empresas c,
                                clientes e
                            WHERE
                                a.servicio_id = b.id AND b.empresa_id = c.id AND a.cliente_id = e.id AND a.estado = ? AND c.id = ?
                            ORDER BY a.id DESC', ['impago',$usuario->empresa_id,]);


        // Número de elementos por página
        $perPage = 15;

        // Página actual obtenida de la consulta de la URL (puedes usar Request::input('page') en un controlador real)
        $paginaActual = (isset($request->page)) ? $request->page : 1;

        // Crear una colección para usar el método slice
        $colección = new Collection($datos);

        // Obtener los elementos para la página actual
        $items = $colección->slice(($paginaActual - 1) * $perPage, $perPage)->all();

        // Crear una instancia de LengthAwarePaginator
        $datosPaginados = new LengthAwarePaginator($items, count($colección), $perPage, $paginaActual, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);

                //ESTO ES PARA EL PAGINADOR
        // $usuarios->withPath('/admin/users');
        // $clientesPaginados->appends(['Buscar' => $datoBuscado]);
    
        // return $clientes;
        
     return view('servicios.ServiciosImpagos',['servicios'=>$datosPaginados,
               ]
            )->render();
    }

    public function ServicioPagarBuscarCliente(Request $request,$estado){

        // return [$estado,$request];

        $datoBuscado= $request->buscar;

        $fechaDesde = (isset($request->fechaDesde)) ? $request->fechaDesde : '2000-01-01';
        $fechaHasta  = (isset($request->fechaHasta)) ? $request->fechaHasta : '3000-01-01';

        $usuario = Auth::user();

        $datos = DB::select('SELECT
                                a.id AS idServicioPagar,
                                e.nombre AS nombreCliente,
                                e.dni as dniCliente,
                                b.nombre AS nombreServicio,
                                c.nombre AS nombreEmpresa,
                                a.precio,
                                a.estado,
                                a.created_at AS fechaCreacion,
                                

                                ROUND(a.precio * a.cantidad, 2) AS total,
                                a.cantidad as cantidad
                                
                            FROM
                                servicio_pagar a,
                                servicios b,
                                empresas c,
                                clientes e
                            WHERE
                                a.servicio_id = b.id AND b.empresa_id = c.id AND a.cliente_id = e.id AND a.created_at >= ? AND a.created_at <= ? AND  a.estado = ? AND c.id = ? AND
                                ( e.nombre LIKE ? or
                                e.correo LIKE ? or
                                e.dni LIKE ? or
                                b.nombre LIKE ?)
                            ORDER BY a.id DESC', [ $fechaDesde,
                                                    $fechaHasta,
                                                    $estado,
                                                    $usuario->empresa_id,
                                                    '%'.$datoBuscado. '%',
                                                    '%'.$datoBuscado. '%',
                                                    '%'.$datoBuscado. '%',
                                                    '%'.$datoBuscado. '%']);


        // Número de elementos por página
        $perPage = 15;

        // Página actual obtenida de la consulta de la URL (puedes usar Request::input('page') en un controlador real)
        $paginaActual = (isset($request->page)) ? $request->page : 1;

        // Crear una colección para usar el método slice
        $colección = new Collection($datos);

        // Obtener los elementos para la página actual
        $items = $colección->slice(($paginaActual - 1) * $perPage, $perPage)->all();

        // Crear una instancia de LengthAwarePaginator
        $datosPaginados = new LengthAwarePaginator($items, count($colección), $perPage, $paginaActual, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);

                //ESTO ES PARA EL PAGINADOR
        // $usuarios->withPath('/admin/users');
        $datosPaginados->appends(['buscar' => $datoBuscado]);
    
        // return $datosPaginados;
        
        if ($estado == 'pago'){
            
            return view('servicios.ServiciosPagos',['servicios'=>$datosPaginados, 'buscar'=>$datoBuscado,'fechaDesde'=>$fechaDesde,'fechaHasta'=>$fechaHasta]
            )->render();

        }else{
            return view('servicios.ServiciosImpagos',['servicios'=>$datosPaginados, 'buscar'=>$datoBuscado,'fechaDesde'=>$fechaDesde,'fechaHasta'=>$fechaHasta]
            )->render();
        }


    }

    public function ServiciosPagos(Request $request){


        $usuario = Auth::user();

        $datos = DB::select('SELECT
                                a.id AS idServicioPagar,
                                e.nombre AS nombreCliente,
                                e.dni as dniCliente,
                                b.nombre AS nombreServicio,
                                c.nombre AS nombreEmpresa,
                                a.precio AS precioUnitario,
                                ROUND(a.precio * a.cantidad, 2) AS total,
                                a.cantidad as cantidad,
                                a.estado,
                                a.created_at AS fechaCreacion
                            FROM
                                servicio_pagar a,
                                servicios b,
                                empresas c,
                                clientes e
                            WHERE
                                a.servicio_id = b.id AND b.empresa_id = c.id AND a.cliente_id = e.id AND a.estado = ? AND c.id = ?
                            ORDER BY a.id DESC', ['pago',$usuario->empresa_id,]);


        // Número de elementos por página
        $perPage = 15;

        // Página actual obtenida de la consulta de la URL (puedes usar Request::input('page') en un controlador real)
        $paginaActual = (isset($request->page)) ? $request->page : 1;

        // Crear una colección para usar el método slice
        $colección = new Collection($datos);

        // Obtener los elementos para la página actual
        $items = $colección->slice(($paginaActual - 1) * $perPage, $perPage)->all();

        // Crear una instancia de LengthAwarePaginator
        $datosPaginados = new LengthAwarePaginator($items, count($colección), $perPage, $paginaActual, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);

                //ESTO ES PARA EL PAGINADOR
        // $usuarios->withPath('/admin/users');
        // $clientesPaginados->appends(['Buscar' => $datoBuscado]);
    
        // return $clientes;
        
     return view('servicios.ServiciosPagos',['servicios'=>$datosPaginados, 'fechaDesde'=>date('Y-m-d', strtotime('first day of this month'))
                                                                            ,'fechaHasta'=>date('Y-m-d', strtotime('last day of this month')),]
            )->render();
    }

    public function ConfirmarPago (Request $request){

        $request->validate([
            'comentario' => 'max:200',
            'idServicioPagar' => 'required',
            'importe' => 'required|numeric|min:0',
            'importeOriginal' => 'required|numeric|min:0',
            'formaPago' => 'required',
            'importe1' => 'required|numeric|min:0',
            'formaPago2' => 'nullable',
            'importe2' => 'nullable|numeric|min:0',
            'aplicarAjuste' => 'nullable',
            'tipoAjuste' => 'nullable|in:descuento,incremento',
            'ajusteTipo' => 'nullable|in:porcentaje,monto',
            'valorAjuste' => 'nullable|numeric|min:0',
        ]);

        // return $request;

        $usuario = Auth::user();
        
        // Obtener el servicio_pagar para actualizar el precio si hubo ajuste
        $servicioPagar = ServicioPagar::findOrFail($request->idServicioPagar);
        
        // Si se aplicó un ajuste, actualizar el precio en servicio_pagar
        if ($request->filled('aplicarAjuste') && $request->aplicarAjuste) {
            $importeFinal = floatval($request->importe);
            $importeOriginal = floatval($request->importeOriginal);
            
            // Calcular el nuevo precio unitario basado en el importe final
            if ($servicioPagar->cantidad > 0) {
                $nuevoPrecio = $importeFinal / $servicioPagar->cantidad;
                
                // Actualizar el precio en servicio_pagar
                $servicioPagar->update([
                    'precio' => $nuevoPrecio,
                ]);
                
                // Agregar información del ajuste al comentario
                $tipoAjusteTexto = $request->tipoAjuste === 'descuento' ? 'Descuento' : 'Incremento';
                $ajusteTexto = $request->ajusteTipo === 'porcentaje' 
                    ? $request->valorAjuste . '%' 
                    : '$' . $request->valorAjuste;
                
                $comentarioAjuste = "{$tipoAjusteTexto} aplicado: {$ajusteTexto} (Importe original: \${$importeOriginal}, Importe final: \${$importeFinal})";
                
                // Combinar con el comentario del usuario si existe
                $comentarioFinal = $request->comentario 
                    ? $request->comentario . ' | ' . $comentarioAjuste 
                    : $comentarioAjuste;
            } else {
                $comentarioFinal = $request->comentario;
            }
        } else {
            $comentarioFinal = $request->comentario;
        }
        
        // Actualizar el estado del servicio a pagado
        DB::update('UPDATE servicio_pagar SET estado=?,updated_at=? WHERE  id = ?',
                         ['pago',
                        date('Y-m-d H:i:s'),
                        $request->idServicioPagar]);

            // Preparar datos del pago
            $pago = ['idServicioPagar'=>$request->idServicioPagar,
                        'idUsuario'=>$usuario->id,
                        'importe'=>$request->importe1,
                        'forma_pago'=>$request->formaPago,
                        'forma_pago2'=>$request->formaPago2,
                        'importe2'=>$request->importe2,
                        'comentario'=>$comentarioFinal];

            // dd($pago);
                     
            PagoServicioEvent::dispatch($pago);



            if (isset( $request->comprobantePDF)){
                



                return redirect()->route('PagosVer', ['idServicioPagar' => $request->idServicioPagar]);

                
            }
    
                return redirect()->route('ServiciosImpagos')
                ->with('status', 'Pagado correcto.');
            


    }

    public function PagarServicio($idServicioPagar,$importe){

        $formaPago = FormaPago::all();
        
        // Utilizar el modelo ServicioPagar con sus relaciones
        $servicioPagar = ServicioPagar::with(['servicio', 'cliente'])
            ->findOrFail($idServicioPagar);
        
        // Preparar datos para la vista con información del cliente y servicio
        $datosServicio = (object) [
            'id' => $servicioPagar->id,
            'cantidad' => $servicioPagar->cantidad,
            'precio' => $servicioPagar->precio,
            'total' => $servicioPagar->total,
            'estado' => $servicioPagar->estado,
            'created_at' => $servicioPagar->created_at,
            'updated_at' => $servicioPagar->updated_at,
            // Datos del servicio
            'servicio_id' => $servicioPagar->servicio->id,
            'nombre' => $servicioPagar->servicio->nombre,
            'descripcion' => $servicioPagar->servicio->descripcion,
            'tiempo' => $servicioPagar->servicio->tiempo,
            'linkPago' => $servicioPagar->servicio->linkPago,
            'imagen' => $servicioPagar->servicio->imagen,
            // Datos del cliente
            'cliente_id' => $servicioPagar->cliente->id,
            'nombreCliente' => $servicioPagar->cliente->nombre,
            'correoCliente' => $servicioPagar->cliente->correo,
            'telefonoCliente' => $servicioPagar->cliente->telefono,
            'dniCliente' => $servicioPagar->cliente->dni,
            'domicilioCliente' => $servicioPagar->cliente->domicilio,
        ];

        return view('servicios.PagarServicio', [
            'servicio' => $datosServicio,
            'formaPago' => $formaPago,
            'idServicioPagar' => $idServicioPagar,
            'importe' => $importe
        ])->render();
    }

    public function NuevoCobro (){


        $usuario = Auth::user();
        // return $usuario->empresa_id;

        $servicios = DB::select('SELECT * FROM `servicios` WHERE empresa_id = ? ORDER BY nombre ASC', [$usuario->empresa_id]);
        $clientes = DB::select('SELECT b.* FROM cliente_empresa a, clientes b WHERE a.cliente_id = b.id AND a.empresa_id = ? ORDER BY b.nombre ASC;', [$usuario->empresa_id]);

        // return $servicios;
        
       return view('servicios.NuevoCobro',compact('servicios','clientes'))->render();
    }

    public function AgregarNuevoCobro (Request $request){

        $request->validate([
            'precio' => 'required|numeric|min:1',
            'cantidad' => 'required|numeric|min:0.5',
            'fecha_vencimiento' => 'nullable|date',
            'comentario' => 'nullable|string|max:1000',
            'periodo_servicio' => 'nullable|string|max:255'
        ]);

        // return $request;

        $fechaHoy = date('Y-m-d H:i:s');

        // Convertir periodo_servicio (YYYY-MM) al primer día del mes (YYYY-MM-01)
        $periodoServicio = null;
        if ($request->periodo_servicio) {
            $periodoServicio = $request->periodo_servicio . '-01';
        }

        // {
        //     "_method": "POST",
        //     "_token": "7lpAuohyL0m8WvBWr3oneaxqQ9YJvSIJ2XGxYHJd",
        //     "servicio": "140",
        //     "cliente": "4",
        //     "precio": "0",
        //     "cantidad": "1"
        //   }

        $id = DB::table('servicio_pagar')->insertGetId([
            'cliente_id' => $request->cliente,
            'servicio_id' => $request->servicio,
            'precio' => $request->precio,
            'estado' => 'impago',
            'created_at' => $fechaHoy,
            'updated_at' => $fechaHoy,
            'cantidad' => $request->cantidad,
            'fecha_vencimiento' => $request->fecha_vencimiento,
            'comentario' => $request->comentario,
            'periodo_servicio' => $periodoServicio,
        ]);


        $empresa = \App\Models\Empresa::find(Auth::user()->empresa_id);

                // Enviar WhatsApp
        // EnviarWhatsAppNuevoServicioJob::dispatch($id, $instanciaWS, $tokenWS);
        \App\Jobs\EnviarWhatsAppNuevoServicioJob::dispatch($id, $empresa->instanciaWS, $empresa->tokenWS);


        // use App\Events\NuevoServicioPagarEvent;
        NuevoServicioPagarEvent::dispatch($id);

        return redirect()->route('ServiciosImpagos')->with('status','Cobro correcto id:' .$id);
        
    }

}

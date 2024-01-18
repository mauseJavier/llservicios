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


class ClienteController extends Controller
{


    public function BuscarCliente(Request $request){      
        
        $usuario = Auth::user();
        $datoBuscado = $request->buscar;

        if(!$request->buscar){
            return redirect()->route('Cliente.index');
        }

        // $clientes = Cliente::where('empresa_id','=',$usuario->empresa_id)
        //                     ->where(function($query) use ($datoBuscado){
        //                         $query->where('nombre','like','%' .$datoBuscado.'%')
        //                         ->orWhere('dni','like','%' .$datoBuscado.'%')
        //                         ->orWhere('correo','like','%' .$datoBuscado.'%');
        //                     })
        //                     // ->toSql();
        //                     ->orderBy('id', 'DESC')
        //                     ->paginate(15);
        $clientes = DB::select('SELECT c.*, b.nombre as nombreEmpresa FROM cliente_empresa a, empresas b, clientes c 
            WHERE a.cliente_id = c.id and a.empresa_id = b.id AND a.empresa_id = ? and 
            (c.nombre LIKE ? OR c.dni LIKE ? OR c.correo LIKE ?)' ,
            [$usuario->empresa_id,
            '%' . $datoBuscado . '%',
            '%' . $datoBuscado . '%',
            '%' . $datoBuscado . '%',]);

        // Número de elementos por página
        $perPage = 15;

        // Página actual obtenida de la consulta de la URL (puedes usar Request::input('page') en un controlador real)
        $paginaActual = (isset($request->page)) ? $request->page : 1;

        // Crear una colección para usar el método slice
        $colección = new Collection($clientes);

        // Obtener los elementos para la página actual
        $items = $colección->slice(($paginaActual - 1) * $perPage, $perPage)->all();

        // Crear una instancia de LengthAwarePaginator
        $clientesPaginados = new LengthAwarePaginator($items, count($colección), $perPage, $paginaActual, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);


            //ESTO ES PARA EL PAGINADOR
        // $usuarios->withPath('/admin/users');
        $clientesPaginados->appends(['buscar' => $request->buscar]);
    
        // return $clientes;

     return view('clientes.Clientes',['clientes'=>$clientesPaginados,
                                    'buscar'=>$request->buscar]
                )->render();

    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $usuario = Auth::user();
        // $clientes =  Cliente::where('empresa_id','=',$usuario->empresa_id)
        //                     ->orderBy('id', 'DESC')->paginate(15);

        $clientes = DB::select('SELECT c.*, b.nombre as nombreEmpresa FROM cliente_empresa a, empresas b, clientes c 
                                WHERE a.cliente_id = c.id and a.empresa_id = b.id AND a.empresa_id = ?
                                ORDER BY c.id DESC',
                    [$usuario->empresa_id]);
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
        $clientesPaginados = new LengthAwarePaginator($items, count($colección), $perPage, $paginaActual, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);

        return view('clientes.Clientes',['clientes'=>$clientesPaginados])->render();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return view('clientes.Create')->render();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClienteRequest $request)
    {
        $usuario = Auth::user();

        $cliente = DB::select('SELECT * FROM `clientes` WHERE dni = ?', [$request->dni]);
        // Obtener la cantidad de filas seleccionadas
        $cantidadFilas = count($cliente);
        // return $cantidadFilas;

        if($cantidadFilas == 0 ){ //el cliente no exite en la base y se agrega 

            $id = Cliente::create(['nombre'=>$request->nombre,
                                'dni'=>$request->dni,
                                'correo'=>$request->correo,
                                'domicilio'=>$request->domicilio,
                                ]);
            // return $id->id;

            $idVinculado = DB::table('cliente_empresa')->insertGetId([
                'cliente_id' => $id->id,
                'empresa_id' => $usuario->empresa_id,
                'created_at' => date('y-m-d H:i:s'),
                'updated_at' => date('y-m-d H:i:s'),
            ]);
    
            return redirect()->route('Cliente.index')->with('status','Cliente '.$id->nombre.' agregado id:'.$id->id);

        }else{ // el cliente existe y se vincula a la empresa si no esta vinculado 
            // return $cantidadFilas;
            // return $usuario;

            $clienteVinculado = DB::select('SELECT b.correo FROM cliente_empresa a, clientes b WHERE a.cliente_id = b.id AND 
                                        (b.dni = ?) and a.empresa_id = ?', 
                                            [$request->dni ,$usuario->empresa_id]);
            // Obtener la cantidad de filas seleccionadas
            $cantidadFilas = count($clienteVinculado);
            if($cantidadFilas == 0){ //se vincula 

                // return $cliente[0]->id; //EL ID CLIENTE PARA VINCULAR 
                $id = DB::table('cliente_empresa')->insertGetId([
                    'cliente_id' => $cliente[0]->id,
                    'empresa_id' => $usuario->empresa_id,
                    'created_at' => date('y-m-d H:i:s'),
                    'updated_at' => date('y-m-d H:i:s'),
                ]);

                return redirect()->route('Cliente.index')->with('status','Cliente vinculado: '.$cliente[0]->nombre.' agregado id:'.$id);
            }else{
                return redirect()->route('Cliente.index')->with('status','Cliente ya vinculado: '.$cliente[0]->nombre);
            }

        }

     


    }

    /**
     * Display the specified resource.
     */
    public function show(Cliente $cliente)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cliente $Cliente)
    {
        // return $Cliente;

        return view('clientes.Edit', compact('Cliente'))->render();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClienteRequest $request, Cliente $Cliente)
    {
        $Cliente->update(['nombre'=>$request->nombre,
                            'dni'=>$request->dni,
                            'correo'=>$request->correo,
                            'domicilio'=>$request->domicilio,]);
        return redirect()->route('Cliente.index')
        ->with('status', 'Guardado correcto.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cliente $cliente)
    {
        //
    }
}

<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Http\Requests\StoreServicioRequest;
use App\Http\Requests\UpdateServicioRequest;
use App\Models\Servicio;

use Illuminate\Support\Facades\Auth;


class ServicioController extends Controller
{

    public function BuscarServicio(Request $buscar){      
        
        $usuario = Auth::user();
        $datoBuscado = $buscar->buscar;

        if(!$buscar->buscar){
            return redirect()->route('Servicios.index');
        }

        $servicios = Servicio::where('empresa_id','=',$usuario->empresa_id)
                            ->where(function($query) use ($datoBuscado){
                                $query->where('nombre','like','%' .$datoBuscado.'%')
                                ->orWhere('descripcion','like','%' .$datoBuscado.'%')
                                ;
                            })
                            // ->toSql();
                            ->orderBy('id', 'DESC')
                            ->paginate(15);


        // Obtener la consulta SQL ->toSql()
        //    dd ($clientes);

        // return response()->json(['clientes'=>$clientes,'empresa'=>$usuario->empresa_id]);



            //ESTO ES PARA EL PAGINADOR
        // $usuarios->withPath('/admin/users');
        $servicios->appends(['buscar' => $buscar->buscar]);
    
        // return $clientes;

     return view('servicios.Servicios',['servicios'=>$servicios,
                                    'buscar'=>$buscar->buscar]
                )->render();

    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $usuario =  Auth::user();
        
   
            $servicios = Servicio::where('empresa_id',$usuario->empresa_id)
            ->orderBy('id', 'DESC')
            ->paginate(15);
       
        // return $servicios;

        return view('servicios.Servicios',compact('servicios'))->render();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (view()->exists('servicios.Create'))
        {
            return view('servicios.Create')->render(); 
        }else{
            return 'No existe vista';
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreServicioRequest $request)
    {
        $usuario =  Auth::user();
        $id = Servicio::create([
            'nombre'=> $request->nombre,
            'precio'=> round($request->precio,2),
            'descripcion'=> $request->descripcion,
            'tiempo' => $request->tiempo,
            'empresa_id'=> $usuario->empresa_id,
            'linkPago' => $request->linkPago,
            'imagen' => $request->imagen
        ]);

        return redirect()->route('Servicios.index')
        ->with('status', 'Guardado '.$id->nombre.' id:'.$id->id .'');
        
    }

    /**
     * Display the specified resource.
     */
    public function show(Servicio $servicio)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Servicio $Servicio)
    {
        // return $Servicio;

        return view('servicios.Edit', compact('Servicio'))->render();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateServicioRequest $request, Servicio $Servicio)
    {
        // return ['r'=>$request->nombre,'S'=>$Servicio];

        $Servicio->update(['nombre'=>$request->nombre,
                            'descripcion'=>$request->descripcion,
                            'precio'=> round($request->precio,2),
                            'tiempo'=> $request->tiempo,
                            'linkPago' => $request->linkPago,
                            'imagen' => $request->imagen
                        
                        ]);
        return redirect()->route('Servicios.index')
        ->with('status', 'Actualizado Correcto.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Servicio $Servicio)
    {
        //
    }
}

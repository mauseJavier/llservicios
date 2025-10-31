<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreempresaRequest;
use App\Http\Requests\UpdateempresaRequest;
use App\Models\Empresa;

// use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $empresas = Empresa::paginate(15);
        $empresas = Empresa::orderBy('id', 'DESC')
        // ->get()
        ->paginate(15);

        return view('empresas.empresas',compact('empresas'))->render();
 
    }

    public function BuscarEmpresa(Request $buscar){

        if(!$buscar->buscar){
            return redirect()->route('empresas.index');
        }

        $empresas = Empresa::where('nombre','like','%' .$buscar->buscar.'%')
                            ->orWhere('cuit','like','%' .$buscar->buscar.'%')
                            ->orWhere('correo','like','%' .$buscar->buscar.'%')
                            ->orderBy('id', 'DESC')
                            ->paginate(15);

        // $usuarios->withPath('/admin/users');
        $empresas->appends(['buscar' => $buscar->buscar]);
    

     return view('empresas.empresas',compact('empresas'))->render();

    }

    public function UsuariosEmpresasVer($idEmpresa){
        //    $usuarios = User::where('empresa_id','=',$idEmpresa)->paginate(15);    
        //    return $usuarios;
    
            $empresa = Empresa::find($idEmpresa);
                                // ->paginate(3);
            // return $empresa;
            return view('empresas.UsuariosEmpresasVer',compact('empresa'))->render();
 
        }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('empresas.Create')->render();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreempresaRequest $request)
    // public function store(Request $request)
    {
        //
        $id = Empresa::create($request->only([
            'nombre',
            'cuit',
            'correo',
            'logo',
            'MP_ACCESS_TOKEN',
            'MP_PUBLIC_KEY',
            'client_secret',
            'client_id',
            'instanciaWS',
            'tokenWS'
        ]));
        return redirect()->route('empresas.index')->with('status','Empresa '.$id->nombre.' agregada id:'.$id->id);

    }

    /**
     * Display the specified resource.
     */
    public function show(Empresa $empresa)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Empresa $empresa)
    {
        //
        // return $empresa;
        return view('empresas.Edit',compact('empresa'))->render();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateempresaRequest $request, Empresa $empresa)
    {
        //
        // return response()->json([
        //     'request' => $request->nombre,
        //     'empresa' => $empresa,
        // ]);

        $empresa->update($request->only([
            'nombre',
            'cuit',
            'correo',
            'logo',
            'MP_ACCESS_TOKEN',
            'MP_PUBLIC_KEY',
            'client_secret',
            'client_id',
            'instanciaWS',
            'tokenWS'
        ]));
        return redirect()->route('empresas.index')
        ->with('status', 'Guardado correcto.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Empresa $empresa)
    {
        //
    }
}

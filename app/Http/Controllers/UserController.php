<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\empresa;
use App\Models\role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;


class UserController extends Controller
{
    //
    public function todosUsuarios(){

        $usuarios = User::orderBy('last_login', 'desc')->paginate(15);
        // $usuarios = $user->paginate(15);

        // return $usuarios;
        return view('usuarios.usuarios',compact('usuarios'))->render();

        // $user = Auth::user->role->nombre;
        // return $user->role->nombre;
    }

    public function registrarUsuario(Request $request){

        $validated = $request->validate([
            'nombre' => 'required',
            'correo' => 'required|unique:App\Models\User,email',
            'contraseña' => 'required',
            'dni' => 'required|unique:users,dni',
        ]);

        $user = new User;

        $user->name = $request->nombre;
        $user->email = $request->correo;
        $user->password = Hash::make($request->contraseña);
        $user->dni = $request->dni;

        $user->save();

        Auth::login($user);

        return redirect()->route('panel');

    }

    public function loginUsuario(Request $request)
    {
        
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);
 
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
 
            return redirect()->route('panel');
        }
 
        return back()->withErrors([
            'email' => 'Correo o Contraseña Incorrectos.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
    
        $request->session()->invalidate();
    
        $request->session()->regenerateToken();
    
        return redirect('/');
    }

    public function BuscarUsuario(Request $buscar){

        if(!$buscar->buscar){
            return redirect()->route('usuarios');
        }

        $usuarios = User::where('name','like','%' .$buscar->buscar.'%')
                            ->orWhere('email','like','%' .$buscar->buscar.'%')
                            ->orWhere('dni','like','%' .$buscar->buscar.'%')
                            ->orderBy('id', 'DESC')
                            ->paginate(15);

        // $usuarios->withPath('/admin/users');
        $usuarios->appends(['buscar' => $buscar->buscar]);


        // if (method_exists($empresas, 'currentPage')) {
        //     echo 'El método "map" existe en la colección.';
        // } else {
        //     echo 'El método "map" no existe en la colección.';
        // }
    

        return view('usuarios.usuarios',compact('usuarios'))->render();

    }

    public function EditarUsuario(Request $id){

        $roles = role::all();
        $empresas = empresa::all();
        $usuario = User::find($id->id);
       return view('usuarios.edit',
                    ['usuario'=>$usuario,
                    'roles'=>$roles,
                    'empresas'=>$empresas]
                    )->render();
    }

    public function UpdateUsuario(Request $datos){
        // return $datos;
        $usuario = User::find($datos->id);

        $usuario->update($datos->all());
        return redirect()->route('usuarios')
        ->with('status', 'Guardado correcto.');
    }

    public function miPerfil(){

        $empresa = empresa::find(Auth::User()->empresa_id);
        $rol = role::find(Auth::User()->role_id);

        // return $rol;

        return view('usuarios.miPerfil', ['empresa'=>$empresa, 'rol'=>$rol])->render();
    }


}

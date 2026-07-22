<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Empresa;
use App\Models\role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\AfipService;


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
        ], [
            'nombre.required' => 'El nombre es obligatorio',
            'correo.required' => 'El correo es obligatorio',
            'correo.unique' => 'Este correo ya está registrado',
            'contraseña.required' => 'La contraseña es obligatoria',
            'dni.required' => 'El DNI es obligatorio',
            'dni.unique' => 'Este DNI ya está registrado',
        ]);

        $user = new User;

        $user->name = $request->nombre;
        $user->email = $request->correo;
        $user->password = Hash::make($request->contraseña);
        $user->dni = $request->dni;

        $user->save();

        Auth::login($user);

        return redirect()->route('Servicios.index');

    }

    public function loginUsuario(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        Log::info('[LOGIN] Intento de login', ['email' => $request->email]);
        
        $authResult = Auth::attempt($credentials);
        Log::info('[LOGIN] Resultado Auth::attempt', ['result' => $authResult]);
        
        if ($authResult) {
            Log::info('[LOGIN] Autenticación exitosa, regenerando sesión');
            $request->session()->regenerate();
  
            $usuario = Auth::user();
            Log::info('[LOGIN] Datos usuario', [
                'id' => $usuario->id,
                'empresa_id' => $usuario->empresa_id,
                'role_id' => $usuario->role_id
            ]);
            
            $empresa = Empresa::where('id', $usuario->empresa_id)->get();
            Log::info('[LOGIN] Empresa encontrada', ['count' => $empresa->count()]);

            session(['logoEmpresa' => $empresa[0]->logo]);    

            $destino = in_array($usuario->role_id, [2, 3]) ? 'Grilla' : 'servicios';
            Log::info('[LOGIN] Redirigiendo a', ['destino' => $destino]);

            return redirect()->intended($destino);
        }

        Log::warning('[LOGIN] Credenciales inválidas', ['email' => $request->email]);
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
        $empresas = Empresa::all();
        $usuario = User::find($id->id);
        $puntosVenta = [];
        $puntosVentaError = null;

        if ($usuario && $usuario->empresa_id) {
            try {
                $afipService = new AfipService($usuario->empresa_id);
                $puntosVenta = $afipService->obtenerPuntosVenta() ?? [];
            } catch (\Throwable $e) {
                $puntosVentaError = $e->getMessage();
            }
        }

       return view('usuarios.edit',
                    [
                        'usuario'=>$usuario,
                        'roles'=>$roles,
                        'empresas'=>$empresas,
                        'puntosVenta'=>$puntosVenta,
                        'puntosVentaError'=>$puntosVentaError
                    ]
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

        $empresa = Empresa::find(Auth::User()->empresa_id);
        $rol = role::find(Auth::User()->role_id);

        // return $rol;

        return view('usuarios.miPerfil', ['empresa'=>$empresa, 'rol'=>$rol])->render();
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'La contraseña actual es obligatoria.',
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'password.required' => 'La nueva contraseña es obligatoria.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación no coincide con la nueva contraseña.',
        ]);

        $user = Auth::user();
        $user->password = Hash::make($validated['password']);
        $user->save();

        return back()->with('status', 'Contraseña actualizada correctamente.');
    }


}

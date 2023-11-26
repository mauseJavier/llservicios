<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
 
use App\Http\Controllers\UserController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\ClienteServicioController;
use App\Http\Controllers\ServicioPagarController;




/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::middleware('auth')->group(function () {

    Route::middleware(['RolSuper'])->group(function () { //ACCESSO SOLO SUPER
        Route::get('/usuarios', [UserController::class, 'todosUsuarios'])->name('usuarios');
        Route::get('/BuscarUsuario', [UserController::class, 'BuscarUsuario'])->name('BuscarUsuario');
        Route::get('/EditarUsuario/{id}', [UserController::class, 'EditarUsuario'])->name('EditarUsuario');
        Route::post('/UpdateUsuario', [UserController::class, 'UpdateUsuario'])->name('UpdateUsuario');

        Route::resource('/empresas', EmpresaController::class);
        Route::get('/UsuariosEmpresasVer/{idEmpresa}', [EmpresaController::class, 'UsuariosEmpresasVer'])->name('UsuariosEmpresasVer');
        Route::get('/BuscarEmpresa', [EmpresaController::class, 'BuscarEmpresa'])->name('BuscarEmpresa');
        
    });

    Route::middleware(['RolAdmin'])->group(function () {//AK CREAR UN MIDDELWARE PARA ADDMIN
        Route::resource('Cliente',ClienteController::class);
        Route::get('/BuscarCliente', [ClienteController::class, 'BuscarCliente'])->name('BuscarCliente');
 
        Route::resource('Servicios', ServicioController::class);
        Route::get('BuscarServicio',  [ServicioController::class, 'BuscarServicio'])->name('BuscarServicio');


        //AK ESTOY TRABAJANDO 
        Route::get('ClientesServicios',  [ClienteServicioController::class, 'index'])->name('ClientesServicios');
        Route::get('ServiciosAgregarCliente/{Servicio}', [ClienteServicioController::class, 'agregarCliente'])->name('ServiciosAgregarCliente'); 
        Route::get('agregarClienteAServicio', [ClienteServicioController::class, 'agregarClienteAServicio'])->name('agregarClienteAServicio'); 
        Route::get('quitarClienteAServicio', [ClienteServicioController::class, 'quitarClienteAServicio'])->name('quitarClienteAServicio'); 

        //RUTAS PAGAR SERVICIOS
        Route::get('ServicioPagar', [ServicioPagarController::class, 'index'])->name('ServicioPagar'); 


    });

    // Route::get('/usuarios', [UserController::class, 'todosUsuarios'])->name('usuarios');
    Route::view('/panel', 'panel.panel')->name('panel');
    Route::view('/miPerfil', 'usuarios.miPerfil')->name('miPerfil');

   
});


Route::get('/login', function () {

    if (Auth::check()) {
        // The user is logged in...
        return redirect('panel');
    }else{
        return view('login');
    }
    
})->name('login');


Route::view('/registro', 'registro')->name('registro');
Route::get('/logout', [UserController::class, 'logout'])->name('logout');


Route::post('/registrarUauario',[UserController::class,'registrarUsuario'])->name('registrarUsuario');
Route::post('/loginUsuario',[UserController::class,'loginUsuario'])->name('loginUsuario');


Route::get('/', function () {
    return view('welcome');
})->name('inicio');
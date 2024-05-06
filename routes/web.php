<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
 
use App\Http\Controllers\UserController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\ClienteServicioController;
use App\Http\Controllers\ServicioPagarController;
use App\Http\Controllers\PagosController;
use App\Http\Controllers\EnviarCorreoController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\GrillaController;
use App\Http\Controllers\ReciboSueldoController;
use App\Http\Controllers\FormatoRegistroReciboController;

// JOBS
use App\Jobs\TutorialJob;

//logs
// use Illuminate\Support\Facades\Log;




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

         //RUTAS DE LA GRILLA
         Route::get('/Grilla', [GrillaController::class, 'index'])->name('Grilla');
         Route::get('/GrillaBuscarCliente', [GrillaController::class, 'GrillaBuscarCliente'])->name('GrillaBuscarCliente');


         
        Route::resource('Cliente',ClienteController::class);
        Route::get('/BuscarCliente', [ClienteController::class, 'BuscarCliente'])->name('BuscarCliente');
        Route::get('/ImportarClientes', function (){
                return view('clientes.ImportarClientes');
            })->name('ImportarClientes');
        Route::post('/ImportarClientes', [ClienteController::class, 'ImportarClientes'])->name('ImportarClientes');
        Route::get('/ExportarClientes', [ClienteController::class, 'ExportarClientes'])->name('ExportarClientes');
 
        Route::resource('Servicios', ServicioController::class);
        Route::get('BuscarServicio',  [ServicioController::class, 'BuscarServicio'])->name('BuscarServicio');


        //AK ESTOY TRABAJANDO 
        Route::get('ClientesServicios',  [ClienteServicioController::class, 'index'])->name('ClientesServicios');
        Route::get('ServiciosAgregarCliente/{Servicio}', [ClienteServicioController::class, 'agregarCliente'])->name('ServiciosAgregarCliente'); 
        Route::get('agregarClienteAServicio', [ClienteServicioController::class, 'agregarClienteAServicio'])->name('agregarClienteAServicio'); 
        Route::get('quitarClienteAServicio', [ClienteServicioController::class, 'quitarClienteAServicio'])->name('quitarClienteAServicio'); 

        //RUTAS PAGAR SERVICIOS
        Route::get('ServiciosImpagos', [ServicioPagarController::class, 'ServiciosImpagos'])->name('ServiciosImpagos'); 
        Route::get('ServiciosPagos', [ServicioPagarController::class, 'ServiciosPagos'])->name('ServiciosPagos'); 
        Route::get('ServicioPagarBuscarCliente/{estado?}', [ServicioPagarController::class, 'ServicioPagarBuscarCliente'])->name('ServicioPagarBuscarCliente');
        Route::get('PagarServicio/{idServicioPagar}/{importe}', [ServicioPagarController::class, 'PagarServicio'])->name('PagarServicio');  
        Route::post('ConfirmarPago', [ServicioPagarController::class, 'ConfirmarPago'])->name('ConfirmarPago');
        Route::get('NuevoCobro', [ServicioPagarController::class, 'NuevoCobro'])->name('NuevoCobro'); 
        Route::post('AgregarNuevoCobro', [ServicioPagarController::class, 'AgregarNuevoCobro'])->name('AgregarNuevoCobro');


        //CORREOS 
        Route::get('NotificacionNuevoServicio/{idServicioPagar}', [EnviarCorreoController::class, 'NotificacionNuevoServicio'])->name('NotificacionNuevoServicio');
        Route::get('NotificacionTodosServiciosImpagos', [EnviarCorreoController::class, 'NotificacionTodosServiciosImpagos'])->name('NotificacionTodosServiciosImpagos');

        //RUTAS PARA LOS PAGOS 
        Route::get('Pagos', [PagosController::class, 'index'])->name('Pagos');  
        Route::get('PagosVer/{idServicioPagar}', [PagosController::class, 'PagosVer'])->name('PagosVer');  
        Route::get('PagoPDF/{idServicioPagar}', [PagosController::class, 'pagoPDF'])->name('PagoPDF');

        //RUTAS PARA LOS ADMIN DE RECIBOS 
        Route::post('/subirArchivoRecibos',[ReciboSueldoController::class, 'subirArchivoRecibos'])->name('subirArchivoRecibos'); 
        
        Route::get('/subirRecibos',function(){
            return view('reciboSueldo.subirRecibos')->render();
        })->name('subirRecibos'); 

        //ruta para los formatos de registro de recibos 
        Route::get('formatoRegistro/Create',function(){
            return view('reciboSueldo.crearRegistro')->render();
        })->name('formatoRegistroCreate'); 

        Route::get('formatoRegistro/Update/{id}', [FormatoRegistroReciboController::class, 'update'])->name('formatoRegistroUpdate');
        Route::post('formatoRegistro/UpdateId/{id}', [FormatoRegistroReciboController::class, 'updateId'])->name('formatoRegistroUpdateId');
       
        Route::post('formatoRegistro/Serch', [FormatoRegistroReciboController::class, 'serch'])->name('formatoRegistroSerch');
        Route::post('formatoRegistro/Store', [FormatoRegistroReciboController::class, 'store'])->name('formatoRegistroStore');
        Route::get('formatoRegistro', [FormatoRegistroReciboController::class, 'index'])->name('formatoRegistro');


    });

    // Route::get('/usuarios', [UserController::class, 'todosUsuarios'])->name('usuarios');
    Route::post('/reciboSueldo',[ReciboSueldoController::class, 'todos'])->name('reciboSueldo'); 
    Route::get('/reciboSueldo',[ReciboSueldoController::class, 'todos'])->name('reciboSueldo'); 
    Route::get('/imprimirRecibo/{idRecibo}',[ReciboSueldoController::class, 'imprimirRecibo'])->name('imprimirRecibo'); 

    Route::get('/servicios', [PanelController::class, 'index'])->name('servicios'); 
    //ruta que es creada por el error de la ruta PANEL VIEJA
    Route::get('/panel', [PanelController::class, 'index'])->name('panel'); 


    // Route::view('/miPerfil', 'usuarios.miPerfil')->name('panel');
    Route::get('/miPerfil', [UserController::class, 'miPerfil'])->name('miPerfil'); 

   
});


Route::get('/login', function () {

    if (Auth::check()) {

        // return Response::json([
        //     'id' => Auth::user()->id,
        //     'name' => Auth::user()->name,
        //     'role' => 'user',
        //     'isNew' => \Session::get('isNew', 0)
        // ]);

        // The user is logged in...
        return redirect('servicios');
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




// RUTAS DE PRUEBA

Route::get('/pruebaJob/log', function () {
    
    $filePath = '../storage/logs/laravel.log';
    $fileContent = file_get_contents($filePath);
    
    echo $fileContent;

})->name('pruebaJobVerLog');


Route::get('/pruebaJob/{mensaje}', function ($mensaje) {
    
    TutorialJob::dispatch($mensaje);

})->name('pruebaJob');

// llservicios/routes/web.php
// llservicios/storage/logs/laravel.log
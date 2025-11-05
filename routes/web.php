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
use App\Http\Controllers\ExpenseController;

// JOBS
use App\Jobs\TutorialJob;

//logs
// use Illuminate\Support\Facades\Log;


// Rutas para MercadoPago
use App\Http\Controllers\MercadoPago\MercadoPagoController;
use App\Http\Controllers\MercadoPago\PaymentFormController;
use App\Http\Controllers\MercadoPago\MercadoPagoWebhookController;




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

            //RUTAS DE LA GRILLA controlador viejo sin livewire 
            //Route::get('/Grilla', [GrillaController::class, 'index'])->name('Grilla');
            //Route::get('/GrillaBuscarCliente', [GrillaController::class, 'GrillaBuscarCliente'])->name('GrillaBuscarCliente');

            // Ruta para el componente Livewire GrillaDos
            Route::get('/Grilla', \App\Livewire\GrillaDos::class)->name('Grilla');

            //Ver cliente Livewire
            Route::get('/VerCliente', \App\Livewire\VerCliente\VerCliente::class)->name('VerCliente');

            //Detalle de cliente con servicios vinculados
            Route::get('/DetalleCliente/{clienteId}', \App\Livewire\DetalleCliente::class)->name('DetalleCliente');

            // Importar clientes desde CSV (Livewire)
            Route::get('/ImportarClientesCSV', \App\Livewire\ImportarCliente::class)->name('ImportarClientesCSV');

            // Gestión de QR MercadoPago (Livewire)
            Route::get('/mercadopago/qr-manager', \App\Livewire\MercadoPagoQrManager::class)->name('mercadopago.qr-manager');


            // Pago mediante QR MercadoPago (Livewire) ejemplo
            Route::get('/mercadopago/qrEjemplo', \App\Livewire\QRPayment::class)->name('mercadopago.qrEjemplo');



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
            Route::delete('EliminarServicioImpago/{idServicioPagar}', [ServicioPagarController::class, 'EliminarServicioImpago'])->name('EliminarServicioImpago');

            // dos rutas corregir servicios  impagos 
            
                // Contar servicios impagos
                Route::get('/servicios/impagos/contar', [ServicioPagarController::class, 'ContarServiciosImpagos'])
                    ->name('ContarServiciosImpagos');

                // Eliminar todos los servicios impagos (requiere POST con confirmación)
                Route::post('/servicios/impagos/eliminar-todos', [ServicioPagarController::class, 'EliminarTodosServiciosImpagos'])
                    ->name('EliminarTodosServiciosImpagos');



            //CORREOS 
            Route::get('NotificacionNuevoServicio/{idServicioPagar}', [EnviarCorreoController::class, 'NotificacionNuevoServicio'])->name('NotificacionNuevoServicio');
            Route::get('NotificacionTodosServiciosImpagos', [EnviarCorreoController::class, 'NotificacionTodosServiciosImpagos'])->name('NotificacionTodosServiciosImpagos');
            
            //WHATSAPP
            Route::get('NotificacionWhatsAppTodosServiciosImpagos', [EnviarCorreoController::class, 'NotificacionWhatsAppTodosServiciosImpagos'])->name('NotificacionWhatsAppServiciosImpagos');

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

            //RUTAS PARA GASTOS
            Route::resource('expenses', ExpenseController::class);

            //RUTA PARA CIERRE DE CAJA
            Route::get('/cierre-caja', \App\Livewire\CierreCaja::class)->name('cierre-caja');

    });

    // Route::get('/usuarios', [UserController::class, 'todosUsuarios'])->name('usuarios');
    Route::post('/reciboSueldo',[ReciboSueldoController::class, 'todos'])->name('reciboSueldo'); 
    Route::get('/reciboSueldo',[ReciboSueldoController::class, 'todos'])->name('reciboSueldo'); 
    Route::get('/imprimirRecibo/{idRecibo}',[ReciboSueldoController::class, 'imprimirRecibo'])->name('imprimirRecibo'); 

    Route::get('/panelServicios', [PanelController::class, 'index'])->name('panelServicios'); 
    //ruta que es creada por el error de la ruta PANEL VIEJA
    Route::get('/panel', [PanelController::class, 'index'])->name('panel'); 

    // Ruta para generar pago de servicio
    Route::get('/pago/generar/{servicioPagar}', [PagosController::class, 'generarPago'])->name('pago.generar');
    
    // Rutas de callback de MercadoPago
    Route::get('/pago/success/{servicioPagar}', [PagosController::class, 'pagoSuccess'])->name('pago.success');
    Route::get('/pago/failure/{servicioPagar}', [PagosController::class, 'pagoFailure'])->name('pago.failure');
    Route::get('/pago/pending/{servicioPagar}', [PagosController::class, 'pagoPending'])->name('pago.pending');

    // Route::view('/miPerfil', 'usuarios.miPerfil')->name('panel');
    Route::get('/miPerfil', [UserController::class, 'miPerfil'])->name('miPerfil'); 


    // Rutas de MercadoPago
    Route::prefix('mercadopago')->group(function () {
        // Ruta para crear preferencia de pago (API)
        Route::post('/create-preference', [MercadoPagoController::class, 'createPreference'])->name('mercadopago.create-preference');
        
        // Formulario de demostración para pagos
        Route::get('/payment-form', [PaymentFormController::class, 'show'])->name('mercadopago.payment-form');
        Route::post('/payment-form', [PaymentFormController::class, 'processPayment'])->name('mercadopago.process-payment');
        
        // URLs de retorno después del pago
        Route::get('/success', [MercadoPagoController::class, 'success'])->name('mercadopago.success');
        Route::get('/pending', [MercadoPagoController::class, 'pending'])->name('mercadopago.pending');
        Route::get('/failure', [MercadoPagoController::class, 'failure'])->name('mercadopago.failure');
        
        // Ruta para obtener información de pago usando el nuevo servicio API
        Route::get('/payment-info/{paymentId}', [PagosController::class, 'obtenerInfoPago'])->name('mercadopago.payment-info');
        

    });

   
});

// Webhook para notificaciones de MercadoPago (sin middleware auth)
Route::post('/mercadopago/webhook', [MercadoPagoWebhookController::class, 'handleNotification'])
    ->name('mercadopago.webhook');

// ============================================================
// DOCUMENTACIÓN DE LA API (Vista HTML)
// ============================================================
Route::get('/api-docs', function () {
    return view('api-docs');
})->name('api.docs.view');

Route::get('/login', function () {

    if (Auth::check()) {

        // return Response::json([
        //     'id' => Auth::user()->id,
        //     'name' => Auth::user()->name,
        //     'role' => 'user',
        //     'isNew' => \Session::get('isNew', 0)
        // ]);

        // The user is logged in...
        return redirect('panelServicios');
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
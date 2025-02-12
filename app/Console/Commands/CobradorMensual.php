<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Events\NuevoServicioPagarEvent;

class CobradorMensual extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cobrador-mensual';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $fechaHoy = date('y-m-d H:i:s');
        $datos = DB::select("SELECT a.id as idServicio , DATEDIFF(a.vencimiento, '$fechaHoy') AS diasRestantes, DATEDIFF(a.vencimiento, a.created_at) AS diasCreadoVencimiento, SEC_TO_TIME(TIME_TO_SEC(a.vencimiento) - TIME_TO_SEC(a.created_at)) AS diferencia_tiempo ,
                                a.cliente_id as clienteId, a.servicio_id as servicioId, b.precio as precio, a.cantidad as cantidad
                                FROM cliente_servicio a , servicios b WHERE a.servicio_id = b.id and b.tiempo='mes' and a.vencimiento >= '$fechaHoy'");

        foreach ($datos as $key => $value) {

            // DB::insert('INSERT INTO `servicio_pagar`(`cliente_id`, `servicio_id`, `precio`, `estado`, `created_at`, `updated_at`, `cantidad`) 
            //             VALUES (?,?,?,?,?,?,?)', 
            //                     [$value->clienteId,
            //                     $value->servicioId,
            //                     round($value->precio * $value->cantidad, 2),
            //                     'impago',
            //                     $fechaHoy,
            //                     $fechaHoy,
            //                     $value->cantidad]);

            $id = DB::table('servicio_pagar')->insertGetId([
                'cliente_id' => $value->clienteId,
                'servicio_id' => $value->servicioId,
                'precio' => $value->precio,
                'estado' => 'impago',
                'created_at' => $fechaHoy,
                'updated_at' => $fechaHoy,
                'cantidad' => $value->cantidad,
            ]);

            // NuevoServicioPagarEvent::dispatch($id);
                            
        }

        
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CobradorEjemploMinuto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cobrador-ejemplo-minuto';

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
        $prueba = DB::select("SELECT a.id as idServicio , DATEDIFF(a.vencimiento, '$fechaHoy') AS diasRestantes, DATEDIFF(a.vencimiento, a.created_at) AS diasCreadoVencimiento, SEC_TO_TIME(TIME_TO_SEC(a.vencimiento) - TIME_TO_SEC(a.created_at)) AS diferencia_tiempo ,
                                a.cliente_id as clienteId, a.servicio_id as servicioId, b.precio as precio 
                                FROM cliente_servicio a , servicios b WHERE a.servicio_id = b.id and b.tiempo='mes' and a.vencimiento >= '$fechaHoy'");

        foreach ($prueba as $key => $value) {

        DB::insert('INSERT INTO `servicio_pagar`(`cliente_id`, `servicio_id`, `precio`, `estado`, `created_at`, `updated_at`) 
                    VALUES (?,?,?,?,?,?)', 
                            [$value->clienteId,
                            $value->servicioId,
                            $value->precio,
                            'impago',
                            $fechaHoy,
                            $fechaHoy]);
        }
        
    }
}

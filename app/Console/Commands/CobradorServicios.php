<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Storage;


class CobradorServicios extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cobrador-servicios';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Este es el cobrador por hora';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $datos = DB::select('SELECT * FROM `cliente_servicio` ORDER BY `id` DESC');


        $rutaArchivo = 'prueba.txt';
        $texto = json_encode($datos);

        if (Storage::exists($rutaArchivo)) {
            // El archivo existe
            echo "El archivo existe.";

                    //EDITANDO EL ARCHIVO

        $contenidoActual = Storage::get($rutaArchivo);
        $contenidoEditado = $contenidoActual . "\n" . $texto;
        Storage::put($rutaArchivo, $contenidoEditado);

        } else {
            // El archivo no existe
            echo "El archivo no existe.";


            $contenido = "Contenido del archivo";

            Storage::disk('local')->put($rutaArchivo,$texto);

        }









    }
}



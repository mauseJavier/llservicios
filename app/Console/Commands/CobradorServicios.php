<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;



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


        // Log::info('Nuevo servicio por minuto en Local '); 


        

    }
}



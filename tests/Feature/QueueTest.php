<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\Fixtures\MarcarColaJob;
use Tests\TestCase;

class QueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_job_se_encola_y_procesa_en_la_cola_de_notificaciones(): void
    {
        config(['queue.default' => 'database']);

        MarcarColaJob::dispatch('job-procesado')->onQueue('notificaciones');

        $this->assertDatabaseHas('jobs', [
            'queue' => 'notificaciones',
        ]);
        $this->assertNull(Cache::get('job-procesado'));

        Artisan::call('queue:work', [
            '--once' => true,
            '--queue' => 'notificaciones',
            '--tries' => 1,
        ]);

        $this->assertTrue(Cache::get('job-procesado'));
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_un_job_se_encola_en_la_cola_alta(): void
    {
        config(['queue.default' => 'database']);

        MarcarColaJob::dispatch('job-alta')->onQueue('alta');

        $this->assertDatabaseHas('jobs', [
            'queue' => 'alta',
        ]);
    }
}

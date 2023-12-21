<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

use Illuminate\Auth\Events\Login;
use App\Listeners\UserLoggedIn;

use App\Events\PagoServicioEvent;
use App\Listeners\RegistrarPagoListener;

use App\Events\NuevoServicioPagarEvent;
use App\Listeners\NuevoServicioPagarListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        Login::class => [
            UserLoggedIn::class,
        ],
        PagoServicioEvent::class => [
            RegistrarPagoListener::class
        ],
        NuevoServicioPagarEvent::class => [
            NuevoServicioPagarListener::class
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

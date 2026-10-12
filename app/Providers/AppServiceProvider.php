<?php

namespace App\Providers;

use App\Services\WhatsAppService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        View::composer('principal.*', function ($view) {
            $whatsappState = null;

            if (Auth::check()) {
                $empresa = Auth::user()->empresa;

                if ($empresa && $empresa->instanciaWS && $empresa->tokenWS) {
                    $whatsappState = Cache::remember(
                        "whatsapp_state_{$empresa->id}",
                        30,
                        function () use ($empresa) {
                            $service = new WhatsAppService($empresa->instanciaWS, $empresa->tokenWS);
                            $result = $service->getConnectionState($empresa->instanciaWS);

                            return $result['state'];
                        }
                    );
                }
            }

            $view->with('whatsappState', $whatsappState);
        });
    }
}

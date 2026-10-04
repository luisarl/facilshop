<?php

namespace App\Providers;

use App\Models\MonedasModel;
use App\Observers\MonedasObserver;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        MonedasModel::observe(MonedasObserver::class);

        // Registro automático de auditoría para eventos de autenticación
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, function (\Illuminate\Auth\Events\Login $event): void
        {
            try
            {
                \App\Models\AuditoriasModel::create([
                    'id_usuario' => $event->user?->id_usuario,
                    'modulo' => 'AUTH',
                    'accion' => 'LOGIN',
                    'tabla_afectada' => 'usuarios',
                    'id_registro_afectado' => $event->user?->id_usuario,
                    'valores_anteriores' => null,
                    'valores_nuevos' => [
                        'email' => $event->user?->email,
                        'name' => $event->user?->name,
                        'evento' => 'Inicio de sesión exitoso',
                    ],
                    'ip_direccion' => \Illuminate\Support\Facades\Request::ip(),
                    'user_agent' => \Illuminate\Support\Facades\Request::userAgent(),
                    'url' => \Illuminate\Support\Facades\Request::fullUrl(),
                    'created_at' => now(),
                ]);
            }
            catch (\Throwable $e)
            {
                // Evitar interrumpir flujo de autenticación ante errores de auditoría
            }
        });

        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Logout::class, function (\Illuminate\Auth\Events\Logout $event): void
        {
            try
            {
                if ($event->user)
                {
                    \App\Models\AuditoriasModel::create([
                        'id_usuario' => $event->user->id_usuario,
                        'modulo' => 'AUTH',
                        'accion' => 'LOGOUT',
                        'tabla_afectada' => 'usuarios',
                        'id_registro_afectado' => $event->user->id_usuario,
                        'valores_anteriores' => null,
                        'valores_nuevos' => [
                            'email' => $event->user->email,
                            'name' => $event->user->name,
                            'evento' => 'Cierre de sesión de usuario',
                        ],
                        'ip_direccion' => \Illuminate\Support\Facades\Request::ip(),
                        'user_agent' => \Illuminate\Support\Facades\Request::userAgent(),
                        'url' => \Illuminate\Support\Facades\Request::fullUrl(),
                        'created_at' => now(),
                    ]);
                }
            }
            catch (\Throwable $e)
            {
                // Evitar interrumpir flujo de cierre de sesión
            }
        });

        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Failed::class, function (\Illuminate\Auth\Events\Failed $event): void
        {
            try
            {
                \App\Models\AuditoriasModel::create([
                    'id_usuario' => $event->user?->id_usuario,
                    'modulo' => 'AUTH',
                    'accion' => 'LOGIN_FALLIDO',
                    'tabla_afectada' => 'usuarios',
                    'id_registro_afectado' => $event->user?->id_usuario,
                    'valores_anteriores' => null,
                    'valores_nuevos' => [
                        'email_ingresado' => $event->credentials['email'] ?? null,
                        'evento' => 'Intento de autenticación fallido',
                    ],
                    'ip_direccion' => \Illuminate\Support\Facades\Request::ip(),
                    'user_agent' => \Illuminate\Support\Facades\Request::userAgent(),
                    'url' => \Illuminate\Support\Facades\Request::fullUrl(),
                    'created_at' => now(),
                ]);
            }
            catch (\Throwable $e)
            {
                // Evitar interrumpir flujo de autenticación
            }
        });
    }
}

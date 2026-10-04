<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'global_info' => [
                'tasa_bcv' => function (): float
                {
                    try
                    {
                        return (float) (\App\Models\MonedasModel::where('codigo', 'VES')->value('tasa_cambio') ?? 1.0);
                    }
                    catch (\Throwable)
                    {
                        return 1.0;
                    }
                },
                'caja_activa' => function () use ($request): bool
                {
                    try
                    {
                        return $request->user() ? \App\Models\CajaTurnosModel::where('id_usuario', $request->user()->id_usuario)->where('estado', 'ABIERTA')->exists() : false;
                    }
                    catch (\Throwable)
                    {
                        return false;
                    }
                },
            ],
        ];
    }
}

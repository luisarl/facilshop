<?php

namespace App\Observers;

use App\Models\HistoricoTasasCambioModel;
use App\Models\MonedasModel;
use Illuminate\Support\Facades\Auth;

class MonedasObserver
{
    /**
     * Handle the MonedasModel "updated" event.
     */
    public function updated(MonedasModel $moneda): void
    {
        if ($moneda->wasChanged('tasa_cambio'))
        {
            $IdUsuario = Auth::id() ?? 1;
            $TasaAnterior = (float) $moneda->getOriginal('tasa_cambio');
            $TasaNueva = (float) $moneda->tasa_cambio;

            HistoricoTasasCambioModel::create([
                'id_moneda' => $moneda->id_moneda,
                'id_usuario' => $IdUsuario,
                'tasa_anterior' => $TasaAnterior,
                'tasa_nueva' => $TasaNueva,
                'observaciones' => 'Actualización de cotización',
                'created_at' => now(),
            ]);
        }
    }
}

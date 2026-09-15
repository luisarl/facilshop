<?php

namespace App\Services;

use App\Models\HistoricoTasasCambioModel;
use App\Models\MonedasModel;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class MonedasService
{
    public function ListarMonedas(): Collection
    {
        return MonedasModel::where('activo', true)->orderBy('es_principal', 'desc')->get();
    }

    public function ObtenerMonedaPrincipal(): ?MonedasModel
    {
        return MonedasModel::where('es_principal', true)->first();
    }

    public function ActualizarTasa(int $IdMoneda, float $NuevaTasa, ?string $observaciones = null): MonedasModel
    {
        $moneda = MonedasModel::findOrFail($IdMoneda);

        if ($moneda->es_principal && abs($NuevaTasa - 1.0000) > 0.0001)
        {
            throw new Exception('La moneda principal del sistema debe tener siempre una tasa fija de 1.0000.');
        }

        if ($NuevaTasa <= 0)
        {
            throw new Exception('La tasa de cambio debe ser un valor positivo mayor a cero.');
        }

        $moneda->tasa_cambio = $NuevaTasa;
        $moneda->save();

        return $moneda;
    }

    public function ObtenerHistoricoPorMoneda(int $IdMoneda, int $limite = 20)
    {
        return HistoricoTasasCambioModel::where('id_moneda', $IdMoneda)
            ->with(['Usuario:id_usuario,nombre,email', 'Moneda:id_moneda,codigo,nombre,simbolo'])
            ->orderBy('created_at', 'desc')
            ->paginate($limite);
    }

    public function ConvertirMonto(float $monto, int $IdMonedaOrigen, int $IdMonedaDestino): float
    {
        if ($IdMonedaOrigen === $IdMonedaDestino)
        {
            return $monto;
        }

        $MonedaOrigen = MonedasModel::findOrFail($IdMonedaOrigen);
        $MonedaDestino = MonedasModel::findOrFail($IdMonedaDestino);

        // Convertir a moneda base (USD) dividiendo entre la tasa de origen
        $MontoBase = $monto / (float) $MonedaOrigen->tasa_cambio;

        // Convertir de moneda base a la de destino multiplicando por la tasa de destino
        return round($MontoBase * (float) $MonedaDestino->tasa_cambio, 4);
    }
}

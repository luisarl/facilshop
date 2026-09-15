<?php

namespace App\Services;

use App\Models\CasheaConfigModel;
use Carbon\Carbon;

class CasheaService
{
    public function ObtenerConfiguracion(): CasheaConfigModel
    {
        $config = CasheaConfigModel::where('activo', true)->first();

        if (!$config)
        {
            $config = CasheaConfigModel::create([
                'modo_operacion' => 'MANUAL',
                'porcentaje_inicial_defecto' => 40.00,
                'cuotas_defecto' => 3,
                'activo' => true,
            ]);
        }

        return $config;
    }

    public function CalcularPlanCashea(float $montoTotal, ?float $porcentajeInicial = null): array
    {
        $config = $this->ObtenerConfiguracion();
        $porcentaje = $porcentajeInicial !== null ? $porcentajeInicial : (float) $config->porcentaje_inicial_defecto;
        $numCuotas = (int) $config->cuotas_defecto;

        $montoInicial = round(($montoTotal * $porcentaje) / 100, 2);
        $montoFinanciado = round($montoTotal - $montoInicial, 2);
        $montoCuota = $numCuotas > 0 ? round($montoFinanciado / $numCuotas, 2) : 0.00;

        // Generar calendario de fechas estimadas (cada 14 días)
        $cuotas = [];
        $hoy = Carbon::now();

        for ($i = 1; $i <= $numCuotas; $i++)
        {
            $fecha = (clone $hoy)->addDays(14 * $i);
            $cuotas[] = [
                'numero_cuota' => $i,
                'monto' => $montoCuota,
                'fecha_vencimiento' => $fecha->toDateString(),
                'fecha_formateada' => $fecha->format('d/m/Y'),
            ];
        }

        return [
            'monto_total' => $montoTotal,
            'porcentaje_inicial' => $porcentaje,
            'monto_inicial' => $montoInicial,
            'monto_financiado' => $montoFinanciado,
            'numero_cuotas' => $numCuotas,
            'monto_cuota' => $montoCuota,
            'cuotas' => $cuotas,
        ];
    }
}

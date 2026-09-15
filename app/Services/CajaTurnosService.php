<?php

namespace App\Services;

use App\Models\CajaTurnosModel;
use App\Models\PagosVentaModel;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CajaTurnosService
{
    public function ObtenerTurnoActivo(int $IdUsuario): ?CajaTurnosModel
    {
        return CajaTurnosModel::where('id_usuario', $IdUsuario)
            ->where('estado', 'ABIERTA')
            ->first();
    }

    public function AbrirTurno(int $IdUsuario, float $MontoInicial, ?string $observaciones = null): CajaTurnosModel
    {
        $TurnoExistente = $this->ObtenerTurnoActivo($IdUsuario);

        if ($TurnoExistente)
        {
            throw new Exception('Ya existe un turno de caja abierto para este usuario. Debe cerrarlo antes de iniciar uno nuevo.');
        }

        if ($MontoInicial < 0)
        {
            throw new Exception('El monto inicial de apertura no puede ser negativo.');
        }

        return CajaTurnosModel::create([
            'id_usuario' => $IdUsuario,
            'monto_inicial' => $MontoInicial,
            'monto_final_teorico' => null,
            'monto_final_declarado' => null,
            'diferencia' => null,
            'estado' => 'ABIERTA',
            'fecha_apertura' => now(),
            'observaciones' => $observaciones,
        ]);
    }

    public function CalcularResumenTurno(int $IdCajaTurno): array
    {
        $turno = CajaTurnosModel::with('Ventas')->findOrFail($IdCajaTurno);

        // Obtener los IDs de las ventas completadas del turno
        $IdsVentas = $turno->Ventas->where('estado', 'COMPLETADA')->pluck('id_venta');

        // Sumar todos los pagos en efectivo realizados en este turno (convertidos a moneda base USD)
        $TotalEfectivoBase = PagosVentaModel::whereIn('id_venta', $IdsVentas)
            ->whereHas('MetodoPago', function ($query)
            {
                $query->where('tipo', 'EFECTIVO');
            })
            ->sum('monto_base');

        // Sumar otros métodos (digitales, tarjetas, transferencias, cashea) en moneda base
        $TotalOtrosMetodosBase = PagosVentaModel::whereIn('id_venta', $IdsVentas)
            ->whereHas('MetodoPago', function ($query)
            {
                $query->where('tipo', '!=', 'EFECTIVO');
            })
            ->sum('monto_base');

        $TotalVentasBase = $turno->Ventas->where('estado', 'COMPLETADA')->sum('total_moneda_base');
        $MontoTeoricoTotal = (float) $turno->monto_inicial + (float) $TotalEfectivoBase;

        return [
            'monto_inicial' => (float) $turno->monto_inicial,
            'total_efectivo_base' => (float) $TotalEfectivoBase,
            'total_otros_base' => (float) $TotalOtrosMetodosBase,
            'total_ventas_base' => (float) $TotalVentasBase,
            'monto_teorico_efectivo' => $MontoTeoricoTotal,
            'total_transacciones' => $IdsVentas->count(),
        ];
    }

    public function CerrarTurno(int $IdCajaTurno, float $MontoDeclarado, ?string $observaciones = null): CajaTurnosModel
    {
        $turno = CajaTurnosModel::findOrFail($IdCajaTurno);

        if ($turno->estado !== 'ABIERTA')
        {
            throw new Exception('El turno seleccionado ya se encuentra cerrado.');
        }

        $resumen = $this->CalcularResumenTurno($IdCajaTurno);
        $MontoTeorico = $resumen['monto_teorico_efectivo'];
        $diferencia = $MontoDeclarado - $MontoTeorico;

        $turno->update([
            'monto_final_teorico' => $MontoTeorico,
            'monto_final_declarado' => $MontoDeclarado,
            'diferencia' => $diferencia,
            'estado' => 'CERRADA',
            'fecha_cierre' => now(),
            'observaciones' => $observaciones ? ($turno->observaciones ? $turno->observaciones . ' | ' . $observaciones : $observaciones) : $turno->observaciones,
        ]);

        return $turno;
    }

    public function HistorialTurnos(?int $IdUsuario = null, int $limite = 15): LengthAwarePaginator
    {
        $query = CajaTurnosModel::with('Usuario:id_usuario,nombre,email')->orderBy('fecha_apertura', 'desc');

        if ($IdUsuario)
        {
            $query->where('id_usuario', $IdUsuario);
        }

        return $query->paginate($limite);
    }
}

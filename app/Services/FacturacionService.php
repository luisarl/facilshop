<?php

namespace App\Services;

use App\Models\ClientesModel;
use App\Models\MonedasModel;
use App\Models\MovimientosInventarioModel;
use App\Models\ProductosModel;
use App\Models\TiposMovimientoInventarioModel;
use App\Models\VentasModel;
use Carbon\Carbon;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FacturacionService
{
    public function ListarVentas(array $filtros = [], int $perPage = 15): array
    {
        $query = VentasModel::with([
            'Cliente',
            'Moneda',
            'Pagos.MetodoPago',
            'Pagos.Moneda',
            'CasheaTransaccion',
            'CajaTurno.Usuario',
            'Detalles.Producto',
        ]);

        if (!empty($filtros['buscar']))
        {
            $termino = trim($filtros['buscar']);
            $query->where(function ($q) use ($termino)
            {
                $q->where('numero_comprobante', 'LIKE', "%{$termino}%")
                    ->orWhereHas('Cliente', function ($sub) use ($termino)
                    {
                        $sub->where('nombre', 'LIKE', "%{$termino}%")
                            ->orWhere('identificacion', 'LIKE', "%{$termino}%");
                    });
            });
        }

        if (!empty($filtros['tipo_comprobante']))
        {
            $query->where('tipo_comprobante', $filtros['tipo_comprobante']);
        }

        if (!empty($filtros['estado']))
        {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['fecha_desde']))
        {
            $query->whereDate('created_at', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta']))
        {
            $query->whereDate('created_at', '<=', $filtros['fecha_hasta']);
        }

        if (!empty($filtros['solo_cashea']) && $filtros['solo_cashea'] === 'true')
        {
            $query->has('CasheaTransaccion');
        }

        $query->orderBy('id_venta', 'desc');

        $ventasPaginadas = $query->paginate($perPage)->withQueryString();

        $metricas = $this->ObtenerMetricasFacturacion();

        return [
            'ventas' => $ventasPaginadas,
            'metricas' => $metricas,
        ];
    }

    public function ObtenerMetricasFacturacion(): array
    {
        $hoy = Carbon::today();

        $MonedaVes = MonedasModel::where('codigo', 'VES')->first();
        $TasaVes = $MonedaVes ? (float) $MonedaVes->tasa_cambio : 1.0;

        $totalHoyUsd = (float) VentasModel::whereDate('created_at', $hoy)
            ->where('estado', 'COMPLETADA')
            ->sum('total');

        $totalHoyVes = round($totalHoyUsd * $TasaVes, 2);

        $comprobantesHoy = VentasModel::whereDate('created_at', $hoy)
            ->where('estado', 'COMPLETADA')
            ->count();

        $ventasCasheaHoy = VentasModel::whereDate('created_at', $hoy)
            ->where('estado', 'COMPLETADA')
            ->has('CasheaTransaccion')
            ->count();

        $totalCasheaUsd = (float) DB::table('cashea_transacciones')
            ->join('ventas', 'cashea_transacciones.id_venta', '=', 'ventas.id_venta')
            ->whereDate('ventas.created_at', $hoy)
            ->where('ventas.estado', 'COMPLETADA')
            ->sum('cashea_transacciones.monto_financiado');

        $totalAnuladas = VentasModel::whereDate('created_at', $hoy)
            ->where('estado', 'ANULADA')
            ->count();

        return [
            'total_hoy_usd' => round($totalHoyUsd, 2),
            'total_hoy_ves' => $totalHoyVes,
            'comprobantes_hoy' => $comprobantesHoy,
            'ventas_cashea_hoy' => $ventasCasheaHoy,
            'total_cashea_usd' => round($totalCasheaUsd, 2),
            'anuladas_hoy' => $totalAnuladas,
            'tasa_ves' => $TasaVes,
        ];
    }

    public function ObtenerVentaDetallada(int $IdVenta): VentasModel
    {
        return VentasModel::with([
            'Cliente',
            'Moneda',
            'Pagos.MetodoPago.Moneda',
            'Pagos.Moneda',
            'CasheaTransaccion',
            'CajaTurno.Usuario',
            'Detalles.Producto.Unidad',
        ])->findOrFail($IdVenta);
    }

    public function AnularVenta(int $IdVenta, string $motivo, int $IdUsuario): VentasModel
    {
        return DB::transaction(function () use ($IdVenta, $motivo, $IdUsuario)
        {
            $venta = VentasModel::where('id_venta', $IdVenta)
                ->lockForUpdate()
                ->firstOrFail();

            if ($venta->estado === 'ANULADA')
            {
                throw new Exception("La venta {$venta->numero_comprobante} ya se encuentra anulada.");
            }

            // Buscar tipo de movimiento DEVOLUCION o ENTRADA_AJUSTE para revertir stock
            $tipoDevolucion = TiposMovimientoInventarioModel::where('codigo', 'DEVOLUCION')->first();
            if (!$tipoDevolucion)
            {
                $tipoDevolucion = TiposMovimientoInventarioModel::where('codigo', 'ENTRADA_AJUSTE')->firstOrFail();
            }

            // Reintegrar stock por cada detalle
            $detalles = $venta->Detalles()->with('Producto')->get();
            foreach ($detalles as $detalle)
            {
                $producto = ProductosModel::where('id_producto', $detalle->id_producto)
                    ->lockForUpdate()
                    ->firstOrFail();

                $cantidadReintegrar = (int) $detalle->cantidad;
                $StockAnterior = (int) $producto->stock_actual;
                $NuevoStock = $StockAnterior + $cantidadReintegrar;

                $producto->stock_actual = $NuevoStock;
                $producto->save();

                MovimientosInventarioModel::create([
                    'id_producto' => $producto->id_producto,
                    'id_usuario' => $IdUsuario,
                    'id_tipo_movimiento' => $tipoDevolucion->id_tipo_movimiento,
                    'cantidad' => $cantidadReintegrar,
                    'stock_anterior' => $StockAnterior,
                    'nuevo_stock' => $NuevoStock,
                    'motivo' => "Reverso por Anulación de Venta {$venta->numero_comprobante}: {$motivo}",
                    'documento_referencia' => $venta->numero_comprobante,
                ]);
            }

            // Revertir crédito si hubo pagos a crédito
            $pagos = $venta->Pagos()->with('MetodoPago')->get();
            foreach ($pagos as $pago)
            {
                if ($pago->MetodoPago?->EsCredito())
                {
                    $cliente = ClientesModel::where('id_cliente', $venta->id_cliente)
                        ->lockForUpdate()
                        ->first();

                    if ($cliente)
                    {
                        $cliente->saldo_pendiente = max(0, round((float) $cliente->saldo_pendiente - (float) $pago->monto_base, 2));
                        $cliente->save();
                    }
                }
            }

            // Cancelar Cashea si aplica
            if ($venta->CasheaTransaccion)
            {
                $venta->CasheaTransaccion->update([
                    'estado' => 'CANCELADA',
                ]);
            }

            // Marcar venta como ANULADA
            $venta->estado = 'ANULADA';
            $venta->save();

            return $this->ObtenerVentaDetallada($venta->id_venta);
        });
    }
}

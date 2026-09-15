<?php

namespace App\Services;

use App\Models\AuditoriasModel;
use App\Models\CajaTurnosModel;
use App\Models\ClientesModel;
use App\Models\MonedasModel;
use App\Models\PagosVentaModel;
use App\Models\ProductosModel;
use App\Models\VentaDetallesModel;
use App\Models\VentasModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function ObtenerResumenDashboard(): array
    {
        $hoy = Carbon::today();
        $inicioMes = Carbon::now()->startOfMonth();

        // Monedas y tasas
        $MonedaUsd = MonedasModel::where('codigo', 'USD')->first();
        $MonedaVes = MonedasModel::where('codigo', 'VES')->where('activo', true)->first();
        $TasaVes = $MonedaVes ? (float) $MonedaVes->tasa_cambio : 1.0;

        // 1. Métricas de Ventas de Hoy
        $ventasHoyQuery = VentasModel::whereDate('created_at', $hoy)
            ->where('estado', 'COMPLETADA');

        $totalVentasHoyUsd = (float) $ventasHoyQuery->sum('total');
        $totalVentasHoyVes = round($totalVentasHoyUsd * $TasaVes, 2);
        $conteoVentasHoy = $ventasHoyQuery->count();

        // 2. Métricas del Mes
        $ventasMesQuery = VentasModel::where('created_at', '>=', $inicioMes)
            ->where('estado', 'COMPLETADA');

        $totalVentasMesUsd = (float) $ventasMesQuery->sum('total');
        $totalVentasMesVes = round($totalVentasMesUsd * $TasaVes, 2);
        $conteoVentasMes = $ventasMesQuery->count();

        $ticketPromedioUsd = $conteoVentasMes > 0 ? round($totalVentasMesUsd / $conteoVentasMes, 2) : 0.00;

        // 3. Cartera de Crédito y Cuentas por Cobrar
        $totalCarteraUsd = (float) ClientesModel::sum('saldo_pendiente');
        $totalCarteraVes = round($totalCarteraUsd * $TasaVes, 2);
        $clientesConDeuda = ClientesModel::where('saldo_pendiente', '>', 0)->count();

        // 4. Alertas de Inventario
        $productosStockBajo = ProductosModel::with('Unidad')
            ->where('activo', true)
            ->whereRaw('stock_actual <= stock_minimo')
            ->orderBy('stock_actual', 'asc')
            ->take(5)
            ->get();

        $conteoStockBajo = ProductosModel::where('activo', true)
            ->whereRaw('stock_actual <= stock_minimo')
            ->count();

        // 5. Desglose de Ventas por Método de Pago (Mes Actual)
        $ventasPorMetodo = PagosVentaModel::join('metodos_pago', 'pagos_venta.id_metodo_pago', '=', 'metodos_pago.id_metodo_pago')
            ->join('ventas', 'pagos_venta.id_venta', '=', 'ventas.id_venta')
            ->where('ventas.created_at', '>=', $inicioMes)
            ->where('ventas.estado', 'COMPLETADA')
            ->select(
                'metodos_pago.nombre as metodo_nombre',
                'metodos_pago.tipo as metodo_tipo',
                DB::raw('SUM(pagos_venta.monto_base) as total_usd'),
                DB::raw('COUNT(pagos_venta.id_pago_venta) as transacciones')
            )
            ->groupBy('metodos_pago.nombre', 'metodos_pago.tipo')
            ->orderBy('total_usd', 'desc')
            ->get()
            ->map(function ($item) use ($totalVentasMesUsd)
            {
                $total = (float) $item->total_usd;
                $porcentaje = $totalVentasMesUsd > 0 ? round(($total / $totalVentasMesUsd) * 100, 1) : 0;
                $item->total_usd = $total;
                $item->porcentaje = $porcentaje;
                return $item;
            });

        // 6. Top 5 Productos Más Vendidos
        $topProductos = VentaDetallesModel::join('productos', 'venta_detalles.id_producto', '=', 'productos.id_producto')
            ->join('ventas', 'venta_detalles.id_venta', '=', 'ventas.id_venta')
            ->where('ventas.created_at', '>=', $inicioMes)
            ->where('ventas.estado', 'COMPLETADA')
            ->select(
                'productos.id_producto',
                'productos.nombre',
                'productos.sku',
                DB::raw('SUM(venta_detalles.cantidad) as total_unidades'),
                DB::raw('SUM(venta_detalles.subtotal) as total_facturado')
            )
            ->groupBy('productos.id_producto', 'productos.nombre', 'productos.sku')
            ->orderBy('total_unidades', 'desc')
            ->take(5)
            ->get();

        // 7. Últimas 5 Ventas
        $ultimasVentas = VentasModel::with(['Cliente', 'Moneda'])
            ->orderBy('id_venta', 'desc')
            ->take(5)
            ->get();

        // 8. Últimas 5 Pistas de Auditoría
        $ultimasAuditorias = AuditoriasModel::with('Usuario')
            ->orderBy('id_auditoria', 'desc')
            ->take(5)
            ->get();

        // 9. Estado de Caja del Día
        $turnoAbierto = CajaTurnosModel::with('Usuario')
            ->where('estado', 'ABIERTA')
            ->latest('id_caja_turno')
            ->first();

        return [
            'kpis' => [
                'ventas_hoy_usd' => $totalVentasHoyUsd,
                'ventas_hoy_ves' => $totalVentasHoyVes,
                'conteo_ventas_hoy' => $conteoVentasHoy,
                'ventas_mes_usd' => $totalVentasMesUsd,
                'ventas_mes_ves' => $totalVentasMesVes,
                'conteo_ventas_mes' => $conteoVentasMes,
                'ticket_promedio_usd' => $ticketPromedioUsd,
                'total_cartera_usd' => $totalCarteraUsd,
                'total_cartera_ves' => $totalCarteraVes,
                'clientes_con_deuda' => $clientesConDeuda,
                'conteo_stock_bajo' => $conteoStockBajo,
                'tasa_ves' => $TasaVes,
            ],
            'productos_stock_bajo' => $productosStockBajo,
            'ventas_por_metodo' => $ventasPorMetodo,
            'top_productos' => $topProductos,
            'ultimas_ventas' => $ultimasVentas,
            'ultimas_auditorias' => $ultimasAuditorias,
            'turno_activo' => $turnoAbierto,
        ];
    }
}

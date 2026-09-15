<?php

namespace App\Services;

use App\Models\CajaTurnosModel;
use App\Models\CasheaTransaccionesModel;
use App\Models\ClasificacionProductosModel;
use App\Models\ClientesModel;
use App\Models\MetodosPagoModel;
use App\Models\MonedasModel;
use App\Models\MovimientosInventarioModel;
use App\Models\PagosVentaModel;
use App\Models\ProductosModel;
use App\Models\TiposMovimientoInventarioModel;
use App\Models\VentaDetallesModel;
use App\Models\VentasModel;
use Exception;
use Illuminate\Support\Facades\DB;

class PosService
{
    public function __construct(
        protected CasheaService $casheaService
    )
    {
    }

    public function ObtenerDatosInicialesPOS(int $IdUsuario): array
    {
        $TurnoActivo = CajaTurnosModel::where('id_usuario', $IdUsuario)
            ->where('estado', 'ABIERTA')
            ->first();

        $MonedaUsd = MonedasModel::where('codigo', 'USD')->first();
        $MonedaVes = MonedasModel::where('codigo', 'VES')->where('activo', true)->first();
        $TasaVes = $MonedaVes ? (float) $MonedaVes->tasa_cambio : 1.0;

        $monedas = MonedasModel::where('activo', true)->get();
        $metodosPago = MetodosPagoModel::with('Moneda')->where('activo', true)->get();
        $clientes = ClientesModel::orderBy('nombre')->get();
        $categorias = ClasificacionProductosModel::where('activo', true)->orderBy('nombre')->get();

        $productos = ProductosModel::with(['Unidad', 'UnidadSecundaria', 'Marca', 'Categoria'])
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(function ($p) use ($TasaVes)
            {
                $p->precio_venta_ves = round((float) $p->precio_venta * $TasaVes, 2);
                $p->es_stock_bajo = $p->stock_actual <= $p->stock_minimo;
                return $p;
            });

        $configCashea = $this->casheaService->ObtenerConfiguracion();

        return [
            'turno_activo' => $TurnoActivo,
            'tiene_turno_abierto' => (bool) $TurnoActivo,
            'moneda_base' => $MonedaUsd,
            'tasa_ves' => $TasaVes,
            'monedas' => $monedas,
            'metodos_pago' => $metodosPago,
            'clientes' => $clientes,
            'categorias' => $categorias,
            'productos' => $productos,
            'config_cashea' => $configCashea,
        ];
    }

    public function GenerarNumeroComprobante(string $tipoComprobante = 'TICKET'): string
    {
        $year = date('Y');
        $prefijo = match ($tipoComprobante)
        {
            'FACTURA' => 'FAC',
            'BOLETA' => 'BOL',
            default => 'TKT',
        };

        $UltimaVenta = VentasModel::where('tipo_comprobante', $tipoComprobante)
            ->whereYear('created_at', $year)
            ->orderBy('id_venta', 'desc')
            ->first();

        $secuencia = 1;
        if ($UltimaVenta && preg_match('/' . $prefijo . '-' . $year . '-(\d+)/', $UltimaVenta->numero_comprobante, $matches))
        {
            $secuencia = (int) $matches[1] + 1;
        }

        return sprintf('%s-%s-%05d', $prefijo, $year, $secuencia);
    }

    public function ProcesarVenta(array $datos, int $IdUsuario): VentasModel
    {
        return DB::transaction(function () use ($datos, $IdUsuario)
        {
            $TurnoActivo = CajaTurnosModel::where('id_usuario', $IdUsuario)
                ->where('estado', 'ABIERTA')
                ->lockForUpdate()
                ->first();

            if (!$TurnoActivo)
            {
                throw new Exception('No hay un turno de caja abierto para este usuario. Debe abrir caja antes de facturar.');
            }

            if (empty($datos['detalles']) || !is_array($datos['detalles']))
            {
                throw new Exception('El carrito de compras no contiene productos.');
            }

            if (empty($datos['pagos']) || !is_array($datos['pagos']))
            {
                throw new Exception('Debe registrar al menos un método de pago.');
            }

            $MonedaPrincipal = MonedasModel::where('es_principal', true)->firstOrFail();
            $MonedaVes = MonedasModel::where('codigo', 'VES')->first();
            $TasaBcv = $MonedaVes ? (float) $MonedaVes->tasa_cambio : 1.0;

            $TipoComprobante = $datos['tipo_comprobante'] ?? 'TICKET';
            $NumeroComprobante = $this->GenerarNumeroComprobante($TipoComprobante);

            $TipoSalidaVenta = TiposMovimientoInventarioModel::where('codigo', 'SALIDA_VENTA')->firstOrFail();

            // Calcular totales de líneas y verificar stock
            $SubTotal = 0.0;
            $DescuentoTotal = 0.0;
            $LineasProcesadas = [];

            foreach ($datos['detalles'] as $detalle)
            {
                $producto = ProductosModel::where('id_producto', $detalle['id_producto'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $cantidad = (float) $detalle['cantidad'];
                if ($cantidad <= 0)
                {
                    throw new Exception("La cantidad para '{$producto->nombre}' debe ser mayor a cero.");
                }

                $IdUnidad = (int) ($detalle['id_unidad'] ?? $producto->id_unidad);
                $FactorEquivalencia = 1.0;

                if ($IdUnidad === (int) $producto->id_unidad)
                {
                    $FactorEquivalencia = (float) $producto->equivalencia_unidad;
                }
                elseif ($producto->id_unidad_secundaria && $IdUnidad === (int) $producto->id_unidad_secundaria)
                {
                    $FactorEquivalencia = (float) $producto->equivalencia_unidad_secundaria;
                }

                $CantidadBase = (int) round($cantidad * $FactorEquivalencia);
                if ($CantidadBase <= 0)
                {
                    $CantidadBase = 1;
                }

                if ($producto->stock_actual < $CantidadBase)
                {
                    throw new Exception("Stock insuficiente para '{$producto->nombre}'. Disponible: {$producto->stock_actual}, Solicitado: {$CantidadBase}.");
                }

                $PrecioUnitario = (float) $detalle['precio_unitario'];
                $descuentoLinea = isset($detalle['descuento']) ? (float) $detalle['descuento'] : 0.0;
                $subtotalLinea = round(($cantidad * $PrecioUnitario) - $descuentoLinea, 2);

                $SubTotal += round($cantidad * $PrecioUnitario, 2);
                $DescuentoTotal += $descuentoLinea;

                $LineasProcesadas[] = [
                    'producto' => $producto,
                    'cantidad' => (int) round($cantidad),
                    'cantidad_base' => $CantidadBase,
                    'precio_unitario' => $PrecioUnitario,
                    'descuento' => $descuentoLinea,
                    'subtotal' => $subtotalLinea,
                ];
            }

            $TotalVenta = round($SubTotal - $DescuentoTotal, 2);
            if ($TotalVenta < 0)
            {
                $TotalVenta = 0.00;
            }

            // Validar que los pagos cubran el total
            $TotalPagadoBase = 0.0;
            foreach ($datos['pagos'] as $pago)
            {
                $TotalPagadoBase += (float) $pago['monto_base'];
            }

            $TotalPagadoBase = round($TotalPagadoBase, 2);
            if ($TotalPagadoBase < $TotalVenta)
            {
                throw new Exception("El monto pagado (\${$TotalPagadoBase}) es inferior al total de la venta (\${$TotalVenta}).");
            }

            // Crear Venta Encabezado
            $IdCliente = $datos['id_cliente'] ?? 1; // 1 = Cliente Mostrador
            $cliente = ClientesModel::findOrFail($IdCliente);

            $venta = VentasModel::create([
                'id_caja_turno' => $TurnoActivo->id_caja_turno,
                'id_cliente' => $cliente->id_cliente,
                'id_moneda' => $MonedaPrincipal->id_moneda,
                'tasa_cambio' => $TasaBcv,
                'numero_comprobante' => $NumeroComprobante,
                'tipo_comprobante' => $TipoComprobante,
                'subtotal' => $SubTotal,
                'descuento_total' => $DescuentoTotal,
                'impuesto' => 0.00,
                'total' => $TotalVenta,
                'total_moneda_base' => $TotalVenta,
                'estado' => 'COMPLETADA',
            ]);

            // Guardar detalles de venta y deducir inventario
            foreach ($LineasProcesadas as $linea)
            {
                $producto = $linea['producto'];
                $StockAnterior = (int) $producto->stock_actual;
                $NuevoStock = $StockAnterior - $linea['cantidad_base'];

                $producto->stock_actual = $NuevoStock;
                $producto->save();

                VentaDetallesModel::create([
                    'id_venta' => $venta->id_venta,
                    'id_producto' => $producto->id_producto,
                    'cantidad' => $linea['cantidad'],
                    'precio_unitario' => $linea['precio_unitario'],
                    'descuento' => $linea['descuento'],
                    'subtotal' => $linea['subtotal'],
                    'created_at' => now(),
                ]);

                MovimientosInventarioModel::create([
                    'id_producto' => $producto->id_producto,
                    'id_usuario' => $IdUsuario,
                    'id_tipo_movimiento' => $TipoSalidaVenta->id_tipo_movimiento,
                    'cantidad' => $linea['cantidad_base'],
                    'stock_anterior' => $StockAnterior,
                    'nuevo_stock' => $NuevoStock,
                    'motivo' => "Venta POS {$NumeroComprobante}",
                    'documento_referencia' => $NumeroComprobante,
                ]);
            }

            // Registrar Pagos
            foreach ($datos['pagos'] as $pago)
            {
                $metodo = MetodosPagoModel::findOrFail($pago['id_metodo_pago']);

                PagosVentaModel::create([
                    'id_venta' => $venta->id_venta,
                    'id_metodo_pago' => $metodo->id_metodo_pago,
                    'id_moneda' => $pago['id_moneda'],
                    'monto' => $pago['monto'],
                    'tasa_cambio' => $pago['tasa_cambio'],
                    'monto_base' => $pago['monto_base'],
                    'referencia' => $pago['referencia'] ?? null,
                    'created_at' => now(),
                ]);

                // Si fue a crédito, actualizar saldo pendiente del cliente
                if ($metodo->EsCredito())
                {
                    $cliente->saldo_pendiente = (float) $cliente->saldo_pendiente + (float) $pago['monto_base'];
                    $cliente->save();
                }
            }

            // Registrar transacción Cashea si aplica
            if (!empty($datos['cashea']))
            {
                $datosCashea = $datos['cashea'];
                $plan = $this->casheaService->CalcularPlanCashea(
                    (float) $datosCashea['monto_total'],
                    isset($datosCashea['porcentaje_inicial']) ? (float) $datosCashea['porcentaje_inicial'] : null
                );

                CasheaTransaccionesModel::create([
                    'id_venta' => $venta->id_venta,
                    'id_cliente' => $cliente->id_cliente,
                    'cedula_cliente' => $datosCashea['cedula_cliente'],
                    'telefono_cliente' => $datosCashea['telefono_cliente'] ?? null,
                    'referencia_cashea' => $datosCashea['referencia_cashea'] ?? ('CSH-' . strtoupper(bin2hex(random_bytes(4)))),
                    'monto_total' => $plan['monto_total'],
                    'porcentaje_inicial' => $plan['porcentaje_inicial'],
                    'monto_inicial' => $plan['monto_inicial'],
                    'monto_financiado' => $plan['monto_financiado'],
                    'numero_cuotas' => $plan['numero_cuotas'],
                    'monto_cuota' => $plan['monto_cuota'],
                    'estado' => 'APROBADA',
                    'modo' => 'MANUAL',
                    'codigo_autorizacion' => $datosCashea['codigo_autorizacion'] ?? null,
                    'payload' => $plan,
                ]);
            }

            return $venta->fresh([
                'Detalles.Producto.Unidad',
                'Pagos.MetodoPago.Moneda',
                'Pagos.Moneda',
                'Cliente',
                'CasheaTransaccion',
                'CajaTurno.Usuario',
            ]);
        });
    }

    public function ObtenerVentaPorId(int $IdVenta): VentasModel
    {
        return VentasModel::with([
            'Detalles.Producto.Unidad',
            'Pagos.MetodoPago.Moneda',
            'Pagos.Moneda',
            'Cliente',
            'CasheaTransaccion',
            'CajaTurno.Usuario',
        ])->findOrFail($IdVenta);
    }
}

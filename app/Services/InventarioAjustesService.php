<?php

namespace App\Services;

use App\Models\InventarioAjusteDetallesModel;
use App\Models\InventarioAjustesModel;
use App\Models\MovimientosInventarioModel;
use App\Models\ProductosModel;
use App\Models\TiposMovimientoInventarioModel;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InventarioAjustesService
{
    public function ListarAjustes(array $filtros = []): LengthAwarePaginator
    {
        $query = InventarioAjustesModel::with([
            'Usuario',
            'TipoMovimiento',
            'Detalles.Producto',
            'Detalles.Unidad'
        ]);

        if (!empty($filtros['buscar']))
        {
            $termino = '%' . $filtros['buscar'] . '%';
            $query->where(function ($subQuery) use ($termino)
            {
                $subQuery->where('codigo_ajuste', 'like', $termino)
                    ->orWhere('motivo', 'like', $termino)
                    ->orWhere('documento_referencia', 'like', $termino);
            });
        }

        if (!empty($filtros['naturaleza']))
        {
            $query->whereHas('TipoMovimiento', function ($subQuery) use ($filtros)
            {
                $subQuery->where('naturaleza', $filtros['naturaleza']);
            });
        }

        if (!empty($filtros['id_tipo_movimiento']))
        {
            $query->where('id_tipo_movimiento', $filtros['id_tipo_movimiento']);
        }

        if (!empty($filtros['fecha_desde']))
        {
            $query->whereDate('fecha_ajuste', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta']))
        {
            $query->whereDate('fecha_ajuste', '<=', $filtros['fecha_hasta']);
        }

        $perPage = $filtros['per_page'] ?? 15;

        return $query->orderBy('id_ajuste', 'desc')->paginate($perPage);
    }

    public function ObtenerTiposPorNaturaleza(string $naturaleza): Collection
    {
        return TiposMovimientoInventarioModel::where('naturaleza', $naturaleza)
            ->where('activo', true)
            ->where('codigo', '!=', 'AJUSTE_CONTEO')
            ->orderBy('nombre', 'asc')
            ->get();
    }

    public function GenerarCodigoAjuste(): string
    {
        $year = date('Y');
        $UltimoAjuste = InventarioAjustesModel::whereYear('created_at', $year)
            ->orderBy('id_ajuste', 'desc')
            ->first();

        $secuencia = 1;
        if ($UltimoAjuste && preg_match('/AJU-' . $year . '-(\d+)/', $UltimoAjuste->codigo_ajuste, $matches))
        {
            $secuencia = (int) $matches[1] + 1;
        }

        return sprintf('AJU-%s-%04d', $year, $secuencia);
    }

    public function RegistrarAjuste(array $datos, int $IdUsuario): InventarioAjustesModel
    {
        return DB::transaction(function () use ($datos, $IdUsuario)
        {
            $TipoMovimiento = TiposMovimientoInventarioModel::findOrFail($datos['id_tipo_movimiento']);

            if (isset($datos['naturaleza']) && $TipoMovimiento->naturaleza !== $datos['naturaleza'])
            {
                throw new Exception("El tipo de movimiento no corresponde con la naturaleza {$datos['naturaleza']}.");
            }

            if (empty($datos['detalles']) || !is_array($datos['detalles']))
            {
                throw new Exception('El ajuste debe contener al menos un producto en el detalle.');
            }

            $CodigoAjuste = $this->GenerarCodigoAjuste();
            $TotalItems = 0;
            $TotalCosto = 0.0;

            $ajuste = InventarioAjustesModel::create([
                'codigo_ajuste' => $CodigoAjuste,
                'id_usuario' => $IdUsuario,
                'id_tipo_movimiento' => $TipoMovimiento->id_tipo_movimiento,
                'motivo' => $datos['motivo'],
                'documento_referencia' => $datos['documento_referencia'] ?? null,
                'estado' => 'APLICADO',
                'fecha_ajuste' => now(),
                'total_items' => 0,
                'total_costo' => 0.00,
            ]);

            foreach ($datos['detalles'] as $detalle)
            {
                $producto = ProductosModel::where('id_producto', $detalle['id_producto'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $cantidad = (float) $detalle['cantidad'];
                if ($cantidad <= 0)
                {
                    throw new Exception("La cantidad para el producto '{$producto->nombre}' debe ser mayor a cero.");
                }

                $IdUnidad = (int) $detalle['id_unidad'];
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

                $StockAnterior = (int) $producto->stock_actual;
                $CostoUnitario = isset($detalle['costo_unitario']) ? (float) $detalle['costo_unitario'] : (float) $producto->precio_costo;
                $CostoTotal = round($CostoUnitario * $cantidad, 2);

                if ($TipoMovimiento->naturaleza === 'SALIDA')
                {
                    if ($StockAnterior < $CantidadBase)
                    {
                        throw new Exception("Stock insuficiente para el producto '{$producto->nombre}'. Existencia actual: {$StockAnterior}, solicitado para salida: {$CantidadBase}.");
                    }
                    $NuevoStock = $StockAnterior - $CantidadBase;
                }
                else
                {
                    $NuevoStock = $StockAnterior + $CantidadBase;
                }

                $producto->stock_actual = $NuevoStock;
                $producto->save();

                InventarioAjusteDetallesModel::create([
                    'id_ajuste' => $ajuste->id_ajuste,
                    'id_producto' => $producto->id_producto,
                    'id_unidad' => $IdUnidad,
                    'cantidad' => $cantidad,
                    'cantidad_base' => $CantidadBase,
                    'costo_unitario' => $CostoUnitario,
                    'costo_total' => $CostoTotal,
                    'stock_anterior' => $StockAnterior,
                    'nuevo_stock' => $NuevoStock,
                    'observaciones' => $detalle['observaciones'] ?? null,
                ]);

                MovimientosInventarioModel::create([
                    'id_producto' => $producto->id_producto,
                    'id_usuario' => $IdUsuario,
                    'id_tipo_movimiento' => $TipoMovimiento->id_tipo_movimiento,
                    'cantidad' => $CantidadBase,
                    'stock_anterior' => $StockAnterior,
                    'nuevo_stock' => $NuevoStock,
                    'motivo' => $datos['motivo'],
                    'documento_referencia' => $CodigoAjuste,
                ]);

                $TotalItems += $CantidadBase;
                $TotalCosto += $CostoTotal;
            }

            $ajuste->update([
                'total_items' => $TotalItems,
                'total_costo' => $TotalCosto,
            ]);

            return $ajuste->fresh([
                'Usuario',
                'TipoMovimiento',
                'Detalles.Producto',
                'Detalles.Unidad'
            ]);
        });
    }

    public function ObtenerAjustePorId(int $IdAjuste): InventarioAjustesModel
    {
        return InventarioAjustesModel::with([
            'Usuario',
            'TipoMovimiento',
            'Detalles.Producto.Marca',
            'Detalles.Producto.Categoria',
            'Detalles.Unidad'
        ])->findOrFail($IdAjuste);
    }
}

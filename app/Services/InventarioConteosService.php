<?php

namespace App\Services;

use App\Models\InventarioConteoDetallesModel;
use App\Models\InventarioConteosModel;
use App\Models\MovimientosInventarioModel;
use App\Models\ProductosModel;
use App\Models\TiposMovimientoInventarioModel;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InventarioConteosService
{
    public function ListarConteos(array $filtros = []): LengthAwarePaginator
    {
        $query = InventarioConteosModel::with(['Usuario', 'Detalles']);

        if (!empty($filtros['buscar']))
        {
            $termino = '%' . $filtros['buscar'] . '%';
            $query->where(function ($subQuery) use ($termino)
            {
                $subQuery->where('codigo_conteo', 'like', $termino)
                    ->orWhere('descripcion', 'like', $termino);
            });
        }

        if (!empty($filtros['estado']))
        {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['fecha_desde']))
        {
            $query->whereDate('fecha_inicio', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta']))
        {
            $query->whereDate('fecha_inicio', '<=', $filtros['fecha_hasta']);
        }

        $perPage = $filtros['per_page'] ?? 15;

        return $query->orderBy('id_conteo', 'desc')->paginate($perPage);
    }

    public function GenerarCodigoConteo(): string
    {
        $year = date('Y');
        $UltimoConteo = InventarioConteosModel::whereYear('created_at', $year)
            ->orderBy('id_conteo', 'desc')
            ->first();

        $secuencia = 1;
        if ($UltimoConteo && preg_match('/CNT-' . $year . '-(\d+)/', $UltimoConteo->codigo_conteo, $matches))
        {
            $secuencia = (int) $matches[1] + 1;
        }

        return sprintf('CNT-%s-%04d', $year, $secuencia);
    }

    public function IniciarConteo(array $datos, int $IdUsuario): InventarioConteosModel
    {
        return DB::transaction(function () use ($datos, $IdUsuario)
        {
            $CodigoConteo = $this->GenerarCodigoConteo();
            $PrecargarStock = !empty($datos['precargar_stock']);

            $conteo = InventarioConteosModel::create([
                'codigo_conteo' => $CodigoConteo,
                'id_usuario' => $IdUsuario,
                'descripcion' => $datos['descripcion'] ?? 'Toma física general de inventario',
                'estado' => 'EN_PROCESO',
                'fecha_inicio' => now(),
                'total_items_contados' => 0,
                'total_diferencia_unidades' => 0,
                'total_diferencia_costo' => 0.00,
            ]);

            $queryProductos = ProductosModel::where('activo', true);

            if (!empty($datos['id_categoria']))
            {
                $queryProductos->where('id_categoria', $datos['id_categoria']);
            }

            if (!empty($datos['id_marca']))
            {
                $queryProductos->where('id_marca', $datos['id_marca']);
            }

            $productos = $queryProductos->orderBy('nombre')->get();

            if ($productos->isEmpty())
            {
                throw new Exception('No se encontraron productos activos para el criterio seleccionado.');
            }

            $TotalItemsContados = 0;
            $TotalDiferenciaUnidades = 0;
            $TotalDiferenciaCosto = 0.0;

            foreach ($productos as $producto)
            {
                $StockTeorico = (int) $producto->stock_actual;
                $StockFisico = $PrecargarStock ? $StockTeorico : 0;
                $diferencia = $StockFisico - $StockTeorico;
                $CostoUnitario = (float) $producto->precio_costo;
                $ValorDiferencia = round($diferencia * $CostoUnitario, 2);

                InventarioConteoDetallesModel::create([
                    'id_conteo' => $conteo->id_conteo,
                    'id_producto' => $producto->id_producto,
                    'stock_teorico' => $StockTeorico,
                    'stock_fisico' => $StockFisico,
                    'diferencia' => $diferencia,
                    'costo_unitario' => $CostoUnitario,
                    'valor_diferencia' => $ValorDiferencia,
                    'observaciones' => null,
                ]);

                $TotalItemsContados += $StockFisico;
                $TotalDiferenciaUnidades += $diferencia;
                $TotalDiferenciaCosto += $ValorDiferencia;
            }

            $conteo->update([
                'total_items_contados' => $TotalItemsContados,
                'total_diferencia_unidades' => $TotalDiferenciaUnidades,
                'total_diferencia_costo' => $TotalDiferenciaCosto,
            ]);

            return $conteo->fresh(['Usuario', 'Detalles.Producto']);
        });
    }

    public function ObtenerConteoPorId(int $IdConteo): InventarioConteosModel
    {
        return InventarioConteosModel::with([
            'Usuario',
            'Detalles.Producto.Marca',
            'Detalles.Producto.Categoria',
            'Detalles.Producto.Unidad'
        ])->findOrFail($IdConteo);
    }

    public function ActualizarItemsConteo(int $IdConteo, array $items): InventarioConteosModel
    {
        return DB::transaction(function () use ($IdConteo, $items)
        {
            $conteo = InventarioConteosModel::findOrFail($IdConteo);

            if ($conteo->estado !== 'EN_PROCESO')
            {
                throw new Exception('Solo se pueden actualizar conteos en estado EN_PROCESO.');
            }

            foreach ($items as $item)
            {
                $detalle = InventarioConteoDetallesModel::where('id_conteo', $IdConteo)
                    ->where('id_conteo_detalle', $item['id_conteo_detalle'])
                    ->firstOrFail();

                $StockFisico = (int) $item['stock_fisico'];
                $diferencia = $StockFisico - (int) $detalle->stock_teorico;
                $ValorDiferencia = round($diferencia * (float) $detalle->costo_unitario, 2);

                $detalle->update([
                    'stock_fisico' => $StockFisico,
                    'diferencia' => $diferencia,
                    'valor_diferencia' => $ValorDiferencia,
                    'observaciones' => $item['observaciones'] ?? $detalle->observaciones,
                ]);
            }

            $detalles = InventarioConteoDetallesModel::where('id_conteo', $IdConteo)->get();
            $TotalItemsContados = $detalles->sum('stock_fisico');
            $TotalDiferenciaUnidades = $detalles->sum('diferencia');
            $TotalDiferenciaCosto = $detalles->sum('valor_diferencia');

            $conteo->update([
                'total_items_contados' => $TotalItemsContados,
                'total_diferencia_unidades' => $TotalDiferenciaUnidades,
                'total_diferencia_costo' => $TotalDiferenciaCosto,
            ]);

            return $conteo->fresh(['Usuario', 'Detalles.Producto']);
        });
    }

    public function AplicarConteo(int $IdConteo, int $IdUsuario): InventarioConteosModel
    {
        return DB::transaction(function () use ($IdConteo, $IdUsuario)
        {
            $conteo = InventarioConteosModel::with('Detalles.Producto')->findOrFail($IdConteo);

            if ($conteo->estado !== 'EN_PROCESO')
            {
                throw new Exception('Solo se pueden aplicar sesiones de conteo que estén EN_PROCESO.');
            }

            $TipoAjusteConteo = TiposMovimientoInventarioModel::where('codigo', 'AJUSTE_CONTEO')->firstOrFail();

            foreach ($conteo->Detalles as $detalle)
            {
                if ($detalle->diferencia !== 0)
                {
                    $producto = ProductosModel::where('id_producto', $detalle->id_producto)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $StockAnterior = (int) $producto->stock_actual;
                    $NuevoStock = (int) $detalle->stock_fisico;

                    $producto->stock_actual = $NuevoStock;
                    $producto->save();

                    MovimientosInventarioModel::create([
                        'id_producto' => $producto->id_producto,
                        'id_usuario' => $IdUsuario,
                        'id_tipo_movimiento' => $TipoAjusteConteo->id_tipo_movimiento,
                        'cantidad' => abs($detalle->diferencia),
                        'stock_anterior' => $StockAnterior,
                        'nuevo_stock' => $NuevoStock,
                        'motivo' => "Conciliación física por conteo {$conteo->codigo_conteo}",
                        'documento_referencia' => $conteo->codigo_conteo,
                    ]);
                }
            }

            $detalles = InventarioConteoDetallesModel::where('id_conteo', $IdConteo)->get();
            $conteo->update([
                'estado' => 'APLICADO',
                'fecha_cierre' => now(),
                'total_items_contados' => $detalles->sum('stock_fisico'),
                'total_diferencia_unidades' => $detalles->sum('diferencia'),
                'total_diferencia_costo' => $detalles->sum('valor_diferencia'),
            ]);

            return $conteo->fresh([
                'Usuario',
                'Detalles.Producto.Marca',
                'Detalles.Producto.Categoria',
                'Detalles.Producto.Unidad'
            ]);
        });
    }

    public function CancelarConteo(int $IdConteo): InventarioConteosModel
    {
        $conteo = InventarioConteosModel::findOrFail($IdConteo);

        if ($conteo->estado === 'APLICADO')
        {
            throw new Exception('No se puede cancelar un conteo que ya ha sido aplicado al inventario.');
        }

        $conteo->update([
            'estado' => 'CANCELADO',
            'fecha_cierre' => now(),
        ]);

        return $conteo;
    }
}

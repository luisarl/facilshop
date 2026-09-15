<?php

namespace App\Services;

use App\Models\ClasificacionProductosModel;
use App\Models\MarcasProductosModel;
use App\Models\MonedasModel;
use App\Models\MovimientosInventarioModel;
use App\Models\ProductosImagenesModel;
use App\Models\ProductosModel;
use App\Models\UnidadesProductosModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InventarioService
{
    public function ListarProductos(array $filtros = []): LengthAwarePaginator
    {
        $query = ProductosModel::with([
            'Marca',
            'Categoria',
            'Unidad',
            'UnidadSecundaria',
            'Imagenes'
        ]);

        if (!empty($filtros['buscar']))
        {
            $termino = '%' . $filtros['buscar'] . '%';
            $query->where(function ($subQuery) use ($termino)
            {
                $subQuery->where('nombre', 'like', $termino)
                    ->orWhere('sku', 'like', $termino)
                    ->orWhere('codigo_barras', 'like', $termino)
                    ->orWhere('modelo', 'like', $termino);
            });
        }

        if (!empty($filtros['id_categoria']))
        {
            $query->where('id_categoria', $filtros['id_categoria']);
        }

        if (!empty($filtros['id_marca']))
        {
            $query->where('id_marca', $filtros['id_marca']);
        }

        if (isset($filtros['bajo_stock']) && $filtros['bajo_stock'] === true)
        {
            $query->whereColumn('stock_actual', '<=', 'stock_minimo');
        }

        if (isset($filtros['activo']))
        {
            $query->where('activo', (bool) $filtros['activo']);
        }

        $perPage = $filtros['per_page'] ?? 15;

        $productos = $query->orderBy('nombre', 'asc')->paginate($perPage);

        $MonedaVes = MonedasModel::where('codigo', 'VES')->where('activo', true)->first();
        $TasaVes = $MonedaVes ? (float) $MonedaVes->tasa_cambio : 1.0;

        $productos->getCollection()->transform(function ($item) use ($TasaVes)
        {
            $item->precio_venta_ves = round((float) $item->precio_venta * $TasaVes, 2);
            $item->precio_costo_ves = round((float) $item->precio_costo * $TasaVes, 2);
            $item->es_stock_bajo = $item->stock_actual <= $item->stock_minimo;
            return $item;
        });

        return $productos;
    }

    public function ObtenerTodosActivos(): array
    {
        $MonedaVes = MonedasModel::where('codigo', 'VES')->where('activo', true)->first();
        $TasaVes = $MonedaVes ? (float) $MonedaVes->tasa_cambio : 1.0;

        return ProductosModel::with(['Unidad', 'UnidadSecundaria', 'Marca', 'Categoria'])
            ->where('activo', true)
            ->orderBy('nombre', 'asc')
            ->get()
            ->map(function ($item) use ($TasaVes)
            {
                $item->precio_venta_ves = round((float) $item->precio_venta * $TasaVes, 2);
                $item->precio_costo_ves = round((float) $item->precio_costo * $TasaVes, 2);
                $item->es_stock_bajo = $item->stock_actual <= $item->stock_minimo;
                return $item;
            })
            ->toArray();
    }

    public function ObtenerCatalogosAuxiliares(): array
    {
        return [
            'categorias' => ClasificacionProductosModel::where('activo', true)->orderBy('nombre')->get(),
            'marcas' => MarcasProductosModel::where('activo', true)->orderBy('nombre')->get(),
            'unidades' => UnidadesProductosModel::where('activo', true)->orderBy('nombre')->get(),
        ];
    }

    public function GuardarProducto(array $datos, ?int $IdProducto = null): ProductosModel
    {
        return DB::transaction(function () use ($datos, $IdProducto)
        {
            if ($IdProducto)
            {
                $producto = ProductosModel::findOrFail($IdProducto);
                $producto->update($datos);
            }
            else
            {
                $producto = ProductosModel::create($datos);
            }

            return $producto->fresh(['Marca', 'Categoria', 'Unidad', 'UnidadSecundaria', 'Imagenes']);
        });
    }

    public function ObtenerProductoPorId(int $IdProducto): ProductosModel
    {
        return ProductosModel::with([
            'Marca',
            'Categoria',
            'Unidad',
            'UnidadSecundaria',
            'Imagenes',
            'MovimientosInventario.TipoMovimiento',
            'MovimientosInventario.Usuario'
        ])->findOrFail($IdProducto);
    }

    public function ObtenerMovimientosProducto(int $IdProducto, int $perPage = 20): LengthAwarePaginator
    {
        return MovimientosInventarioModel::with(['TipoMovimiento', 'Usuario'])
            ->where('id_producto', $IdProducto)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}

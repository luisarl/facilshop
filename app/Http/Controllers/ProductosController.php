<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarProductoRequest;
use App\Models\ProductosModel;
use App\Services\InventarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductosController extends Controller
{
    public function __construct(
        protected InventarioService $inventarioService
    )
    {
    }

    public function Index(Request $request): Response
    {
        $filtros = [
            'buscar' => $request->input('buscar'),
            'id_categoria' => $request->input('id_categoria'),
            'id_marca' => $request->input('id_marca'),
            'bajo_stock' => $request->boolean('bajo_stock'),
            'activo' => $request->has('activo') ? $request->boolean('activo') : null,
        ];

        $productos = $this->inventarioService->ListarProductos($filtros);
        $catalogos = $this->inventarioService->ObtenerCatalogosAuxiliares();

        return Inertia::render('Inventario/Productos/Index', [
            'productos' => $productos,
            'catalogos' => $catalogos,
            'filtros' => $filtros,
        ]);
    }

    public function Store(GuardarProductoRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $this->inventarioService->GuardarProducto($datos);

        return redirect()->route('inventario.productos.index')->with('success', 'Producto registrado exitosamente.');
    }

    public function Update(GuardarProductoRequest $request, int $id_producto): RedirectResponse
    {
        $datos = $request->validated();
        $this->inventarioService->GuardarProducto($datos, $id_producto);

        return redirect()->route('inventario.productos.index')->with('success', 'Producto actualizado exitosamente.');
    }

    public function Movimientos(int $id_producto): JsonResponse
    {
        $movimientos = $this->inventarioService->ObtenerMovimientosProducto($id_producto);
        return response()->json($movimientos);
    }

    public function ApiListar(Request $request): JsonResponse
    {
        $filtros = [
            'buscar' => $request->input('buscar'),
            'id_categoria' => $request->input('id_categoria'),
            'id_marca' => $request->input('id_marca'),
            'bajo_stock' => $request->boolean('bajo_stock'),
        ];

        $productos = $this->inventarioService->ListarProductos($filtros);
        return response()->json($productos);
    }

    public function ApiBuscarPorCodigo(Request $request): JsonResponse
    {
        $codigo = $request->input('codigo');
        if (empty($codigo))
        {
            return response()->json(['error' => 'Código no proporcionado'], 400);
        }

        $producto = ProductosModel::with(['Unidad', 'UnidadSecundaria', 'Marca', 'Categoria'])
            ->where('activo', true)
            ->where(function ($subQuery) use ($codigo)
            {
                $subQuery->where('codigo_barras', $codigo)
                    ->orWhere('sku', $codigo);
            })
            ->first();

        if (!$producto)
        {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }

        return response()->json($producto);
    }
}

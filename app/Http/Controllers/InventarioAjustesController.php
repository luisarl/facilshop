<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrarAjusteRequest;
use App\Services\InventarioAjustesService;
use App\Services\InventarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InventarioAjustesController extends Controller
{
    public function __construct(
        protected InventarioAjustesService $inventarioAjustesService,
        protected InventarioService $inventarioService
    )
    {
    }

    public function Index(Request $request): Response
    {
        $filtros = [
            'buscar' => $request->input('buscar'),
            'naturaleza' => $request->input('naturaleza'),
            'id_tipo_movimiento' => $request->input('id_tipo_movimiento'),
            'fecha_desde' => $request->input('fecha_desde'),
            'fecha_hasta' => $request->input('fecha_hasta'),
        ];

        $ajustes = $this->inventarioAjustesService->ListarAjustes($filtros);
        $TiposEntrada = $this->inventarioAjustesService->ObtenerTiposPorNaturaleza('ENTRADA');
        $TiposSalida = $this->inventarioAjustesService->ObtenerTiposPorNaturaleza('SALIDA');

        return Inertia::render('Inventario/Ajustes/Index', [
            'ajustes' => $ajustes,
            'filtros' => $filtros,
            'tipos_entrada' => $TiposEntrada,
            'tipos_salida' => $TiposSalida,
        ]);
    }

    public function Create(): Response
    {
        $TiposEntrada = $this->inventarioAjustesService->ObtenerTiposPorNaturaleza('ENTRADA');
        $TiposSalida = $this->inventarioAjustesService->ObtenerTiposPorNaturaleza('SALIDA');
        $CodigoSugerido = $this->inventarioAjustesService->GenerarCodigoAjuste();
        $productos = $this->inventarioService->ObtenerTodosActivos();

        return Inertia::render('Inventario/Ajustes/Create', [
            'tipos_entrada' => $TiposEntrada,
            'tipos_salida' => $TiposSalida,
            'codigo_sugerido' => $CodigoSugerido,
            'productos' => $productos,
        ]);
    }

    public function Store(RegistrarAjusteRequest $request): RedirectResponse
    {
        $IdUsuario = Auth::id() ?? 1;
        $ajuste = $this->inventarioAjustesService->RegistrarAjuste($request->validated(), $IdUsuario);

        return redirect()->route('inventario.ajustes.show', $ajuste->id_ajuste)
            ->with('success', "Ajuste {$ajuste->codigo_ajuste} registrado y aplicado correctamente.");
    }

    public function Show(int $id_ajuste): Response
    {
        $ajuste = $this->inventarioAjustesService->ObtenerAjustePorId($id_ajuste);

        return Inertia::render('Inventario/Ajustes/Show', [
            'ajuste' => $ajuste,
        ]);
    }

    public function ApiTipos(string $naturaleza): JsonResponse
    {
        $tipos = $this->inventarioAjustesService->ObtenerTiposPorNaturaleza(strtoupper($naturaleza));
        return response()->json($tipos);
    }

    public function ApiStore(RegistrarAjusteRequest $request): JsonResponse
    {
        $IdUsuario = Auth::id() ?? 1;
        $ajuste = $this->inventarioAjustesService->RegistrarAjuste($request->validated(), $IdUsuario);

        return response()->json([
            'mensaje' => 'Ajuste de inventario aplicado exitosamente',
            'ajuste' => $ajuste,
        ], 201);
    }
}

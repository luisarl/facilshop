<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarDetalleConteoRequest;
use App\Http\Requests\IniciarConteoRequest;
use App\Services\InventarioConteosService;
use App\Services\InventarioService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InventarioConteosController extends Controller
{
    public function __construct(
        protected InventarioConteosService $inventarioConteosService,
        protected InventarioService $inventarioService
    )
    {
    }

    public function Index(Request $request): Response
    {
        $filtros = [
            'buscar' => $request->input('buscar'),
            'estado' => $request->input('estado'),
            'fecha_desde' => $request->input('fecha_desde'),
            'fecha_hasta' => $request->input('fecha_hasta'),
        ];

        $conteos = $this->inventarioConteosService->ListarConteos($filtros);
        $catalogos = $this->inventarioService->ObtenerCatalogosAuxiliares();

        return Inertia::render('Inventario/Conteos/Index', [
            'conteos' => $conteos,
            'filtros' => $filtros,
            'catalogos' => $catalogos,
        ]);
    }

    public function Store(IniciarConteoRequest $request): RedirectResponse
    {
        $IdUsuario = Auth::id() ?? 1;

        try
        {
            $conteo = $this->inventarioConteosService->IniciarConteo($request->validated(), $IdUsuario);
            return redirect()->route('inventario.conteos.worksheet', $conteo->id_conteo)
                ->with('success', "Sesión de conteo {$conteo->codigo_conteo} iniciada. Existencias teóricas congeladas.");
        }
        catch (Exception $e)
        {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function Worksheet(int $id_conteo): Response
    {
        $conteo = $this->inventarioConteosService->ObtenerConteoPorId($id_conteo);

        return Inertia::render('Inventario/Conteos/Worksheet', [
            'conteo' => $conteo,
        ]);
    }

    public function ActualizarDetalles(ActualizarDetalleConteoRequest $request, int $id_conteo): JsonResponse|RedirectResponse
    {
        try
        {
            $conteo = $this->inventarioConteosService->ActualizarItemsConteo($id_conteo, $request->input('items'));

            if ($request->wantsJson())
            {
                return response()->json([
                    'mensaje' => 'Conteo físico actualizado correctamente.',
                    'conteo' => $conteo,
                ]);
            }

            return back()->with('success', 'Conteo físico guardado correctamente.');
        }
        catch (Exception $e)
        {
            if ($request->wantsJson())
            {
                return response()->json(['error' => $e->getMessage()], 422);
            }
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function Aplicar(Request $request, int $id_conteo): RedirectResponse
    {
        $IdUsuario = Auth::id() ?? 1;

        try
        {
            $conteo = $this->inventarioConteosService->AplicarConteo($id_conteo, $IdUsuario);
            return redirect()->route('inventario.conteos.worksheet', $conteo->id_conteo)
                ->with('success', "Conteo {$conteo->codigo_conteo} aplicado. El inventario ha sido conciliado satisfactoriamente.");
        }
        catch (Exception $e)
        {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function Cancelar(Request $request, int $id_conteo): RedirectResponse
    {
        try
        {
            $conteo = $this->inventarioConteosService->CancelarConteo($id_conteo);
            return redirect()->route('inventario.conteos.index')
                ->with('success', "Sesión de conteo {$conteo->codigo_conteo} cancelada.");
        }
        catch (Exception $e)
        {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}

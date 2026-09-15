<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarCategoriaRequest;
use App\Http\Requests\GuardarMarcaRequest;
use App\Http\Requests\GuardarUsuarioRequest;
use App\Services\ConfiguracionService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ConfiguracionController extends Controller
{
    public function __construct(
        protected ConfiguracionService $configuracionService
    )
    {
    }

    public function Index(Request $request): Response
    {
        $TabActivo = $request->input('tab', 'usuarios');
        $datos = $this->configuracionService->ObtenerDatosConfiguracion();

        return Inertia::render('Configuracion/Index', array_merge($datos, [
            'tabActivo' => $TabActivo,
        ]));
    }

    // --- USUARIOS ---
    public function GuardarUsuario(GuardarUsuarioRequest $request, ?int $id_usuario = null): RedirectResponse
    {
        try
        {
            $usuario = $this->configuracionService->GuardarUsuario($request->validated(), $id_usuario);
            $accion = $id_usuario ? 'actualizado' : 'registrado';

            return redirect()->route('configuracion.index', ['tab' => 'usuarios'])
                ->with('success', "Usuario '{$usuario->name}' {$accion} exitosamente.");
        }
        catch (Exception $e)
        {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function AlternarEstadoUsuario(int $id_usuario): RedirectResponse
    {
        $IdUsuarioActual = Auth::id() ?? 0;

        try
        {
            $usuario = $this->configuracionService->AlternarEstadoUsuario($id_usuario, $IdUsuarioActual);
            $EstadoTexto = $usuario->activo ? 'activado' : 'desactivado';

            return redirect()->route('configuracion.index', ['tab' => 'usuarios'])
                ->with('success', "Usuario '{$usuario->name}' {$EstadoTexto} exitosamente.");
        }
        catch (Exception $e)
        {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // --- CATEGORÍAS ---
    public function GuardarCategoria(GuardarCategoriaRequest $request, ?int $id_categoria = null): RedirectResponse
    {
        try
        {
            $categoria = $this->configuracionService->GuardarCategoria($request->validated(), $id_categoria);
            $accion = $id_categoria ? 'actualizada' : 'registrada';

            return redirect()->route('configuracion.index', ['tab' => 'categorias'])
                ->with('success', "Categoría '{$categoria->nombre}' {$accion} exitosamente.");
        }
        catch (Exception $e)
        {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function AlternarEstadoCategoria(int $id_categoria): RedirectResponse
    {
        try
        {
            $categoria = $this->configuracionService->AlternarEstadoCategoria($id_categoria);
            $EstadoTexto = $categoria->activo ? 'activada' : 'desactivada';

            return redirect()->route('configuracion.index', ['tab' => 'categorias'])
                ->with('success', "Categoría '{$categoria->nombre}' {$EstadoTexto} exitosamente.");
        }
        catch (Exception $e)
        {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // --- MARCAS ---
    public function GuardarMarca(GuardarMarcaRequest $request, ?int $id_marca = null): RedirectResponse
    {
        try
        {
            $marca = $this->configuracionService->GuardarMarca($request->validated(), $id_marca);
            $accion = $id_marca ? 'actualizada' : 'registrada';

            return redirect()->route('configuracion.index', ['tab' => 'marcas'])
                ->with('success', "Marca '{$marca->nombre}' {$accion} exitosamente.");
        }
        catch (Exception $e)
        {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function AlternarEstadoMarca(int $id_marca): RedirectResponse
    {
        try
        {
            $marca = $this->configuracionService->AlternarEstadoMarca($id_marca);
            $EstadoTexto = $marca->activo ? 'activada' : 'desactivada';

            return redirect()->route('configuracion.index', ['tab' => 'marcas'])
                ->with('success', "Marca '{$marca->nombre}' {$EstadoTexto} exitosamente.");
        }
        catch (Exception $e)
        {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // --- API REST ---
    public function ApiIndex(): JsonResponse
    {
        $datos = $this->configuracionService->ObtenerDatosConfiguracion();
        return response()->json($datos);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\AbrirCajaTurnoRequest;
use App\Http\Requests\CerrarCajaTurnoRequest;
use App\Services\CajaTurnosService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CajaTurnosController extends Controller
{
    protected CajaTurnosService $CajaTurnosService;

    public function __construct(CajaTurnosService $CajaTurnosService)
    {
        $this->CajaTurnosService = $CajaTurnosService;
    }

    public function index(Request $request): Response|JsonResponse
    {
        $idUsuario = Auth::id();
        $TurnoActivo = $this->CajaTurnosService->ObtenerTurnoActivo($idUsuario);
        $resumen = $TurnoActivo ? $this->CajaTurnosService->CalcularResumenTurno($TurnoActivo->id_caja_turno) : null;
        $historial = $this->CajaTurnosService->HistorialTurnos(null, 10);

        if ($request->expectsJson() && !$request->header('X-Inertia'))
        {
            return response()->json([
                'success' => true,
                'turno_activo' => $TurnoActivo,
                'resumen' => $resumen,
                'historial' => $historial,
            ]);
        }

        return Inertia::render('Caja/Index', [
            'turnoActivo' => $TurnoActivo,
            'resumenTurno' => $resumen,
            'historialTurnos' => $historial,
        ]);
    }

    public function abrirTurno(AbrirCajaTurnoRequest $request): RedirectResponse|JsonResponse
    {
        try
        {
            $idUsuario = Auth::id();
            $MontoInicial = (float) $request->input('monto_inicial');
            $observaciones = $request->input('observaciones');

            $turno = $this->CajaTurnosService->AbrirTurno($idUsuario, $MontoInicial, $observaciones);

            if ($request->expectsJson() && !$request->header('X-Inertia'))
            {
                return response()->json([
                    'success' => true,
                    'message' => 'Turno de caja aperturado correctamente.',
                    'data' => $turno,
                ], 201);
            }

            return redirect()->route('caja.index')->with('success', 'Turno de caja abierto exitosamente con un fondo de $' . number_format($MontoInicial, 2) . ' USD.');
        }
        catch (Exception $e)
        {
            if ($request->expectsJson() && !$request->header('X-Inertia'))
            {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->withErrors(['monto_inicial' => $e->getMessage()]);
        }
    }

    public function cerrarTurno(CerrarCajaTurnoRequest $request, int $id_caja_turno): RedirectResponse|JsonResponse
    {
        try
        {
            $MontoDeclarado = (float) $request->input('monto_final_declarado');
            $observaciones = $request->input('observaciones');

            $turno = $this->CajaTurnosService->CerrarTurno($id_caja_turno, $MontoDeclarado, $observaciones);

            if ($request->expectsJson() && !$request->header('X-Inertia'))
            {
                return response()->json([
                    'success' => true,
                    'message' => 'Turno de caja cerrado exitosamente.',
                    'data' => $turno,
                ]);
            }

            $mensaje = 'Turno de caja cerrado exitosamente. Diferencia calculada: $' . number_format((float) $turno->diferencia, 2) . ' USD.';
            return redirect()->route('caja.index')->with('success', $mensaje);
        }
        catch (Exception $e)
        {
            if ($request->expectsJson() && !$request->header('X-Inertia'))
            {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->withErrors(['monto_final_declarado' => $e->getMessage()]);
        }
    }

    public function current(Request $request): JsonResponse
    {
        $idUsuario = Auth::id();
        $turno = $this->CajaTurnosService->ObtenerTurnoActivo($idUsuario);

        if (!$turno)
        {
            return response()->json([
                'success' => false,
                'message' => 'No hay un turno de caja activo para el usuario.',
                'data' => null,
            ], 404);
        }

        $resumen = $this->CajaTurnosService->CalcularResumenTurno($turno->id_caja_turno);

        return response()->json([
            'success' => true,
            'data' => $turno,
            'resumen' => $resumen,
        ]);
    }
}

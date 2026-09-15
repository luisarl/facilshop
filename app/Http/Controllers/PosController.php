<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcesarVentaRequest;
use App\Services\CasheaService;
use App\Services\PosService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    public function __construct(
        protected PosService $posService,
        protected CasheaService $casheaService
    )
    {
    }

    public function Index(Request $request): Response|RedirectResponse
    {
        $IdUsuario = Auth::id() ?? 1;
        $datosIniciales = $this->posService->ObtenerDatosInicialesPOS($IdUsuario);

        if (!$datosIniciales['tiene_turno_abierto'])
        {
            return redirect()->route('caja.index')->with(
                'warning',
                'Debe abrir un turno de caja con fondo inicial antes de poder acceder al Punto de Venta (POS).'
            );
        }

        return Inertia::render('Pos/Index', $datosIniciales);
    }

    public function Checkout(ProcesarVentaRequest $request): JsonResponse|RedirectResponse
    {
        $IdUsuario = Auth::id() ?? 1;

        try
        {
            $venta = $this->posService->ProcesarVenta($request->validated(), $IdUsuario);

            if ($request->wantsJson())
            {
                return response()->json([
                    'mensaje' => 'Venta procesada con éxito.',
                    'venta' => $venta,
                ], 201);
            }

            return back()->with([
                'success' => "Venta {$venta->numero_comprobante} registrada exitosamente.",
                'venta_reciente' => $venta,
            ]);
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

    public function SimularCashea(Request $request): JsonResponse
    {
        $monto = (float) $request->input('monto', 0);
        $porcentaje = $request->has('porcentaje') ? (float) $request->input('porcentaje') : null;

        $plan = $this->casheaService->CalcularPlanCashea($monto, $porcentaje);

        return response()->json($plan);
    }

    public function Ticket(int $id_venta): JsonResponse
    {
        $venta = $this->posService->ObtenerVentaPorId($id_venta);
        return response()->json($venta);
    }

    public function ApiCheckout(ProcesarVentaRequest $request): JsonResponse
    {
        $IdUsuario = Auth::id() ?? 1;

        try
        {
            $venta = $this->posService->ProcesarVenta($request->validated(), $IdUsuario);
            return response()->json([
                'mensaje' => 'Venta procesada exitosamente',
                'venta' => $venta,
            ], 201);
        }
        catch (Exception $e)
        {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarMetodoPagoRequest;
use App\Services\MetodosPagoService;
use App\Services\MonedasService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MetodosPagoController extends Controller
{
    protected MetodosPagoService $MetodosPagoService;
    protected MonedasService $MonedasService;

    public function __construct(MetodosPagoService $MetodosPagoService, MonedasService $MonedasService)
    {
        $this->MetodosPagoService = $MetodosPagoService;
        $this->MonedasService = $MonedasService;
    }

    public function index(Request $request): Response|JsonResponse
    {
        $MetodosPago = $this->MetodosPagoService->ListarMetodosPago(false);
        $monedas = $this->MonedasService->ListarMonedas();

        if ($request->expectsJson() && !$request->header('X-Inertia'))
        {
            return response()->json([
                'success' => true,
                'data' => $MetodosPago,
            ]);
        }

        return Inertia::render('MetodosPago/Index', [
            'metodosPago' => $MetodosPago,
            'monedas' => $monedas,
        ]);
    }

    public function store(GuardarMetodoPagoRequest $request): RedirectResponse|JsonResponse
    {
        try
        {
            $metodo = $this->MetodosPagoService->GuardarMetodoPago($request->validated());

            if ($request->expectsJson() && !$request->header('X-Inertia'))
            {
                return response()->json([
                    'success' => true,
                    'message' => 'Método de pago registrado correctamente.',
                    'data' => $metodo,
                ], 201);
            }

            return redirect()->back()->with('success', 'Método de pago registrado con éxito.');
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

            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function update(GuardarMetodoPagoRequest $request, int $id_metodo_pago): RedirectResponse|JsonResponse
    {
        try
        {
            $metodo = $this->MetodosPagoService->GuardarMetodoPago($request->validated(), $id_metodo_pago);

            if ($request->expectsJson() && !$request->header('X-Inertia'))
            {
                return response()->json([
                    'success' => true,
                    'message' => 'Método de pago actualizado correctamente.',
                    'data' => $metodo,
                ]);
            }

            return redirect()->back()->with('success', 'Método de pago actualizado con éxito.');
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

            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function alternarEstado(int $id_metodo_pago): RedirectResponse|JsonResponse
    {
        $metodo = $this->MetodosPagoService->AlternarEstado($id_metodo_pago);

        if (request()->expectsJson() && !request()->header('X-Inertia'))
        {
            return response()->json([
                'success' => true,
                'message' => 'Estado del método de pago actualizado.',
                'data' => $metodo,
            ]);
        }

        return redirect()->back()->with('success', 'Estado modificado exitosamente.');
    }
}

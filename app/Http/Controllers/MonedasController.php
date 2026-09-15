<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarTasasRequest;
use App\Services\MonedasService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MonedasController extends Controller
{
    protected MonedasService $MonedasService;

    public function __construct(MonedasService $MonedasService)
    {
        $this->MonedasService = $MonedasService;
    }

    public function index(Request $request): Response|JsonResponse
    {
        $monedas = $this->MonedasService->ListarMonedas();
        $MonedaPrincipal = $this->MonedasService->ObtenerMonedaPrincipal();

        if ($request->expectsJson() && !$request->header('X-Inertia'))
        {
            return response()->json([
                'success' => true,
                'data' => $monedas,
                'moneda_principal' => $MonedaPrincipal,
            ]);
        }

        // Histórico de la moneda secundaria por defecto (VES o primera que no sea principal)
        $MonedaSecundaria = $monedas->firstWhere('es_principal', false);
        $historico = $MonedaSecundaria
            ? $this->MonedasService->ObtenerHistoricoPorMoneda($MonedaSecundaria->id_moneda, 10)
            : null;

        return Inertia::render('Monedas/Index', [
            'monedas' => $monedas,
            'monedaPrincipal' => $MonedaPrincipal,
            'historicoInicial' => $historico,
        ]);
    }

    public function actualizarTasas(ActualizarTasasRequest $request): RedirectResponse|JsonResponse
    {
        try
        {
            $IdMoneda = (int) $request->input('id_moneda');
            $TasaCambio = (float) $request->input('tasa_cambio');
            $observaciones = $request->input('observaciones');

            $moneda = $this->MonedasService->ActualizarTasa($IdMoneda, $TasaCambio, $observaciones);

            if ($request->expectsJson() && !$request->header('X-Inertia'))
            {
                return response()->json([
                    'success' => true,
                    'message' => 'Tasa de cambio actualizada correctamente.',
                    'data' => $moneda,
                ]);
            }

            return redirect()->back()->with('success', 'Tasa de cambio de ' . $moneda->nombre . ' actualizada exitosamente.');
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

            return redirect()->back()->withErrors(['tasa_cambio' => $e->getMessage()]);
        }
    }

    public function historico(int $id_moneda): JsonResponse
    {
        $historico = $this->MonedasService->ObtenerHistoricoPorMoneda($id_moneda, 20);

        return response()->json([
            'success' => true,
            'data' => $historico,
        ]);
    }
}

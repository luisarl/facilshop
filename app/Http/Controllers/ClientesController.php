<?php

namespace App\Http\Controllers;

use App\Http\Requests\AbonarCreditoRequest;
use App\Http\Requests\GuardarClienteRequest;
use App\Models\MetodosPagoModel;
use App\Models\MonedasModel;
use App\Services\CustomerCreditService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ClientesController extends Controller
{
    public function __construct(
        protected CustomerCreditService $customerCreditService
    )
    {
    }

    public function Index(Request $request): Response
    {
        $filtros = $request->only(['buscar', 'estado_credito']);
        $resultado = $this->customerCreditService->ListarClientes($filtros, 15);

        $metodosPago = MetodosPagoModel::with('Moneda')->where('activo', true)->get();
        $monedas = MonedasModel::where('activo', true)->get();
        $monedaVes = MonedasModel::where('codigo', 'VES')->first();
        $tasaVes = $monedaVes ? (float) $monedaVes->tasa_cambio : 1.0;

        return Inertia::render('Clientes/Index', [
            'clientes' => $resultado['clientes'],
            'kpis' => $resultado['kpis'],
            'filtros' => $filtros,
            'metodosPago' => $metodosPago,
            'monedas' => $monedas,
            'tasaVes' => $tasaVes,
        ]);
    }

    public function Store(GuardarClienteRequest $request): RedirectResponse|JsonResponse
    {
        try
        {
            $cliente = $this->customerCreditService->GuardarCliente($request->validated());

            if ($request->wantsJson())
            {
                return response()->json([
                    'mensaje' => "Cliente '{$cliente->nombre}' registrado exitosamente.",
                    'cliente' => $cliente,
                ], 201);
            }

            return back()->with('success', "Cliente '{$cliente->nombre}' registrado exitosamente.");
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

    public function Update(GuardarClienteRequest $request, int $id_cliente): RedirectResponse
    {
        try
        {
            $cliente = $this->customerCreditService->GuardarCliente($request->validated(), $id_cliente);

            return back()->with('success', "Cliente '{$cliente->nombre}' actualizado exitosamente.");
        }
        catch (Exception $e)
        {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function Abonar(AbonarCreditoRequest $request, int $id_cliente): RedirectResponse|JsonResponse
    {
        $IdUsuario = Auth::id() ?? 1;

        try
        {
            $abono = $this->customerCreditService->AbonarCredito($id_cliente, $request->validated(), $IdUsuario);

            if ($request->wantsJson())
            {
                return response()->json([
                    'mensaje' => sprintf(
                        "Abono de $%.2f registrado con comprobante %s. Nuevo saldo: $%.2f",
                        $abono['monto_abonado'],
                        $abono['comprobante_abono'],
                        $abono['nuevo_saldo']
                    ),
                    'abono' => $abono,
                ], 200);
            }

            return back()->with(
                'success',
                sprintf(
                    "Abono de $%.2f registrado con comprobante %s. Nuevo saldo: $%.2f",
                    $abono['monto_abonado'],
                    $abono['comprobante_abono'],
                    $abono['nuevo_saldo']
                )
            );
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

    public function EstadoCuenta(int $id_cliente): JsonResponse
    {
        try
        {
            $estadoCuenta = $this->customerCreditService->ObtenerEstadoCuenta($id_cliente);
            return response()->json($estadoCuenta);
        }
        catch (Exception $e)
        {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    // API REST Endpoints
    public function ApiIndex(Request $request): JsonResponse
    {
        $filtros = $request->only(['buscar', 'estado_credito']);
        $resultado = $this->customerCreditService->ListarClientes($filtros, 30);

        return response()->json($resultado);
    }

    public function ApiStore(GuardarClienteRequest $request): JsonResponse
    {
        try
        {
            $cliente = $this->customerCreditService->GuardarCliente($request->validated());
            return response()->json([
                'mensaje' => 'Cliente registrado exitosamente',
                'cliente' => $cliente,
            ], 201);
        }
        catch (Exception $e)
        {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function ApiAbonar(AbonarCreditoRequest $request, int $id_cliente): JsonResponse
    {
        $IdUsuario = Auth::id() ?? 1;

        try
        {
            $abono = $this->customerCreditService->AbonarCredito($id_cliente, $request->validated(), $IdUsuario);
            return response()->json([
                'mensaje' => 'Abono registrado exitosamente',
                'abono' => $abono,
            ], 200);
        }
        catch (Exception $e)
        {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}

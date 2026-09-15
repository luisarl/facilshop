<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnularVentaRequest;
use App\Models\MetodosPagoModel;
use App\Services\FacturacionService;
use App\Services\ReceiptService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class FacturacionController extends Controller
{
    public function __construct(
        protected FacturacionService $facturacionService,
        protected ReceiptService $receiptService
    )
    {
    }

    public function Index(Request $request): Response
    {
        $filtros = $request->only([
            'buscar',
            'tipo_comprobante',
            'estado',
            'fecha_desde',
            'fecha_hasta',
            'solo_cashea',
        ]);

        $resultado = $this->facturacionService->ListarVentas($filtros, 15);
        $metodosPago = MetodosPagoModel::where('activo', true)->get();

        return Inertia::render('Facturacion/Index', [
            'ventas' => $resultado['ventas'],
            'metricas' => $resultado['metricas'],
            'filtros' => $filtros,
            'metodosPago' => $metodosPago,
        ]);
    }

    public function Show(int $id_venta): Response|JsonResponse
    {
        try
        {
            $venta = $this->facturacionService->ObtenerVentaDetallada($id_venta);
            $datosPdf = $this->receiptService->GenerarDatosComprobantePdf($venta);

            if (request()->wantsJson())
            {
                return response()->json($datosPdf);
            }

            return Inertia::render('Facturacion/Show', $datosPdf);
        }
        catch (Exception $e)
        {
            if (request()->wantsJson())
            {
                return response()->json(['error' => $e->getMessage()], 404);
            }

            return redirect()->route('facturacion.index')->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function Escpos(int $id_venta, Request $request): HttpResponse
    {
        $venta = $this->facturacionService->ObtenerVentaDetallada($id_venta);
        $textoEscPos = $this->receiptService->GenerarTextoEscPos($venta);

        $filename = "ticket-{$venta->numero_comprobante}.raw";

        return response($textoEscPos, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function Anular(AnularVentaRequest $request, int $id_venta): RedirectResponse
    {
        $IdUsuario = Auth::id() ?? 1;

        try
        {
            $venta = $this->facturacionService->AnularVenta(
                $id_venta,
                $request->input('motivo'),
                $IdUsuario
            );

            return back()->with(
                'success',
                "La venta {$venta->numero_comprobante} ha sido anulada exitosamente y el inventario fue reincorporado."
            );
        }
        catch (Exception $e)
        {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // API REST Endpoints
    public function ApiIndex(Request $request): JsonResponse
    {
        $filtros = $request->only([
            'buscar',
            'tipo_comprobante',
            'estado',
            'fecha_desde',
            'fecha_hasta',
            'solo_cashea',
        ]);

        $resultado = $this->facturacionService->ListarVentas($filtros, 30);
        return response()->json($resultado);
    }

    public function ApiShow(int $id_venta): JsonResponse
    {
        try
        {
            $venta = $this->facturacionService->ObtenerVentaDetallada($id_venta);
            $datos = $this->receiptService->GenerarDatosComprobantePdf($venta);
            return response()->json($datos);
        }
        catch (Exception $e)
        {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    public function ApiAnular(AnularVentaRequest $request, int $id_venta): JsonResponse
    {
        $IdUsuario = Auth::id() ?? 1;

        try
        {
            $venta = $this->facturacionService->AnularVenta(
                $id_venta,
                $request->input('motivo'),
                $IdUsuario
            );

            return response()->json([
                'mensaje' => 'Venta anulada e inventario reincorporado exitosamente',
                'venta' => $venta,
            ]);
        }
        catch (Exception $e)
        {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}

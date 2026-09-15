<?php

namespace App\Http\Controllers;

use App\Services\AuditoriaService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class AuditoriasController extends Controller
{
    public function __construct(
        protected AuditoriaService $auditoriaService
    )
    {
    }

    public function Index(Request $request): Response
    {
        $filtros = $request->only([
            'buscar',
            'modulo',
            'accion',
            'tabla_afectada',
            'id_usuario',
            'fecha_desde',
            'fecha_hasta',
        ]);

        $resultado = $this->auditoriaService->ListarAuditorias($filtros, 20);

        return Inertia::render('Auditorias/Index', [
            'auditorias' => $resultado['auditorias'],
            'kpis' => $resultado['kpis'],
            'usuarios' => $resultado['usuarios'],
            'modulos' => $resultado['modulos'],
            'acciones' => $resultado['acciones'],
            'filtros' => $filtros,
        ]);
    }

    public function Show(int $id_auditoria): JsonResponse
    {
        try
        {
            $detalle = $this->auditoriaService->ObtenerDetalle($id_auditoria);
            return response()->json($detalle);
        }
        catch (Exception $e)
        {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    public function Exportar(Request $request): HttpResponse
    {
        $filtros = $request->only([
            'buscar',
            'modulo',
            'accion',
            'tabla_afectada',
            'id_usuario',
            'fecha_desde',
            'fecha_hasta',
        ]);

        $csv = $this->auditoriaService->GenerarCsvLog($filtros);
        $fecha = date('Ymd_His');

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"auditorias_{$fecha}.csv\"",
        ]);
    }

    // Endpoints REST API
    public function ApiIndex(Request $request): JsonResponse
    {
        $filtros = $request->only([
            'buscar',
            'modulo',
            'accion',
            'tabla_afectada',
            'id_usuario',
            'fecha_desde',
            'fecha_hasta',
        ]);

        $resultado = $this->auditoriaService->ListarAuditorias($filtros, 50);
        return response()->json($resultado);
    }
}

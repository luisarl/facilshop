<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    )
    {
    }

    public function Index(): Response
    {
        $resumen = $this->reportService->ObtenerResumenDashboard();

        return Inertia::render('Dashboard', $resumen);
    }

    public function ApiMetrics(): JsonResponse
    {
        $resumen = $this->reportService->ObtenerResumenDashboard();

        return response()->json($resumen);
    }
}

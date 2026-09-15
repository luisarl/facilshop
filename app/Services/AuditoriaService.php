<?php

namespace App\Services;

use App\Models\AuditoriasModel;
use App\Models\UsuariosModel;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Request;

class AuditoriaService
{
    public function ListarAuditorias(array $filtros = [], int $perPage = 20): array
    {
        $query = AuditoriasModel::with('Usuario');

        if (!empty($filtros['buscar']))
        {
            $termino = trim($filtros['buscar']);
            $query->where(function ($q) use ($termino)
            {
                $q->where('tabla_afectada', 'LIKE', "%{$termino}%")
                    ->orWhere('modulo', 'LIKE', "%{$termino}%")
                    ->orWhere('accion', 'LIKE', "%{$termino}%")
                    ->orWhere('ip_direccion', 'LIKE', "%{$termino}%")
                    ->orWhere('id_registro_afectado', 'LIKE', "%{$termino}%")
                    ->orWhereHas('Usuario', function ($sub) use ($termino)
                    {
                        $sub->where('name', 'LIKE', "%{$termino}%")
                            ->orWhere('email', 'LIKE', "%{$termino}%");
                    });
            });
        }

        if (!empty($filtros['modulo']))
        {
            $query->where('modulo', $filtros['modulo']);
        }

        if (!empty($filtros['accion']))
        {
            $query->where('accion', $filtros['accion']);
        }

        if (!empty($filtros['tabla_afectada']))
        {
            $query->where('tabla_afectada', $filtros['tabla_afectada']);
        }

        if (!empty($filtros['id_usuario']))
        {
            $query->where('id_usuario', $filtros['id_usuario']);
        }

        if (!empty($filtros['fecha_desde']))
        {
            $query->whereDate('created_at', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta']))
        {
            $query->whereDate('created_at', '<=', $filtros['fecha_hasta']);
        }

        $query->orderBy('id_auditoria', 'desc');

        $auditoriasPaginadas = $query->paginate($perPage)->withQueryString();

        // Enriquecer cada registro con diff formateado
        $auditoriasPaginadas->getCollection()->transform(function ($auditoria)
        {
            $auditoria->resumen_cambios = $this->CalcularResumenCambios($auditoria);
            return $auditoria;
        });

        // Métricas rápidas de trazabilidad
        $hoy = Carbon::today();
        $totalHoy = AuditoriasModel::whereDate('created_at', $hoy)->count();
        $totalCreaciones = AuditoriasModel::where('accion', 'CREAR')->count();
        $totalActualizaciones = AuditoriasModel::where('accion', 'ACTUALIZAR')->count();
        $totalEliminaciones = AuditoriasModel::where('accion', 'ELIMINAR')->count();
        $totalAnulaciones = AuditoriasModel::where('accion', 'ANULAR')->count();
        $totalGeneral = AuditoriasModel::count();

        // Lista de usuarios y módulos para selects
        $usuarios = UsuariosModel::select('id_usuario', 'name', 'email')->orderBy('name')->get();
        $modulos = AuditoriasModel::select('modulo')->distinct()->pluck('modulo');
        $acciones = AuditoriasModel::select('accion')->distinct()->pluck('accion');

        return [
            'auditorias' => $auditoriasPaginadas,
            'kpis' => [
                'total_general' => $totalGeneral,
                'total_hoy' => $totalHoy,
                'total_creaciones' => $totalCreaciones,
                'total_actualizaciones' => $totalActualizaciones,
                'total_eliminaciones' => $totalEliminaciones,
                'total_anulaciones' => $totalAnulaciones,
            ],
            'usuarios' => $usuarios,
            'modulos' => $modulos,
            'acciones' => $acciones,
        ];
    }

    public function RegistrarEventoAuditoria(
        ?int $idUsuario,
        string $modulo,
        string $accion,
        string $tabla,
        ?int $idRegistro,
        ?array $valoresAnteriores = null,
        ?array $valoresNuevos = null,
        ?string $ip = null,
        ?string $userAgent = null,
        ?string $url = null
    ): AuditoriasModel
    {
        return AuditoriasModel::create([
            'id_usuario' => $idUsuario,
            'modulo' => strtoupper($modulo),
            'accion' => strtoupper($accion),
            'tabla_afectada' => strtolower($tabla),
            'id_registro_afectado' => $idRegistro,
            'valores_anteriores' => $valoresAnteriores,
            'valores_nuevos' => $valoresNuevos,
            'ip_direccion' => $ip ?? Request::ip(),
            'user_agent' => $userAgent ?? Request::userAgent(),
            'url' => $url ?? Request::fullUrl(),
            'created_at' => now(),
        ]);
    }

    public function ObtenerDetalle(int $id_auditoria): array
    {
        $auditoria = AuditoriasModel::with('Usuario')->findOrFail($id_auditoria);

        $diff = $this->GenerarDiffDetallado(
            $auditoria->valores_anteriores ?? [],
            $auditoria->valores_nuevos ?? []
        );

        return [
            'auditoria' => $auditoria,
            'diff' => $diff,
        ];
    }

    public function GenerarDiffDetallado(array $anteriores, array $nuevos): array
    {
        $claves = array_unique(array_merge(array_keys($anteriores), array_keys($nuevos)));
        sort($claves);

        $diff = [];
        foreach ($claves as $clave)
        {
            $existeEnAnterior = array_key_exists($clave, $anteriores);
            $existeEnNuevo = array_key_exists($clave, $nuevos);

            $valorAnterior = $existeEnAnterior ? $anteriores[$clave] : null;
            $valorNuevo = $existeEnNuevo ? $nuevos[$clave] : null;

            $tipoCambio = 'MODIFICADO';
            if (!$existeEnAnterior && $existeEnNuevo)
            {
                $tipoCambio = 'AGREGADO';
            }
            elseif ($existeEnAnterior && !$existeEnNuevo)
            {
                $tipoCambio = 'ELIMINADO';
            }
            elseif ($valorAnterior === $valorNuevo)
            {
                $tipoCambio = 'SIN_CAMBIO';
            }

            $diff[] = [
                'campo' => $clave,
                'valor_anterior' => $valorAnterior,
                'valor_nuevo' => $valorNuevo,
                'tipo_cambio' => $tipoCambio,
            ];
        }

        return $diff;
    }

    protected function CalcularResumenCambios(AuditoriasModel $auditoria): string
    {
        if ($auditoria->accion === 'CREAR')
        {
            $campos = count($auditoria->valores_nuevos ?? []);
            return "Registro creado con {$campos} atributos iniciales.";
        }

        if ($auditoria->accion === 'ELIMINAR')
        {
            return "Registro eliminado del sistema.";
        }

        if ($auditoria->accion === 'ACTUALIZAR' && is_array($auditoria->valores_nuevos))
        {
            $clavesModificadas = array_keys($auditoria->valores_nuevos);
            return "Campos modificados: " . implode(', ', array_slice($clavesModificadas, 0, 4)) . (count($clavesModificadas) > 4 ? '...' : '');
        }

        return "Acción ejecutada en tabla {$auditoria->tabla_afectada}.";
    }

    public function GenerarCsvLog(array $filtros = []): string
    {
        $datos = $this->ListarAuditorias($filtros, 5000);
        $auditorias = $datos['auditorias']->items();

        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['ID', 'FECHA_HORA', 'USUARIO', 'MODULO', 'ACCION', 'TABLA', 'REGISTRO_ID', 'IP', 'URL', 'VALORES_ANTERIORES', 'VALORES_NUEVOS']);

        foreach ($auditorias as $a)
        {
            fputcsv($output, [
                $a->id_auditoria,
                $a->created_at ? $a->created_at->toDateTimeString() : '',
                $a->Usuario?->name ?? 'Sistema / Anónimo',
                $a->modulo,
                $a->accion,
                $a->tabla_afectada,
                $a->id_registro_afectado,
                $a->ip_direccion,
                $a->url,
                json_encode($a->valores_anteriores, JSON_UNESCAPED_UNICODE),
                json_encode($a->valores_nuevos, JSON_UNESCAPED_UNICODE),
            ]);
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }
}

<?php

namespace App\Services;

use App\Models\CajaTurnosModel;
use App\Models\ClientesModel;
use App\Models\MonedasModel;
use App\Models\VentasModel;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CustomerCreditService
{
    public function ListarClientes(array $filtros = [], int $perPage = 15): array
    {
        $query = ClientesModel::query();

        if (!empty($filtros['buscar']))
        {
            $termino = trim($filtros['buscar']);
            $query->where(function ($q) use ($termino)
            {
                $q->where('nombre', 'LIKE', "%{$termino}%")
                    ->orWhere('identificacion', 'LIKE', "%{$termino}%")
                    ->orWhere('telefono', 'LIKE', "%{$termino}%")
                    ->orWhere('email', 'LIKE', "%{$termino}%");
            });
        }

        if (!empty($filtros['estado_credito']))
        {
            match ($filtros['estado_credito'])
            {
                'CON_DEUDA' => $query->where('saldo_pendiente', '>', 0),
                'AL_DIA' => $query->where('saldo_pendiente', '<=', 0),
                'LIMITE_ALCANZADO' => $query->whereRaw('saldo_pendiente >= limite_credito AND limite_credito > 0'),
                default => null,
            };
        }

        $query->orderBy('nombre', 'asc');

        $clientesPaginados = $query->paginate($perPage)->withQueryString();

        // Calcular KPIs globales de cartera
        $MonedaVes = MonedasModel::where('codigo', 'VES')->first();
        $TasaVes = $MonedaVes ? (float) $MonedaVes->tasa_cambio : 1.0;

        $totalCarteraUsd = (float) ClientesModel::sum('saldo_pendiente');
        $totalLimiteOtorgado = (float) ClientesModel::sum('limite_credito');
        $clientesConDeuda = ClientesModel::where('saldo_pendiente', '>', 0)->count();
        $totalClientes = ClientesModel::count();

        return [
            'clientes' => $clientesPaginados,
            'kpis' => [
                'total_clientes' => $totalClientes,
                'clientes_con_deuda' => $clientesConDeuda,
                'total_cartera_usd' => round($totalCarteraUsd, 2),
                'total_cartera_ves' => round($totalCarteraUsd * $TasaVes, 2),
                'total_limite_otorgado' => round($totalLimiteOtorgado, 2),
                'tasa_ves' => $TasaVes,
            ],
        ];
    }

    public function GuardarCliente(array $datos, ?int $IdCliente = null): ClientesModel
    {
        return DB::transaction(function () use ($datos, $IdCliente)
        {
            if ($IdCliente)
            {
                $cliente = ClientesModel::findOrFail($IdCliente);
                $cliente->update([
                    'identificacion' => $datos['identificacion'],
                    'nombre' => $datos['nombre'],
                    'telefono' => $datos['telefono'] ?? null,
                    'email' => $datos['email'] ?? null,
                    'limite_credito' => $datos['limite_credito'] ?? 0.00,
                ]);
            }
            else
            {
                $cliente = ClientesModel::create([
                    'identificacion' => $datos['identificacion'],
                    'nombre' => $datos['nombre'],
                    'telefono' => $datos['telefono'] ?? null,
                    'email' => $datos['email'] ?? null,
                    'limite_credito' => $datos['limite_credito'] ?? 0.00,
                    'saldo_pendiente' => 0.00,
                ]);
            }

            return $cliente->fresh();
        });
    }

    public function AbonarCredito(int $IdCliente, array $datosAbono, int $IdUsuario): array
    {
        return DB::transaction(function () use ($IdCliente, $datosAbono, $IdUsuario)
        {
            $cliente = ClientesModel::where('id_cliente', $IdCliente)
                ->lockForUpdate()
                ->firstOrFail();

            $SaldoAnterior = (float) $cliente->saldo_pendiente;

            if ($SaldoAnterior <= 0)
            {
                throw new Exception("El cliente '{$cliente->nombre}' no posee saldo pendiente por pagar.");
            }

            $MontoAbonoBase = (float) ($datosAbono['monto_base'] ?? $datosAbono['monto']);

            if ($MontoAbonoBase <= 0)
            {
                throw new Exception('El monto del abono debe ser mayor a cero.');
            }

            if ($MontoAbonoBase > $SaldoAnterior)
            {
                throw new Exception(sprintf(
                    "El monto del abono ($%.2f) excede el saldo pendiente actual ($%.2f).",
                    $MontoAbonoBase,
                    $SaldoAnterior
                ));
            }

            $NuevoSaldo = round($SaldoAnterior - $MontoAbonoBase, 2);
            $cliente->saldo_pendiente = $NuevoSaldo;
            $cliente->save();

            // Verificar si hay turno de caja abierto para registrar el movimiento
            $TurnoActivo = CajaTurnosModel::where('id_usuario', $IdUsuario)
                ->where('estado', 'ABIERTA')
                ->first();

            $comprobanteAbono = sprintf('ABN-%s-%05d', date('Y'), rand(1000, 99999));

            return [
                'cliente' => $cliente->fresh(),
                'comprobante_abono' => $comprobanteAbono,
                'monto_abonado' => $MontoAbonoBase,
                'saldo_anterior' => $SaldoAnterior,
                'nuevo_saldo' => $NuevoSaldo,
                'turno_activo' => $TurnoActivo ? $TurnoActivo->id_caja_turno : null,
                'fecha' => now()->toDateTimeString(),
            ];
        });
    }

    public function ObtenerEstadoCuenta(int $IdCliente): array
    {
        $cliente = ClientesModel::findOrFail($IdCliente);

        $ventasCredito = VentasModel::with(['Detalles.Producto', 'Pagos.MetodoPago', 'Moneda'])
            ->where('id_cliente', $IdCliente)
            ->whereHas('Pagos.MetodoPago', function ($q)
            {
                $q->where('tipo', 'CREDITO');
            })
            ->orderBy('id_venta', 'desc')
            ->get();

        $MonedaVes = MonedasModel::where('codigo', 'VES')->first();
        $TasaVes = $MonedaVes ? (float) $MonedaVes->tasa_cambio : 1.0;

        return [
            'cliente' => $cliente,
            'ventas_credito' => $ventasCredito,
            'saldo_pendiente_usd' => (float) $cliente->saldo_pendiente,
            'saldo_pendiente_ves' => round((float) $cliente->saldo_pendiente * $TasaVes, 2),
            'limite_credito' => (float) $cliente->limite_credito,
            'credito_disponible' => $cliente->CreditoDisponible(),
            'tasa_ves' => $TasaVes,
        ];
    }
}

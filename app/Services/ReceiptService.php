<?php

namespace App\Services;

use App\Models\MonedasModel;
use App\Models\VentasModel;

class ReceiptService
{
    public function GenerarTextoEscPos(VentasModel $venta, int $anchoColumnas = 42): string
    {
        $venta->loadMissing([
            'Detalles.Producto.Unidad',
            'Pagos.MetodoPago.Moneda',
            'Pagos.Moneda',
            'Cliente',
            'CasheaTransaccion',
            'CajaTurno.Usuario',
        ]);

        $MonedaVes = MonedasModel::where('codigo', 'VES')->first();
        $TasaVes = $MonedaVes ? (float) $MonedaVes->tasa_cambio : (float) $venta->tasa_cambio;

        $lineaSeparador = str_repeat('-', $anchoColumnas) . "\n";
        $lineaDoble = str_repeat('=', $anchoColumnas) . "\n";

        // Comandos ESC/POS estándar
        $ESC = "\x1b";
        $GS = "\x1d";
        $Inicializar = $ESC . "@";
        $Centrado = $ESC . "a\x01";
        $Izquierda = $ESC . "a\x00";
        $Derecha = $ESC . "a\x02";
        $NegritaOn = $ESC . "E\x01";
        $NegritaOff = $ESC . "E\x00";
        $DobleAlto = $ESC . "!\x10";
        $Normal = $ESC . "!\x00";
        $CortarPapel = $GS . "V\x41\x10";
        $AbrirCajon = $ESC . "p\x00\x19\xfa";

        $ticket = "";
        $ticket .= $Inicializar;
        $ticket .= $AbrirCajon;

        // Encabezado
        $ticket .= $Centrado;
        $ticket .= $NegritaOn . $DobleAlto . "FACIL SHOP" . $Normal . $NegritaOff . "\n";
        $ticket .= "SISTEMA INTEGRAL DE VENTAS\n";
        $ticket .= "RIF: J-50012345-0\n";
        $ticket .= "Av. Principal, Edif. Facil Shop\n";
        $ticket .= "Tel: (0212) 555-0199 / 0414-0000000\n";
        $ticket .= $lineaDoble;

        // Información de Comprobante
        $ticket .= $Izquierda;
        $ticket .= "COMPROBANTE: " . $venta->numero_comprobante . "\n";
        $ticket .= "TIPO:        " . $venta->tipo_comprobante . "\n";
        $ticket .= "FECHA:       " . $venta->created_at->format('d/m/Y H:i:s') . "\n";
        $ticket .= "CAJERO:      " . ($venta->CajaTurno?->Usuario?->name ?? 'Caja General') . "\n";
        $ticket .= "ESTADO:      " . $venta->estado . "\n";

        // Datos del Cliente
        $cliente = $venta->Cliente;
        $ticket .= $lineaSeparador;
        $ticket .= "CLIENTE:     " . ($cliente?->nombre ?? 'Cliente Mostrador') . "\n";
        $ticket .= "DOC/ID:      " . ($cliente?->identificacion ?? 'V-00000000') . "\n";
        if ($cliente && $cliente->telefono)
        {
            $ticket .= "TELEFONO:    " . $cliente->telefono . "\n";
        }
        $ticket .= $lineaSeparador;

        // Cabecera de Ítems
        $ticket .= sprintf("%-20s %4s %7s %8s\n", "CANT / DESCRIPCION", "UND", "P.UNIT", "TOTAL");
        $ticket .= $lineaSeparador;

        // Ítems
        foreach ($venta->Detalles as $detalle)
        {
            $nombreProd = substr($detalle->Producto?->nombre ?? 'Producto', 0, 40);
            $unidadAbrev = substr($detalle->Producto?->Unidad?->abreviatura ?? 'UND', 0, 4);
            $cantStr = number_format($detalle->cantidad, 0);
            $pUnitStr = number_format((float) $detalle->precio_unitario, 2);
            $subtotalStr = number_format((float) $detalle->subtotal, 2);

            $ticket .= $nombreProd . "\n";
            $ticket .= sprintf("  %3sx %-4s        %8s %8s\n", $cantStr, $unidadAbrev, "$" . $pUnitStr, "$" . $subtotalStr);

            if ((float) $detalle->descuento > 0)
            {
                $ticket .= sprintf("  (Descuento: -%s)\n", "$" . number_format((float) $detalle->descuento, 2));
            }
        }

        $ticket .= $lineaSeparador;

        // Totales en USD y VES
        $totalUsd = (float) $venta->total;
        $totalVes = round($totalUsd * $TasaVes, 2);

        $ticket .= $Derecha;
        if ((float) $venta->descuento_total > 0)
        {
            $ticket .= sprintf("SUBTOTAL: %12s\n", "$" . number_format((float) $venta->subtotal, 2));
            $ticket .= sprintf("DESCUENTO: %11s\n", "-$" . number_format((float) $venta->descuento_total, 2));
        }

        $ticket .= $NegritaOn . sprintf("TOTAL USD: %11s\n", "$" . number_format($totalUsd, 2)) . $NegritaOff;
        $ticket .= sprintf("TASA BCV:  %11s\n", "Bs. " . number_format($TasaVes, 2));
        $ticket .= $NegritaOn . sprintf("TOTAL VES: %11s\n", "Bs. " . number_format($totalVes, 2)) . $NegritaOff;

        // Formas de Pago
        $ticket .= $Izquierda;
        $ticket .= $lineaSeparador;
        $ticket .= $NegritaOn . "FORMAS DE PAGO:\n" . $NegritaOff;

        foreach ($venta->Pagos as $pago)
        {
            $metodoNombre = $pago->MetodoPago?->nombre ?? 'Pago';
            $simbolo = $pago->Moneda?->simbolo ?? '$';
            $montoOriginal = number_format((float) $pago->monto, 2);
            $montoBase = number_format((float) $pago->monto_base, 2);

            $lineaPago = sprintf(" - %-18s %s%s", substr($metodoNombre, 0, 18), $simbolo, $montoOriginal);
            if ($pago->Moneda?->codigo !== 'USD')
            {
                $lineaPago .= sprintf(" ($%s)", $montoBase);
            }
            $ticket .= $lineaPago . "\n";

            if ($pago->referencia)
            {
                $ticket .= "   Ref: " . $pago->referencia . "\n";
            }
        }

        // Financiamiento Cashea BNPL si aplica
        if ($venta->CasheaTransaccion)
        {
            $cashea = $venta->CasheaTransaccion;
            $ticket .= $lineaSeparador;
            $ticket .= $Centrado . $NegritaOn . "*** FINANCIAMIENTO CASHEA (BNPL) ***\n" . $NegritaOff . $Izquierda;
            $ticket .= "REF CASHEA:     " . $cashea->referencia_cashea . "\n";
            $ticket .= "CEDULA:         " . $cashea->cedula_cliente . "\n";
            $ticket .= sprintf("INICIAL PAGADA: $%s (%.0f%%)\n", number_format((float) $cashea->monto_inicial, 2), (float) $cashea->porcentaje_inicial);
            $ticket .= sprintf("SALDO FINANCIADO: $%s\n", number_format((float) $cashea->monto_financiado, 2));
            $ticket .= sprintf("CUOTAS EN APP:  %d cuotas quincenales de $%s\n", (int) $cashea->numero_cuotas, number_format((float) $cashea->monto_cuota, 2));

            if (!empty($cashea->payload['cuotas']) && is_array($cashea->payload['cuotas']))
            {
                $ticket .= "CALENDARIO DE VENCIMIENTOS:\n";
                foreach ($cashea->payload['cuotas'] as $c)
                {
                    $ticket .= sprintf("  Cuota %d: $%s - Vence: %s\n", $c['numero_cuota'], number_format((float) $c['monto'], 2), $c['fecha_formateada'] ?? $c['fecha_vencimiento']);
                }
            }
            $ticket .= "(Pague sus cuotas a tiempo en la app Cashea)\n";
        }

        // Pie de Página
        $ticket .= $lineaDoble;
        $ticket .= $Centrado;
        $ticket .= "GRACIAS POR SU COMPRA!\n";
        $ticket .= "Conserve este comprobante para reclamos o garantias.\n";
        $ticket .= "Verifique su mercancia antes de retirarse.\n";
        $ticket .= "www.facilshop.com\n\n";

        $ticket .= $CortarPapel;

        return $ticket;
    }

    public function GenerarDatosComprobantePdf(VentasModel $venta): array
    {
        $venta->loadMissing([
            'Detalles.Producto.Unidad',
            'Pagos.MetodoPago.Moneda',
            'Pagos.Moneda',
            'Cliente',
            'CasheaTransaccion',
            'CajaTurno.Usuario',
        ]);

        $MonedaVes = MonedasModel::where('codigo', 'VES')->first();
        $TasaVes = $MonedaVes ? (float) $MonedaVes->tasa_cambio : (float) $venta->tasa_cambio;

        $totalUsd = (float) $venta->total;
        $totalVes = round($totalUsd * $TasaVes, 2);

        // Cadena para código QR verificador interno
        $DatosQr = [
            'sistema' => 'FACIL_SHOP',
            'comprobante' => $venta->numero_comprobante,
            'tipo' => $venta->tipo_comprobante,
            'fecha' => $venta->created_at->toIso8601String(),
            'cliente' => $venta->Cliente?->identificacion ?? 'CONSUMIDOR_FINAL',
            'total_usd' => $totalUsd,
            'total_ves' => $totalVes,
            'tasa_bcv' => $TasaVes,
            'hash_seguridad' => hash('sha256', $venta->numero_comprobante . '|' . $totalUsd . '|' . $venta->created_at),
        ];

        return [
            'empresa' => [
                'nombre' => 'FÁCIL SHOP C.A.',
                'rif' => 'J-50012345-0',
                'direccion' => 'Av. Principal, Edificio Fácil Shop, Piso 1, Caracas, Venezuela',
                'telefono' => '(0212) 555-0199 / 0414-0000000',
                'email' => 'ventas@facilshop.com',
                'web' => 'https://facilshop.com',
            ],
            'venta' => $venta,
            'tasa_ves' => $TasaVes,
            'total_ves' => $totalVes,
            'qr_contenido' => json_encode($DatosQr),
            'qr_datos' => $DatosQr,
        ];
    }
}

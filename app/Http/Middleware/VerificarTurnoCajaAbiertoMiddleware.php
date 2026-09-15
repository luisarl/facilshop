<?php

namespace App\Http\Middleware;

use App\Models\CajaTurnosModel;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerificarTurnoCajaAbiertoMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $idUsuario = Auth::id();

        if (!$idUsuario)
        {
            return redirect()->route('login');
        }

        $TurnoAbierto = CajaTurnosModel::where('id_usuario', $idUsuario)
            ->where('estado', 'ABIERTA')
            ->first();

        if (!$TurnoAbierto)
        {
            if ($request->expectsJson())
            {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene un turno de caja abierto. Debe abrir caja antes de realizar operaciones de venta.',
                ], 403);
            }

            return redirect()->route('caja.index')->with('error', 'Debe aperturar un turno de caja para continuar.');
        }

        return $next($request);
    }
}

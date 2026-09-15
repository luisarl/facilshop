<?php

namespace App\Services;

use App\Models\MetodosPagoModel;
use Illuminate\Database\Eloquent\Collection;

class MetodosPagoService
{
    public function ListarMetodosPago(bool $soloActivos = true): Collection
    {
        $query = MetodosPagoModel::with('Moneda:id_moneda,codigo,nombre,simbolo,tasa_cambio');

        if ($soloActivos)
        {
            $query->where('activo', true);
        }

        return $query->orderBy('id_metodo_pago', 'asc')->get();
    }

    public function ObtenerMetodoPorId(int $IdMetodoPago): MetodosPagoModel
    {
        return MetodosPagoModel::with('Moneda')->findOrFail($IdMetodoPago);
    }

    public function GuardarMetodoPago(array $datos, ?int $IdMetodoPago = null): MetodosPagoModel
    {
        if ($IdMetodoPago)
        {
            $metodo = MetodosPagoModel::findOrFail($IdMetodoPago);
            $metodo->update($datos);
            return $metodo;
        }

        return MetodosPagoModel::create($datos);
    }

    public function AlternarEstado(int $IdMetodoPago): MetodosPagoModel
    {
        $metodo = MetodosPagoModel::findOrFail($IdMetodoPago);
        $metodo->activo = !$metodo->activo;
        $metodo->save();

        return $metodo;
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarAjusteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'naturaleza' => ['required', 'in:ENTRADA,SALIDA'],
            'id_tipo_movimiento' => ['required', 'integer', 'exists:tipos_movimiento_inventario,id_tipo_movimiento'],
            'motivo' => ['required', 'string', 'max:255'],
            'documento_referencia' => ['nullable', 'string', 'max:100'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.id_producto' => ['required', 'integer', 'exists:productos,id_producto'],
            'detalles.*.id_unidad' => ['required', 'integer', 'exists:unidades_productos,id_unidad'],
            'detalles.*.cantidad' => ['required', 'numeric', 'min:0.0001'],
            'detalles.*.costo_unitario' => ['nullable', 'numeric', 'min:0'],
            'detalles.*.observaciones' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'naturaleza.required' => 'Debe seleccionar si el ajuste es de ENTRADA o SALIDA.',
            'id_tipo_movimiento.required' => 'Debe seleccionar un tipo de movimiento.',
            'motivo.required' => 'El motivo o justificación del ajuste es obligatorio.',
            'detalles.required' => 'Debe agregar al menos un producto al ajuste.',
            'detalles.min' => 'Debe agregar al menos un producto al ajuste.',
            'detalles.*.cantidad.required' => 'La cantidad para cada producto es requerida.',
            'detalles.*.cantidad.min' => 'La cantidad debe ser mayor a cero.',
        ];
    }
}

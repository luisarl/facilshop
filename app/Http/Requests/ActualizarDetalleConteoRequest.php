<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarDetalleConteoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_conteo_detalle' => ['required', 'integer', 'exists:inventario_conteo_detalles,id_conteo_detalle'],
            'items.*.stock_fisico' => ['required', 'integer', 'min:0'],
            'items.*.observaciones' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Debe enviar al menos un item para actualizar el conteo.',
            'items.*.stock_fisico.required' => 'El stock físico es obligatorio.',
            'items.*.stock_fisico.min' => 'El stock físico no puede ser negativo.',
        ];
    }
}

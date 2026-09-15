<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarTasasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_moneda' => ['required', 'exists:monedas,id_moneda'],
            'tasa_cambio' => ['required', 'numeric', 'gt:0'],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_moneda.required' => 'Debe seleccionar una moneda válida.',
            'id_moneda.exists' => 'La moneda seleccionada no existe en el sistema.',
            'tasa_cambio.required' => 'Debe ingresar el valor de la nueva tasa.',
            'tasa_cambio.numeric' => 'La tasa de cambio debe ser un número.',
            'tasa_cambio.gt' => 'La tasa de cambio debe ser mayor a cero.',
        ];
    }
}

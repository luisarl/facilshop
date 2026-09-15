<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AbonarCreditoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monto' => ['required', 'numeric', 'min:0.01'],
            'monto_base' => ['required', 'numeric', 'min:0.01'],
            'id_metodo_pago' => ['required', 'exists:metodos_pago,id_metodo_pago'],
            'id_moneda' => ['required', 'exists:monedas,id_moneda'],
            'tasa_cambio' => ['required', 'numeric', 'min:0.0001'],
            'referencia' => ['nullable', 'string', 'max:100'],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'monto.required' => 'El monto a abonar es obligatorio.',
            'monto.min' => 'El monto del abono debe ser mayor a cero.',
            'id_metodo_pago.required' => 'Debe seleccionar un método de pago.',
            'id_moneda.required' => 'La moneda del abono es obligatoria.',
        ];
    }
}

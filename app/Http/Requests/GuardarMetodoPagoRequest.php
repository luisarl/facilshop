<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarMetodoPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $IdMetodoPago = $this->route('id_metodo_pago') ?? $this->id_metodo_pago;

        return [
            'nombre' => ['required', 'string', 'max:100'],
            'codigo' => [
                'required',
                'string',
                'max:50',
                Rule::unique('metodos_pago', 'codigo')->ignore($IdMetodoPago, 'id_metodo_pago'),
            ],
            'id_moneda' => ['required', 'exists:monedas,id_moneda'],
            'tipo' => ['required', 'in:EFECTIVO,DIGITAL,TRANSFERENCIA,TARJETA,CREDITO,FINANCIAMIENTO'],
            'requiere_referencia' => ['boolean'],
            'activo' => ['boolean'],
        ];
    }
}

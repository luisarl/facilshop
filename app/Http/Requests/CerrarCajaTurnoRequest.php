<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CerrarCajaTurnoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monto_final_declarado' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'monto_final_declarado.required' => 'Debe ingresar el monto en efectivo contado físicamente en caja.',
            'monto_final_declarado.numeric' => 'El monto declarado debe ser numérico.',
            'monto_final_declarado.min' => 'El monto declarado no puede ser negativo.',
        ];
    }
}

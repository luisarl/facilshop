<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AbrirCajaTurnoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monto_inicial' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'monto_inicial.required' => 'Debe ingresar el fondo inicial de apertura.',
            'monto_inicial.numeric' => 'El monto inicial debe ser numérico.',
            'monto_inicial.min' => 'El monto inicial no puede ser un valor negativo.',
        ];
    }
}

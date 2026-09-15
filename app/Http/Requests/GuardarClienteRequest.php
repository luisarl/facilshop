<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $idCliente = $this->route('id_cliente') ?? $this->input('id_cliente');

        return [
            'identificacion' => [
                'required',
                'string',
                'max:20',
                Rule::unique('clientes', 'identificacion')->ignore($idCliente, 'id_cliente'),
            ],
            'nombre' => ['required', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'limite_credito' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'identificacion.required' => 'El número de identificación (Cédula/RIF) es obligatorio.',
            'identificacion.unique' => 'Ya existe un cliente registrado con esta identificación.',
            'nombre.required' => 'El nombre o razón social del cliente es obligatorio.',
            'limite_credito.required' => 'El límite de crédito es obligatorio.',
            'limite_credito.min' => 'El límite de crédito no puede ser negativo.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IniciarConteoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'descripcion' => ['nullable', 'string', 'max:255'],
            'id_categoria' => ['nullable', 'integer', 'exists:clasificacion_productos,id_categoria'],
            'id_marca' => ['nullable', 'integer', 'exists:marcas_productos,id_marca'],
            'precargar_stock' => ['nullable', 'boolean'],
        ];
    }
}

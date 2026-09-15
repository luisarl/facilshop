<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarMarcaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $IdMarca = $this->route('id_marca') ?? $this->input('id_marca');

        return [
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('marcas_productos', 'nombre')->ignore($IdMarca, 'id_marca'),
            ],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la marca comercial es obligatorio.',
            'nombre.unique' => 'Ya existe una marca registrada con este nombre.',
        ];
    }
}

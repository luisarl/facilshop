<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarCategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $IdCategoria = $this->route('id_categoria') ?? $this->input('id_categoria');

        return [
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('clasificacion_productos', 'nombre')->ignore($IdCategoria, 'id_categoria'),
            ],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'id_categoria_padre' => ['nullable', 'exists:clasificacion_productos,id_categoria'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
            'nombre.unique' => 'Ya existe una categoría registrada con este nombre.',
            'id_categoria_padre.exists' => 'La categoría superior seleccionada no existe.',
        ];
    }
}

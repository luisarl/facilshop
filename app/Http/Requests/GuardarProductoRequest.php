<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('codigo_barras') && trim((string) $this->input('codigo_barras')) === '')
        {
            $this->merge([
                'codigo_barras' => null,
            ]);
        }
    }

    public function rules(): array
    {
        $IdProducto = $this->route('id_producto') ?? $this->route('producto') ?? $this->input('id_producto');

        return [
            'sku' => [
                'required',
                'string',
                'max:50',
                Rule::unique('productos', 'sku')->ignore($IdProducto, 'id_producto'),
            ],
            'codigo_barras' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('productos', 'codigo_barras')->ignore($IdProducto, 'id_producto'),
            ],
            'nombre' => ['required', 'string', 'max:150'],
            'modelo' => ['nullable', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'id_marca' => ['nullable', 'integer', 'exists:marcas_productos,id_marca'],
            'id_categoria' => ['nullable', 'integer', 'exists:clasificacion_productos,id_categoria'],
            'id_unidad' => ['required', 'integer', 'exists:unidades_productos,id_unidad'],
            'id_unidad_secundaria' => ['nullable', 'integer', 'exists:unidades_productos,id_unidad'],
            'equivalencia_unidad' => ['required', 'numeric', 'min:0.0001'],
            'equivalencia_unidad_secundaria' => ['nullable', 'numeric', 'min:0.0001'],
            'precio_costo' => ['required', 'numeric', 'min:0'],
            'precio_venta' => ['required', 'numeric', 'min:0'],
            'stock_actual' => ['nullable', 'integer', 'min:0'],
            'stock_minimo' => ['required', 'integer', 'min:0'],
            'imagen_principal' => $this->hasFile('imagen_principal')
                ? ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:5120']
                : ['nullable', 'string', 'max:255'],
            'imagenes' => ['nullable', 'array'],
            'imagenes.*' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:5120'],
            'imagenes_eliminar' => ['nullable', 'array'],
            'imagenes_eliminar.*' => ['integer'],
            'fecha_vencimiento' => ['nullable', 'date'],
            'activo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.required' => 'El código SKU es obligatorio.',
            'sku.unique' => 'El código SKU ya se encuentra registrado.',
            'codigo_barras.unique' => 'El código de barras ya se encuentra registrado.',
            'nombre.required' => 'El nombre del producto es obligatorio.',
            'id_unidad.required' => 'La unidad de medida principal es obligatoria.',
            'precio_costo.required' => 'El precio de costo es obligatorio.',
            'precio_venta.required' => 'El precio de venta es obligatorio.',
            'imagenes.*.image' => 'El archivo seleccionado debe ser una imagen válida.',
            'imagenes.*.max' => 'Cada imagen no debe superar los 5MB.',
        ];
    }
}

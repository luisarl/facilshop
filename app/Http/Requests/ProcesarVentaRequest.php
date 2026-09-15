<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcesarVentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_cliente' => ['required', 'integer', 'exists:clientes,id_cliente'],
            'tipo_comprobante' => ['required', 'in:TICKET,BOLETA,FACTURA'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.id_producto' => ['required', 'integer', 'exists:productos,id_producto'],
            'detalles.*.id_unidad' => ['nullable', 'integer', 'exists:unidades_productos,id_unidad'],
            'detalles.*.cantidad' => ['required', 'numeric', 'min:0.001'],
            'detalles.*.precio_unitario' => ['required', 'numeric', 'min:0'],
            'detalles.*.descuento' => ['nullable', 'numeric', 'min:0'],
            'pagos' => ['required', 'array', 'min:1'],
            'pagos.*.id_metodo_pago' => ['required', 'integer', 'exists:metodos_pago,id_metodo_pago'],
            'pagos.*.id_moneda' => ['required', 'integer', 'exists:monedas,id_moneda'],
            'pagos.*.monto' => ['required', 'numeric', 'min:0.01'],
            'pagos.*.tasa_cambio' => ['required', 'numeric', 'min:0.0001'],
            'pagos.*.monto_base' => ['required', 'numeric', 'min:0.01'],
            'pagos.*.referencia' => ['nullable', 'string', 'max:100'],
            'cashea' => ['nullable', 'array'],
            'cashea.cedula_cliente' => ['required_with:cashea', 'string', 'max:20'],
            'cashea.telefono_cliente' => ['nullable', 'string', 'max:30'],
            'cashea.monto_total' => ['required_with:cashea', 'numeric', 'min:0.01'],
            'cashea.referencia_cashea' => ['required_with:cashea', 'string', 'max:100'],
            'cashea.codigo_autorizacion' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_cliente.required' => 'Debe seleccionar un cliente para la venta.',
            'detalles.required' => 'El carrito no contiene productos.',
            'detalles.min' => 'Debe agregar al menos un producto a la venta.',
            'pagos.required' => 'Debe ingresar al menos un método de pago.',
            'pagos.min' => 'Debe ingresar al menos un método de pago.',
        ];
    }
}

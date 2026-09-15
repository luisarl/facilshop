<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $IdUsuario = $this->route('id_usuario') ?? $this->input('id_usuario');

        $rules = [
            'nombre' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'email',
                'max:150',
                Rule::unique('usuarios', 'email')->ignore($IdUsuario, 'id_usuario'),
            ],
            'rol' => ['required', 'in:superadmin,admin,cajero'],
            'activo' => ['sometimes', 'boolean'],
        ];

        if ($IdUsuario)
        {
            $rules['password'] = ['nullable', 'string', 'min:6'];
        }
        else
        {
            $rules['password'] = ['required', 'string', 'min:6'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre completo del usuario es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.unique' => 'Ya existe un usuario registrado con este correo electrónico.',
            'rol.required' => 'Debe seleccionar un rol para el usuario.',
            'password.required' => 'La contraseña es obligatoria para nuevos usuarios.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ];
    }
}

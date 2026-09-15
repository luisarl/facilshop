<?php

namespace App\Services;

use App\Models\ClasificacionProductosModel;
use App\Models\HistoricoTasasCambioModel;
use App\Models\MarcasProductosModel;
use App\Models\MonedasModel;
use App\Models\UsuariosModel;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ConfiguracionService
{
    public function ObtenerDatosConfiguracion(): array
    {
        $usuarios = UsuariosModel::withCount(['CajaTurnos', 'MovimientosInventario'])
            ->orderBy('id_usuario', 'asc')
            ->get();

        $categorias = ClasificacionProductosModel::with('Padre')
            ->withCount('Productos')
            ->orderBy('nombre', 'asc')
            ->get();

        $marcas = MarcasProductosModel::withCount('Productos')
            ->orderBy('nombre', 'asc')
            ->get();

        $monedas = MonedasModel::with(['HistoricoTasas' => function ($q)
        {
            $q->latest('created_at')->take(5);
        }])
            ->orderBy('es_principal', 'desc')
            ->orderBy('codigo', 'asc')
            ->get();

        $HistoricoTasas = HistoricoTasasCambioModel::with(['Moneda', 'Usuario'])
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        return [
            'usuarios' => $usuarios,
            'categorias' => $categorias,
            'marcas' => $marcas,
            'monedas' => $monedas,
            'historico_tasas' => $HistoricoTasas,
            'kpis' => [
                'total_usuarios' => $usuarios->count(),
                'total_categorias' => $categorias->count(),
                'total_marcas' => $marcas->count(),
                'total_monedas' => $monedas->count(),
            ],
        ];
    }

    // --- GESTIÓN DE USUARIOS ---
    public function GuardarUsuario(array $datos, ?int $IdUsuario = null): UsuariosModel
    {
        return DB::transaction(function () use ($datos, $IdUsuario)
        {
            if ($IdUsuario)
            {
                $usuario = UsuariosModel::findOrFail($IdUsuario);
                $UpdateData = [
                    'nombre' => $datos['nombre'],
                    'name' => $datos['nombre'],
                    'email' => $datos['email'],
                    'rol' => $datos['rol'],
                ];

                if (isset($datos['activo']))
                {
                    $UpdateData['activo'] = (bool) $datos['activo'];
                }

                if (!empty($datos['password']))
                {
                    $UpdateData['password'] = Hash::make($datos['password']);
                }

                $usuario->update($UpdateData);
            }
            else
            {
                $usuario = UsuariosModel::create([
                    'nombre' => $datos['nombre'],
                    'name' => $datos['nombre'],
                    'email' => $datos['email'],
                    'password' => Hash::make($datos['password']),
                    'rol' => $datos['rol'],
                    'activo' => $datos['activo'] ?? true,
                ]);
            }

            return $usuario->fresh();
        });
    }

    public function AlternarEstadoUsuario(int $IdUsuario, int $IdUsuarioActual): UsuariosModel
    {
        $usuario = UsuariosModel::findOrFail($IdUsuario);

        if ($usuario->id_usuario === $IdUsuarioActual)
        {
            throw new Exception('No puede desactivar su propia cuenta de usuario en sesión.');
        }

        $usuario->activo = !$usuario->activo;
        $usuario->save();

        return $usuario;
    }

    // --- GESTIÓN DE CATEGORÍAS ---
    public function GuardarCategoria(array $datos, ?int $IdCategoria = null): ClasificacionProductosModel
    {
        return DB::transaction(function () use ($datos, $IdCategoria)
        {
            if ($IdCategoria)
            {
                $categoria = ClasificacionProductosModel::findOrFail($IdCategoria);
                $categoria->update([
                    'nombre' => $datos['nombre'],
                    'descripcion' => $datos['descripcion'] ?? null,
                    'id_categoria_padre' => $datos['id_categoria_padre'] ?? null,
                    'activo' => $datos['activo'] ?? $categoria->activo,
                ]);
            }
            else
            {
                $categoria = ClasificacionProductosModel::create([
                    'nombre' => $datos['nombre'],
                    'descripcion' => $datos['descripcion'] ?? null,
                    'id_categoria_padre' => $datos['id_categoria_padre'] ?? null,
                    'activo' => $datos['activo'] ?? true,
                ]);
            }

            return $categoria->fresh(['Padre']);
        });
    }

    public function AlternarEstadoCategoria(int $IdCategoria): ClasificacionProductosModel
    {
        $categoria = ClasificacionProductosModel::findOrFail($IdCategoria);
        $categoria->activo = !$categoria->activo;
        $categoria->save();

        return $categoria;
    }

    // --- GESTIÓN DE MARCAS ---
    public function GuardarMarca(array $datos, ?int $IdMarca = null): MarcasProductosModel
    {
        return DB::transaction(function () use ($datos, $IdMarca)
        {
            if ($IdMarca)
            {
                $marca = MarcasProductosModel::findOrFail($IdMarca);
                $marca->update([
                    'nombre' => $datos['nombre'],
                    'descripcion' => $datos['descripcion'] ?? null,
                    'activo' => $datos['activo'] ?? $marca->activo,
                ]);
            }
            else
            {
                $marca = MarcasProductosModel::create([
                    'nombre' => $datos['nombre'],
                    'descripcion' => $datos['descripcion'] ?? null,
                    'activo' => $datos['activo'] ?? true,
                ]);
            }

            return $marca->fresh();
        });
    }

    public function AlternarEstadoMarca(int $IdMarca): MarcasProductosModel
    {
        $marca = MarcasProductosModel::findOrFail($IdMarca);
        $marca->activo = !$marca->activo;
        $marca->save();

        return $marca;
    }
}
